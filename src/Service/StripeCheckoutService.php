<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeCheckoutService
{
    public function __construct(
        private readonly string $stripeSecretKey,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== trim($this->stripeSecretKey);
    }

    public function createCheckoutSession(Order $order): Session
    {
        return $this->getClient()->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => $this->urlGenerator->generate('store_checkout_success', [
                'order' => $order->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL) . '&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->urlGenerator->generate('store_checkout_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'customer_email' => $order->getCustomerEmail(),
            'metadata' => [
                'order_id' => (string) $order->getId(),
            ],
            'line_items' => $this->buildLineItems($order),
        ]);
    }

    public function retrieveCheckoutSession(string $sessionId): Session
    {
        return $this->getClient()->checkout->sessions->retrieve($sessionId, []);
    }

    private function getClient(): StripeClient
    {
        return new StripeClient($this->stripeSecretKey);
    }

    private function buildLineItems(Order $order): array
    {
        $discountCents = max(0, $order->getDiscountCents());
        $unitLines = [];

        foreach ($order->getItems() as $item) {
            for ($index = 0; $index < $item->getQuantity(); ++$index) {
                $unitLines[] = [
                    'name' => $item->getProductName(),
                    'base' => $item->getUnitPriceCents(),
                    'discount' => 0,
                ];
            }
        }

        $subtotal = array_reduce(
            $unitLines,
            static fn (int $carry, array $line): int => $carry + $line['base'],
            0,
        );

        if ($discountCents > 0 && $subtotal > 0) {
            $remainingDiscount = $discountCents;
            $lastIndex = count($unitLines) - 1;

            foreach ($unitLines as $index => &$line) {
                if ($remainingDiscount <= 0) {
                    break;
                }

                if ($index === $lastIndex) {
                    $line['discount'] = min($remainingDiscount, $line['base']);
                    $remainingDiscount -= $line['discount'];

                    continue;
                }

                $share = (int) floor($discountCents * ($line['base'] / $subtotal));
                $line['discount'] = min($share, $line['base']);
                $remainingDiscount -= $line['discount'];
            }
            unset($line);

            if ($remainingDiscount > 0) {
                foreach ($unitLines as &$line) {
                    if ($remainingDiscount <= 0) {
                        break;
                    }

                    $headroom = $line['base'] - $line['discount'];

                    if ($headroom <= 0) {
                        continue;
                    }

                    ++$line['discount'];
                    --$remainingDiscount;
                }
                unset($line);
            }
        }

        $lineItems = array_map(static fn (array $line): array => [
            'quantity' => 1,
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => max(0, $line['base'] - $line['discount']),
                'product_data' => [
                    'name' => $line['name'],
                ],
            ],
        ], $unitLines);

        if ($order->getShippingRateCents() > 0) {
            $lineItems[] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $order->getShippingRateCents(),
                    'product_data' => [
                        'name' => $order->getShippingMethodLabel() ?: 'Frais de livraison',
                    ],
                ],
            ];
        }

        return $lineItems;
    }
}
