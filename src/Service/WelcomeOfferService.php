<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class WelcomeOfferService
{
    private const WELCOME_PERCENT = 10;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getPercent(): int
    {
        return self::WELCOME_PERCENT;
    }

    public function getLabel(): string
    {
        return sprintf('Bienvenue Société secrète -%d %%', self::WELCOME_PERCENT);
    }

    public function isEligible(?User $user, string $email): bool
    {
        return $this->isEligibleForPreview($user, $email, $user?->isMarketingOptIn() ?? false);
    }

    public function isEligibleForPreview(?User $user, string $email, bool $marketingOptIn): bool
    {
        $normalizedEmail = mb_strtolower(trim($email));

        if (!$marketingOptIn) {
            return false;
        }

        if ($user instanceof User) {
            if ($user->isAccountClosed() || $user->hasUsedWelcomeDiscount()) {
                return false;
            }

            $normalizedEmail = mb_strtolower((string) ($user->getEmail() ?: $normalizedEmail));
        }

        if ('' === $normalizedEmail) {
            return false;
        }

        return 0 === $this->countCommercialOrders($user, $normalizedEmail);
    }

    public function hasCommercialOrder(?User $user, string $email): bool
    {
        $normalizedEmail = mb_strtolower(trim($email));

        if ($user instanceof User) {
            $normalizedEmail = mb_strtolower((string) ($user->getEmail() ?: $normalizedEmail));
        }

        if ('' === $normalizedEmail) {
            return false;
        }

        return $this->countCommercialOrders($user, $normalizedEmail) > 0;
    }

    public function calculateDiscountCents(int $subtotalCents): int
    {
        if ($subtotalCents <= 0) {
            return 0;
        }

        return max(0, (int) round($subtotalCents * (self::WELCOME_PERCENT / 100)));
    }

    public function applyWelcomeDiscount(Order $order): void
    {
        $subtotal = $order->getSubtotalCents();
        $discount = $this->calculateDiscountCents($subtotal);

        $order
            ->setDiscountLabel($discount > 0 ? $this->getLabel() : null)
            ->setDiscountCents($discount)
            ->setTotalCents(max(0, $subtotal - $discount));
    }

    public function markUsedIfNeeded(Order $order): void
    {
        if (!$order->hasDiscount()) {
            return;
        }

        $customer = $order->getCustomerAccount();

        if (!$customer instanceof User || $customer->hasUsedWelcomeDiscount()) {
            return;
        }

        $customer->setWelcomeDiscountUsedAt(new \DateTimeImmutable());
    }

    private function countCommercialOrders(?User $user, string $email): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT o.id)')
            ->from(Order::class, 'o');

        $conditions = [$queryBuilder->expr()->eq('o.customerEmail', ':email')];
        $queryBuilder->setParameter('email', $email);

        if ($user instanceof User && null !== $user->getId()) {
            $conditions[] = $queryBuilder->expr()->eq('o.customerAccount', ':user');
            $queryBuilder->setParameter('user', $user);
        }

        $queryBuilder
            ->andWhere(call_user_func_array([$queryBuilder->expr(), 'orX'], $conditions))
            ->andWhere('(o.paymentStatus IN (:paidStatuses) OR o.status IN (:retainedStatuses))')
            ->setParameter('paidStatuses', [
                Order::PAYMENT_STATUS_AUTHORIZED,
                Order::PAYMENT_STATUS_PAID,
                Order::PAYMENT_STATUS_REFUNDED,
            ])
            ->setParameter('retainedStatuses', [
                Order::STATUS_PAID,
                Order::STATUS_PROCESSING,
                Order::STATUS_SHIPPED,
                Order::STATUS_CLOSED,
                Order::STATUS_REFUNDED,
            ]);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
