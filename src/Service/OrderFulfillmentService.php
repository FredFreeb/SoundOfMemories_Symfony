<?php

namespace App\Service;

use App\Entity\Order;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class OrderFulfillmentService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProductRepository $products,
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
