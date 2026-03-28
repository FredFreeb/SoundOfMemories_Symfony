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
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

#[AdminRoute(path: '/commandes', name: 'commandes')]
// Fred note: Les commandes sont administrables surtout pour le suivi, pas pour les créer a la main.
final class OrderCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly OrderWorkflowManager $workflowManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commande')
            ->setEntityLabelInPlural('Commandes')
            ->setSearchFields(['customerName', 'customerEmail', 'customerPhone', 'city', 'status', 'paymentStatus', 'deliveryStatus', 'paymentReference', 'shippingCarrier', 'shippingMethodLabel', 'trackingNumber'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('status')
            ->add('paymentStatus')
            ->add('deliveryStatus')
            ->add('createdAt');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex()->hideOnDetail();

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

        yield TextField::new('customerName', 'Client');
        yield TextField::new('customerEmail', 'Email');
        yield TextField::new('customerAccount.fullName', 'Compte client')
            ->hideOnIndex();
        yield TextField::new('customerPhone', 'Telephone')
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
        yield MoneyField::new('shippingRateCents', 'Port')
            ->setCurrency('EUR')
            ->setStoredAsCents()
            ->hideOnForm()
            ->hideOnIndex();
        yield MoneyField::new('totalCents', 'Total')
            ->setCurrency('EUR')
            ->setStoredAsCents()
            ->hideOnForm();

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

        foreach ($this->workflowManager->synchronize($entityInstance) as $message) {
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
}
