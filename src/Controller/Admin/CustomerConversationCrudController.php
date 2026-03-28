<?php

namespace App\Controller\Admin;

use App\Entity\CustomerConversation;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[AdminRoute(path: '/clients/questions', name: 'clients_questions')]
// Fred note: Cette vue isole les questions avant achat pour repondre vite sans melanger ca avec le SAV commande.
final class CustomerConversationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CustomerConversation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Question client')
            ->setEntityLabelInPlural('Q&A clients')
            ->setSearchFields(['subject', 'customerName', 'customerEmail', 'status'])
            ->setDefaultSort(['lastMessageAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('status')
            ->add('hasUnreadForAdmin');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield TextField::new('subject', 'Sujet');
        yield TextField::new('customerName', 'Client');
        yield EmailField::new('customerEmail', 'Email');
        if (Crud::PAGE_EDIT === $pageName || Crud::PAGE_NEW === $pageName) {
            yield ChoiceField::new('status', 'Statut')
                ->setChoices(CustomerConversation::getStatusChoices());
        } else {
            yield ChoiceField::new('status', 'Statut')
                ->setChoices(CustomerConversation::getStatusChoices())
                ->renderAsBadges(CustomerConversation::getStatusBadgeMap());
        }
        yield TextField::new('inboxLabel', 'Messages')
            ->hideOnForm()
            ->formatValue(static function ($value, CustomerConversation $conversation): string {
                if ($conversation->hasUnreadForAdmin()) {
                    return '<span class="admin-message-pill is-unread">Nouveau message</span>';
                }

                return '<span class="admin-message-pill is-read">Lu</span>';
            })
            ->renderAsHtml();
        yield DateTimeField::new('lastMessageAt', 'Dernier message')
            ->hideOnForm();
        yield AssociationField::new('customerAccount', 'Compte client')
            ->hideOnIndex();
        yield CollectionField::new('messages', 'Conversation')
            ->useEntryCrudForm(CustomerConversationMessageCrudController::class)
            ->addCssClass('admin-conversation-thread-field')
            ->allowAdd()
            ->allowDelete()
            ->setEntryIsComplex()
            ->onlyOnForms();
        yield CollectionField::new('messages', 'Conversation')
            ->onlyOnDetail();
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->andWhere('entity.type = :type')
            ->setParameter('type', 'pre_sale');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        \assert($entityInstance instanceof CustomerConversation);

        $entityInstance->setType('pre_sale');
        $this->refreshConversationMetadata($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        \assert($entityInstance instanceof CustomerConversation);

        $entityInstance->setType('pre_sale');
        $this->refreshConversationMetadata($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function refreshConversationMetadata(CustomerConversation $conversation): void
    {
        $messages = $conversation->getMessages();
        $lastMessage = $messages->isEmpty() ? null : $messages->last();

        if ($lastMessage !== false && null !== $lastMessage) {
            $conversation
                ->setLastMessageAt($lastMessage->getCreatedAt())
                ->setHasUnreadForAdmin('client' === $lastMessage->getAuthorType());
        }
    }
}
