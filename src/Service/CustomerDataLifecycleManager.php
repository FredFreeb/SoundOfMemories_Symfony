<?php

namespace App\Service;

use App\Entity\CustomerConversation;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CustomerDataLifecycleManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly string $userAvatarsDir,
    ) {
    }

    public function hasRetainedOrderData(User $user): bool
    {
        return $this->countRetainedOrders($user) > 0;
    }

    public function revokeMarketing(User $user): void
    {
        $user->setMarketingOptIn(false);
        $this->entityManager->flush();
    }

    public function processErasureRequest(User $user): string
    {
        if ($user->isAdmin()) {
            throw new \LogicException('Le compte administrateur ne peut pas être traité par ce flux client.');
        }

        $hasRetainedOrders = $this->hasRetainedOrderData($user);
        $conversations = $this->findConversationsFor($user);
        $orders = $this->findOrdersFor($user);

        if (!$hasRetainedOrders) {
            foreach ($conversations as $conversation) {
                $this->entityManager->remove($conversation);
            }

            foreach ($orders as $order) {
                foreach ($order->getItems()->toArray() as $item) {
                    $this->entityManager->remove($item);
                }

                $this->entityManager->remove($order);
            }

            $this->removeAvatar($user);
            $this->entityManager->remove($user);
            $this->entityManager->flush();

            return 'deleted';
        }

        $anonymizedEmail = $this->buildAnonymizedEmail($user);

        foreach ($conversations as $conversation) {
            if (null === $conversation->getOrderRef() || !$this->isRetainedOrder($conversation->getOrderRef())) {
                $this->entityManager->remove($conversation);

                continue;
            }

            $conversation
                ->setCustomerAccount(null)
                ->setCustomerName('Compte anonymisé')
                ->setCustomerEmail($anonymizedEmail);
        }

        foreach ($orders as $order) {
            if ($this->isRetainedOrder($order)) {
                continue;
            }

            foreach ($order->getItems()->toArray() as $item) {
                $this->entityManager->remove($item);
            }

            $this->entityManager->remove($order);
        }

        $this->removeAvatar($user);

        $user
            ->setMarketingOptIn(false)
            ->setEmail($anonymizedEmail)
            ->setFullName('Compte anonymisé')
            ->setPhone(null)
            ->setDefaultAddress(null)
            ->setAddressBuilding(null)
            ->setAddressExtra(null)
            ->setPostalCode(null)
            ->setCity(null)
            ->setAvatarPath(null)
            ->setGoogleId(null)
            ->setGoogleAvatarUrl(null)
            ->setAppleId(null)
            ->setIsVerified(false)
            ->setAccountClosedAt(new \DateTimeImmutable());

        $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));
        $this->entityManager->flush();

        return 'anonymized';
    }

    /**
     * @return CustomerConversation[]
     */
    private function findConversationsFor(User $user): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(CustomerConversation::class, 'c');

        $conditions = [$queryBuilder->expr()->eq('c.customerEmail', ':email')];
        $queryBuilder->setParameter('email', mb_strtolower((string) $user->getEmail()));

        if (null !== $user->getId()) {
            $conditions[] = $queryBuilder->expr()->eq('c.customerAccount', ':user');
            $queryBuilder->setParameter('user', $user);
        }

        return $queryBuilder
            ->where(call_user_func_array([$queryBuilder->expr(), 'orX'], $conditions))
            ->getQuery()
            ->getResult();
    }

    private function countRetainedOrders(User $user): int
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT o.id)')
            ->from(Order::class, 'o');

        $conditions = [$queryBuilder->expr()->eq('o.customerEmail', ':email')];
        $queryBuilder->setParameter('email', mb_strtolower((string) $user->getEmail()));

        if (null !== $user->getId()) {
            $conditions[] = $queryBuilder->expr()->eq('o.customerAccount', ':user');
            $queryBuilder->setParameter('user', $user);
        }

        $queryBuilder
            ->where(call_user_func_array([$queryBuilder->expr(), 'orX'], $conditions))
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

    /**
     * @return Order[]
     */
    private function findOrdersFor(User $user): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o');

        $conditions = [$queryBuilder->expr()->eq('o.customerEmail', ':email')];
        $queryBuilder->setParameter('email', mb_strtolower((string) $user->getEmail()));

        if (null !== $user->getId()) {
            $conditions[] = $queryBuilder->expr()->eq('o.customerAccount', ':user');
            $queryBuilder->setParameter('user', $user);
        }

        return $queryBuilder
            ->where(call_user_func_array([$queryBuilder->expr(), 'orX'], $conditions))
            ->getQuery()
            ->getResult();
    }

    private function isRetainedOrder(Order $order): bool
    {
        return \in_array($order->getPaymentStatus(), [
            Order::PAYMENT_STATUS_AUTHORIZED,
            Order::PAYMENT_STATUS_PAID,
            Order::PAYMENT_STATUS_REFUNDED,
        ], true) || \in_array($order->getStatus(), [
            Order::STATUS_PAID,
            Order::STATUS_PROCESSING,
            Order::STATUS_SHIPPED,
            Order::STATUS_CLOSED,
            Order::STATUS_REFUNDED,
        ], true);
    }

    private function buildAnonymizedEmail(User $user): string
    {
        return sprintf(
            'compte-ferme+%d+%s@soundofmemories.invalid',
            $user->getId() ?? 0,
            bin2hex(random_bytes(3))
        );
    }

    private function removeAvatar(User $user): void
    {
        $avatarPath = $user->getAvatarPath();

        if (null === $avatarPath || '' === $avatarPath) {
            return;
        }

        $absolutePath = rtrim($this->userAvatarsDir, '/').'/'.basename($avatarPath);

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }
}
