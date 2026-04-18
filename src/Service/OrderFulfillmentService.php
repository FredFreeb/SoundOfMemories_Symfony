<?php

namespace App\Service;

use App\Entity\Order;
use App\Repository\ProductRepository;
use App\Repository\ProductVariantRepository;
use Doctrine\ORM\EntityManagerInterface;

class OrderFulfillmentService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $products,
        private readonly ProductVariantRepository $variants,
        private readonly WelcomeOfferService $welcomeOfferService,
    ) {
    }

    public function markPaid(Order $order, ?string $paymentReference = null): void
    {
        if ($order->isPaid()) {
            return;
        }

        foreach ($order->getItems() as $item) {
            $product = null !== $item->getProductIdSnapshot()
                ? $this->products->find($item->getProductIdSnapshot())
                : null;

            if (null === $product) {
                continue;
            }

            $variant = null !== $item->getProductVariantIdSnapshot()
                ? $this->variants->find($item->getProductVariantIdSnapshot())
                : null;

            if (null !== $variant && $variant->getProduct() === $product) {
                $variant->setStock(max(0, $variant->getStock() - $item->getQuantity()));
                $product->setStock($product->getDisplayStock());

                $defaultVariant = $product->getDefaultVariant();
                if (null !== $defaultVariant) {
                    $product->setPriceCents($defaultVariant->getPriceCents());
                }

                continue;
            }

            $product->setStock(max(0, $product->getStock() - $item->getQuantity()));
        }

        $order
            ->setStatus(Order::STATUS_PAID)
            ->setPaymentStatus(Order::PAYMENT_STATUS_PAID)
            ->setDeliveryStatus(Order::DELIVERY_STATUS_PENDING)
            ->setPaymentReference($paymentReference)
            ->setPaidAt(new \DateTimeImmutable());

        $this->welcomeOfferService->markUsedIfNeeded($order);
        $this->entityManager->flush();
    }

    public function markFailed(Order $order, ?string $paymentReference = null): void
    {
        $order
            ->setStatus(Order::STATUS_CANCELLED)
            ->setPaymentStatus(Order::PAYMENT_STATUS_FAILED)
            ->setDeliveryStatus(Order::DELIVERY_STATUS_PENDING)
            ->setPaymentReference($paymentReference);

        $this->entityManager->flush();
    }
}
