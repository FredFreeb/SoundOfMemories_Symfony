<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Service\OrderWorkflowManager;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\HttpFoundation\Response;

#[AdminRoute(path: '/commandes', name: 'commandes')]
// Fred note: Les commandes sont administrables surtout pour le suivi, pas pour les créer a la main.
final class OrderCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setDefaultRowAction(Action::EDIT)
            ->setSearchFields(['customerName', 'customerEmail', 'customerPhone', 'city', 'status', 'paymentStatus', 'deliveryStatus', 'paymentReference', 'shippingCarrier', 'shippingMethodLabel', 'trackingNumber'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('status')
            ->add('paymentStatus')
            ->add('deliveryStatus')
            ->add('shippingCountryCode')
            ->add('paymentProvider')
            ->add('shippingCarrier')
            ->add('createdAt');
    }

    public function configureActions(Actions $actions): Actions
    {
        $prepareAction = Action::new('markPreparing', 'Préparer', 'fas fa-box-open')
            ->linkToCrudAction('markPreparing')
            ->displayIf(fn (Order $order): bool => $this->canPrepare($order));

        $shipAction = Action::new('markShipped', 'Expédier', 'fas fa-truck-fast')
            ->linkToCrudAction('markShipped')
            ->displayIf(fn (Order $order): bool => $this->canShip($order));

        $receiveAction = Action::new('markReceived', 'Clôturer', 'fas fa-circle-check')
            ->linkToCrudAction('markReceived')
            ->displayIf(fn (Order $order): bool => $this->canClose($order));

        $issueAction = Action::new('markIssue', 'Incident', 'fas fa-triangle-exclamation')
            ->linkToCrudAction('markIssue')
            ->displayIf(fn (Order $order): bool => $this->canFlagIssue($order));

        return $actions
            ->add(Crud::PAGE_INDEX, $prepareAction)
            ->add(Crud::PAGE_INDEX, $shipAction)
            ->add(Crud::PAGE_INDEX, $receiveAction)
            ->add(Crud::PAGE_INDEX, $issueAction)
            ->add(Crud::PAGE_DETAIL, $prepareAction)
            ->add(Crud::PAGE_DETAIL, $shipAction)
            ->add(Crud::PAGE_DETAIL, $receiveAction)
            ->add(Crud::PAGE_DETAIL, $issueAction)
            ->update(Crud::PAGE_INDEX, Action::EDIT, static fn (Action $action): Action => $action
                ->setLabel('Gérer')
                ->setIcon('fas fa-box-open'))
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex()->hideOnDetail();

        if (Crud::PAGE_INDEX !== $pageName) {
            if (Crud::PAGE_EDIT === $pageName) {
                yield ChoiceField::new('status', 'Statut de commande')
                    ->setChoices(Order::getStatusChoices())
                    ->setHelp('Le back-office garde la cohérence métier : une commande non payée ne peut pas partir en expédition.');
            } else {
                yield TextField::new('statusLabel', 'Statut')
                    ->formatValue(fn ($value, Order $order) => $this->renderBadge($order->getStatus(), [
                        Order::STATUS_PENDING => 'warning',
                        Order::STATUS_PAID => 'success',
                        Order::STATUS_PROCESSING => 'info',
                        Order::STATUS_SHIPPED => 'primary',
                        Order::STATUS_CLOSED => 'success',
                        Order::STATUS_CANCELLED => 'danger',
                        Order::STATUS_REFUNDED => 'dark',
                    ], $order->getStatusLabel()))
                    ->renderAsHtml();
            }
        }

        if (Crud::PAGE_INDEX === $pageName) {
            yield TextField::new('referenceLabel', 'Commande');
            yield TextField::new('customerName', 'Fan')
                ->formatValue(function ($value, Order $order): string {
                    return sprintf(
                        '<strong>%s</strong><br><small>%s</small>',
                        htmlspecialchars($order->getCustomerName(), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($order->getCustomerEmail(), ENT_QUOTES, 'UTF-8'),
                    );
                })
                ->renderAsHtml();
            yield TextField::new('itemsSummary', 'Articles');
            yield TextField::new('shippingSummary', 'Destination');
            yield MoneyField::new('totalCents', 'Total')
                ->setCurrency('EUR')
                ->setStoredAsCents()
                ->hideOnForm();
            yield TextField::new('paymentStatusLabel', 'Paiement')
                ->formatValue(fn ($value, Order $order) => $this->renderBadge($order->getPaymentStatus(), [
                    Order::PAYMENT_STATUS_PENDING => 'warning',
                    Order::PAYMENT_STATUS_AUTHORIZED => 'info',
                    Order::PAYMENT_STATUS_PAID => 'success',
                    Order::PAYMENT_STATUS_CANCELLED => 'danger',
                    Order::PAYMENT_STATUS_FAILED => 'danger',
                    Order::PAYMENT_STATUS_REFUNDED => 'dark',
                ], $order->getPaymentStatusLabel()))
                ->renderAsHtml();
            yield TextField::new('deliveryStatusLabel', 'Livraison')
                ->formatValue(fn ($value, Order $order) => $this->renderBadge($order->getDeliveryStatus(), [
                    Order::DELIVERY_STATUS_PENDING => 'secondary',
                    Order::DELIVERY_STATUS_PREPARING => 'info',
                    Order::DELIVERY_STATUS_LABEL_CREATED => 'info',
                    Order::DELIVERY_STATUS_IN_TRANSIT => 'primary',
                    Order::DELIVERY_STATUS_RECEIVED => 'success',
                    Order::DELIVERY_STATUS_ISSUE => 'danger',
                ], $order->getDeliveryStatusLabel()))
                ->renderAsHtml();
            yield TextField::new('statusLabel', 'Statut')
                ->formatValue(fn ($value, Order $order) => $this->renderBadge($order->getStatus(), [
                    Order::STATUS_PENDING => 'warning',
                    Order::STATUS_PAID => 'success',
                    Order::STATUS_PROCESSING => 'info',
                    Order::STATUS_SHIPPED => 'primary',
                    Order::STATUS_CLOSED => 'success',
                    Order::STATUS_CANCELLED => 'danger',
                    Order::STATUS_REFUNDED => 'dark',
                ], $order->getStatusLabel()))
                ->renderAsHtml();
            yield DateTimeField::new('createdAt', 'Créée le')
                ->hideOnForm();

            return;
        }

        yield FormField::addPanel('Fan & référence')
            ->hideOnIndex();
        yield TextField::new('customerName', 'Fan');
        yield TextField::new('customerEmail', 'Email');
        yield TextField::new('referenceLabel', 'Référence')
            ->hideOnIndex();
        yield TextField::new('customerAccount.fullName', 'Compte fan')
            ->hideOnIndex();
        yield TextField::new('customerPhone', 'Telephone')
            ->hideOnIndex();
        yield FormField::addPanel('Adresse & livraison')
            ->hideOnIndex();
        yield TextField::new('shippingAddress', 'Adresse')
            ->hideOnIndex();
        yield TextField::new('shippingCountryLabel', 'Pays')
            ->hideOnIndex();
        yield TextField::new('postalCode', 'Code postal')
            ->hideOnIndex();
        yield TextField::new('city', 'Ville')
            ->hideOnIndex();
        yield TextField::new('shippingMethodLabel', 'Mode de livraison')
            ->hideOnIndex();
        yield TextareaField::new('note', 'Complément / note')
            ->hideOnIndex();
        yield MoneyField::new('shippingRateCents', 'Port')
            ->setCurrency('EUR')
            ->setStoredAsCents()
            ->hideOnForm()
            ->hideOnIndex();
        yield MoneyField::new('totalCents', 'Total')
            ->setCurrency('EUR')
            ->setStoredAsCents()
            ->hideOnForm();

        yield FormField::addPanel('Paiement & suivi')
            ->hideOnIndex();
        if (Crud::PAGE_EDIT === $pageName) {
            yield ChoiceField::new('paymentStatus', 'Paiement')
                ->setChoices(Order::getPaymentStatusChoices())
                ->setHelp('Si le paiement passe en annulé ou échoué, la commande est automatiquement bloquée côté expédition.');
            yield ChoiceField::new('deliveryStatus', 'Livraison')
                ->setChoices(Order::getDeliveryStatusChoices())
                ->setHelp('Quand le colis est marqué reçu, la commande passe automatiquement en "Clôturée".');
        } else {
            yield TextField::new('paymentStatusLabel', 'Paiement')
                ->formatValue(fn ($value, Order $order) => $this->renderBadge($order->getPaymentStatus(), [
                    Order::PAYMENT_STATUS_PENDING => 'warning',
                    Order::PAYMENT_STATUS_AUTHORIZED => 'info',
                    Order::PAYMENT_STATUS_PAID => 'success',
                    Order::PAYMENT_STATUS_CANCELLED => 'danger',
                    Order::PAYMENT_STATUS_FAILED => 'danger',
                    Order::PAYMENT_STATUS_REFUNDED => 'dark',
                ], $order->getPaymentStatusLabel()))
                ->renderAsHtml();
            yield TextField::new('deliveryStatusLabel', 'Livraison')
                ->formatValue(fn ($value, Order $order) => $this->renderBadge($order->getDeliveryStatus(), [
                    Order::DELIVERY_STATUS_PENDING => 'secondary',
                    Order::DELIVERY_STATUS_PREPARING => 'info',
                    Order::DELIVERY_STATUS_LABEL_CREATED => 'info',
                    Order::DELIVERY_STATUS_IN_TRANSIT => 'primary',
                    Order::DELIVERY_STATUS_RECEIVED => 'success',
                    Order::DELIVERY_STATUS_ISSUE => 'danger',
                ], $order->getDeliveryStatusLabel()))
                ->renderAsHtml();
        }

        yield TextField::new('paymentProvider', 'Prestataire')
            ->hideOnIndex();
        yield TextField::new('paymentReference', 'Reference')
            ->hideOnIndex();
        yield TextField::new('shippingProvider', 'Agrégateur')
            ->hideOnIndex();
        yield TextField::new('shippingCarrier', 'Transporteur')
            ->hideOnIndex();
        yield TextField::new('trackingNumber', 'Numéro de suivi')
            ->hideOnIndex();
        yield UrlField::new('trackingUrl', 'Lien de suivi')
            ->hideOnIndex();
        yield TextField::new('molliePaymentId', 'Paiement Mollie')
            ->hideOnIndex();
        yield TextField::new('stripeCheckoutSessionId', 'Session Stripe')
            ->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Cree le')
            ->hideOnForm();
        yield DateTimeField::new('paidAt', 'Paye le')
            ->hideOnForm()
            ->hideOnIndex();
        yield CollectionField::new('items', 'Lignes')
            ->onlyOnDetail();
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        \assert($entityInstance instanceof Order);

        foreach ($this->getWorkflowManager()->synchronize($entityInstance) as $message) {
            $this->addFlash('info', $message);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function renderBadge(?string $value, array $classes, ?string $label = null): string
    {
        // Fred note: Ce helper transforme un statut texte en badge colore lisible dans la liste admin.
        $label = $label ?? ($value !== null && $value !== '' ? $value : 'non renseigné');
        $class = $classes[$value] ?? 'secondary';

        return sprintf(
            '<span class="badge badge-%s">%s</span>',
            $class,
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
        );
    }

    public function new(AdminContext $context)
    {
        throw $this->createAccessDeniedException('Les commandes ne se creent pas manuellement dans le back-office.');
    }

    public function delete(AdminContext $context)
    {
        throw $this->createAccessDeniedException('La suppression de commandes est desactivee.');
    }

    public function batchDelete(AdminContext $context, BatchActionDto $batchActionDto): \Symfony\Component\HttpFoundation\Response
    {
        throw $this->createAccessDeniedException('La suppression de commandes est desactivee.');
    }

    public function markPreparing(AdminContext $context, EntityManagerInterface $entityManager): Response
    {
        return $this->applyQuickAction(
            $context,
            $entityManager,
            static function (Order $order): void {
                $order
                    ->setStatus(Order::STATUS_PROCESSING)
                    ->setDeliveryStatus(Order::DELIVERY_STATUS_PREPARING);
            },
            'Commande passée en préparation.'
        );
    }

    public function markShipped(AdminContext $context, EntityManagerInterface $entityManager): Response
    {
        return $this->applyQuickAction(
            $context,
            $entityManager,
            static function (Order $order): void {
                $order
                    ->setStatus(Order::STATUS_SHIPPED)
                    ->setDeliveryStatus(Order::DELIVERY_STATUS_IN_TRANSIT);
            },
            'Commande marquée comme expédiée.'
        );
    }

    public function markReceived(AdminContext $context, EntityManagerInterface $entityManager): Response
    {
        return $this->applyQuickAction(
            $context,
            $entityManager,
            static function (Order $order): void {
                $order
                    ->setStatus(Order::STATUS_CLOSED)
                    ->setDeliveryStatus(Order::DELIVERY_STATUS_RECEIVED);
            },
            'Commande clôturée.'
        );
    }

    public function markIssue(AdminContext $context, EntityManagerInterface $entityManager): Response
    {
        return $this->applyQuickAction(
            $context,
            $entityManager,
            static function (Order $order): void {
                $order->setDeliveryStatus(Order::DELIVERY_STATUS_ISSUE);
            },
            'Incident de livraison signalé.'
        );
    }

    private function applyQuickAction(
        AdminContext $context,
        EntityManagerInterface $entityManager,
        callable $callback,
        string $successMessage,
    ): Response {
        $entity = $context->getEntity()->getInstance();

        if (!$entity instanceof Order) {
            $this->addFlash('danger', 'Commande introuvable.');

            return $this->redirectToIndex();
        }

        if (!$this->isPaymentValidated($entity)) {
            $this->addFlash('warning', 'Cette commande ne peut pas avancer tant que le paiement n’est pas validé.');

            return $this->redirectBack($context);
        }

        $callback($entity);

        foreach ($this->getWorkflowManager()->synchronize($entity) as $message) {
            $this->addFlash('info', $message);
        }

        $entityManager->flush();
        $this->addFlash('success', $successMessage);

        return $this->redirectBack($context);
    }

    private function redirectBack(AdminContext $context): Response
    {
        $referer = $context->getRequest()->headers->get('referer');

        return null !== $referer && '' !== $referer
            ? $this->redirect($referer)
            : $this->redirectToIndex();
    }

    private function redirectToIndex(): Response
    {
        return $this->redirect(
            (clone $this->getAdminUrlGenerator())
                ->unsetAll()
                ->setController(self::class)
                ->setAction(Action::INDEX)
                ->generateUrl()
        );
    }

    /**
     * EasyAdmin instantiates CRUD controllers in a specific way; keeping service
     * lookups lazy here is more robust than constructor injection for this repo.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getWorkflowManager(): OrderWorkflowManager
    {
        /** @var OrderWorkflowManager $workflowManager */
        $workflowManager = $this->container->get(OrderWorkflowManager::class);

        return $workflowManager;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getAdminUrlGenerator(): AdminUrlGeneratorInterface
    {
        /** @var AdminUrlGeneratorInterface $adminUrlGenerator */
        $adminUrlGenerator = $this->container->get(AdminUrlGeneratorInterface::class);

        return $adminUrlGenerator;
    }

    private function isPaymentValidated(Order $order): bool
    {
        return \in_array($order->getPaymentStatus(), [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_AUTHORIZED], true);
    }

    private function canPrepare(Order $order): bool
    {
        return $this->isPaymentValidated($order)
            && !\in_array($order->getStatus(), [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED, Order::STATUS_CLOSED], true)
            && \in_array($order->getDeliveryStatus(), [Order::DELIVERY_STATUS_PENDING, Order::DELIVERY_STATUS_ISSUE], true);
    }

    private function canShip(Order $order): bool
    {
        return $this->isPaymentValidated($order)
            && !\in_array($order->getStatus(), [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED, Order::STATUS_CLOSED], true)
            && \in_array($order->getDeliveryStatus(), [Order::DELIVERY_STATUS_PREPARING, Order::DELIVERY_STATUS_LABEL_CREATED], true);
    }

    private function canClose(Order $order): bool
    {
        return $this->isPaymentValidated($order)
            && !\in_array($order->getStatus(), [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED, Order::STATUS_CLOSED], true)
            && \in_array($order->getDeliveryStatus(), [Order::DELIVERY_STATUS_IN_TRANSIT, Order::DELIVERY_STATUS_ISSUE], true);
    }

    private function canFlagIssue(Order $order): bool
    {
        return $this->isPaymentValidated($order)
            && !\in_array($order->getStatus(), [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED, Order::STATUS_CLOSED], true)
            && Order::DELIVERY_STATUS_ISSUE !== $order->getDeliveryStatus();
    }
}
