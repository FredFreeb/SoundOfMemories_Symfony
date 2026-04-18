<?php

namespace App\Service;

use App\Entity\Order;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MollieCheckoutService
{
    public function __construct(
        private readonly string $mollieApiKey,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== trim($this->mollieApiKey);
    }

    /**
     * @throws ApiException
     */
    public function createPayment(Order $order): Payment
    {
        return $this->getClient()->payments->create([
            'amount' => [
                'currency' => 'EUR',
                // Fred note: Mollie attend un montant decimal sous forme de chaine, donc je convertis les centimes ici.
                'value' => number_format($order->getTotalCents() / 100, 2, '.', ''),
            ],
            'description' => sprintf('Commande #%d - Expedition Mystere', $order->getId()),
            'redirectUrl' => $this->urlGenerator->generate('store_checkout_success', [
                'order' => $order->getId(),
                'token' => $order->getCheckoutAccessToken(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
            'webhookUrl' => $this->urlGenerator->generate('store_checkout_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'metadata' => [
                'order_id' => (string) $order->getId(),
            ],
            'locale' => 'fr_FR',
        ]);
    }

    /**
     * @throws ApiException
     */
    public function retrievePayment(string $paymentId): Payment
    {
        return $this->getClient()->payments->get($paymentId);
    }

    private function getClient(): MollieApiClient
    {
        $client = new MollieApiClient();
        $client->setApiKey($this->mollieApiKey);

        return $client;
    }
}
