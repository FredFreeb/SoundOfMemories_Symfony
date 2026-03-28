<?php

namespace App\Service;

use App\Entity\Order;

final class OrderWorkflowManager
{
    /**
     * @return list<string>
     */
    public function synchronize(Order $order): array
    {
        $messages = [];

        if (Order::LEGACY_STATUS_COMPLETED === $order->getStatus()) {
            $order->setStatus(Order::STATUS_CLOSED);
            $messages[] = 'L’ancien statut "Terminée" a été converti en "Clôturée".';
        }

        if (\in_array($order->getPaymentStatus(), [Order::PAYMENT_STATUS_CANCELLED, Order::PAYMENT_STATUS_FAILED], true)) {
            if (Order::STATUS_CANCELLED !== $order->getStatus()) {
                $order->setStatus(Order::STATUS_CANCELLED);
                $messages[] = 'Paiement annulé ou échoué : la commande a été replacée en "Annulée".';
            }

            if (Order::DELIVERY_STATUS_PENDING !== $order->getDeliveryStatus()) {
                $order->setDeliveryStatus(Order::DELIVERY_STATUS_PENDING);
                $messages[] = 'Impossible d’expédier une commande non payée : la livraison a été remise en attente.';
            }

            return array_values(array_unique($messages));
        }

        if (Order::PAYMENT_STATUS_REFUNDED === $order->getPaymentStatus()) {
            if (Order::STATUS_REFUNDED !== $order->getStatus()) {
                $order->setStatus(Order::STATUS_REFUNDED);
                $messages[] = 'Paiement remboursé : la commande a été marquée comme "Remboursée".';
            }

            return array_values(array_unique($messages));
        }

        if (Order::STATUS_REFUNDED === $order->getStatus() && Order::PAYMENT_STATUS_REFUNDED !== $order->getPaymentStatus()) {
            $order->setPaymentStatus(Order::PAYMENT_STATUS_REFUNDED);
            $messages[] = 'Le statut "Remboursée" a aussi appliqué le paiement "Remboursé".';
        }

        $paymentValidated = \in_array($order->getPaymentStatus(), [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_AUTHORIZED], true);
        $advancedOrderStatuses = [Order::STATUS_PROCESSING, Order::STATUS_SHIPPED, Order::STATUS_CLOSED];
        $advancedDeliveryStatuses = [
            Order::DELIVERY_STATUS_PREPARING,
            Order::DELIVERY_STATUS_LABEL_CREATED,
            Order::DELIVERY_STATUS_IN_TRANSIT,
            Order::DELIVERY_STATUS_RECEIVED,
        ];

        if (!$paymentValidated && \in_array($order->getStatus(), $advancedOrderStatuses, true)) {
            $order->setStatus(Order::STATUS_PENDING);
            $messages[] = 'Les statuts avancés d’expédition demandent un paiement validé : la commande est revenue en attente.';
        }

        if (!$paymentValidated && \in_array($order->getDeliveryStatus(), $advancedDeliveryStatuses, true)) {
            $order->setDeliveryStatus(Order::DELIVERY_STATUS_PENDING);
            $messages[] = 'La livraison a été remise en attente tant que le paiement n’est pas validé.';
        }

        if (Order::STATUS_CLOSED === $order->getStatus() && Order::DELIVERY_STATUS_RECEIVED !== $order->getDeliveryStatus()) {
            $order->setDeliveryStatus(Order::DELIVERY_STATUS_RECEIVED);
            $messages[] = 'Commande clôturée : la livraison a été marquée comme reçue.';
        }

        if (Order::DELIVERY_STATUS_RECEIVED === $order->getDeliveryStatus() && Order::STATUS_CLOSED !== $order->getStatus()) {
            $order->setStatus(Order::STATUS_CLOSED);
            $messages[] = 'Colis reçu : la commande a été clôturée automatiquement.';
        }

        if (Order::STATUS_SHIPPED === $order->getStatus()
            && !\in_array($order->getDeliveryStatus(), [Order::DELIVERY_STATUS_IN_TRANSIT, Order::DELIVERY_STATUS_RECEIVED], true)
        ) {
            $order->setDeliveryStatus(Order::DELIVERY_STATUS_IN_TRANSIT);
            $messages[] = 'Commande expédiée : la livraison est passée en transit.';
        }

        if (Order::DELIVERY_STATUS_IN_TRANSIT === $order->getDeliveryStatus() && Order::STATUS_SHIPPED !== $order->getStatus()) {
            $order->setStatus(Order::STATUS_SHIPPED);
            $messages[] = 'Livraison en transit : la commande a été marquée comme expédiée.';
        }

        if (Order::STATUS_PROCESSING === $order->getStatus() && Order::DELIVERY_STATUS_PENDING === $order->getDeliveryStatus()) {
            $order->setDeliveryStatus(Order::DELIVERY_STATUS_PREPARING);
            $messages[] = 'Commande en préparation : la livraison est passée en préparation colis.';
        }

        if (\in_array($order->getDeliveryStatus(), [Order::DELIVERY_STATUS_PREPARING, Order::DELIVERY_STATUS_LABEL_CREATED], true)
            && Order::STATUS_PROCESSING !== $order->getStatus()
        ) {
            $order->setStatus(Order::STATUS_PROCESSING);
            $messages[] = 'Préparation logistique en cours : la commande a été marquée "En préparation".';
        }

        if ($paymentValidated && Order::STATUS_PENDING === $order->getStatus()) {
            $order->setStatus(Order::STATUS_PAID);
            $messages[] = 'Paiement validé : la commande est prête à être préparée.';
        }

        return array_values(array_unique($messages));
    }
}
