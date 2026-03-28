<?php

namespace App\Controller\Admin;

use App\Entity\User;
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
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;

#[AdminRoute(path: '/clients', name: 'clients')]
// Fred note: Je separe la vue client de la vue admin pour que le back-office reste lisible et sans ambiguite.
final class CustomerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Client')
            ->setEntityLabelInPlural('Clients')
            ->setSearchFields(['fullName', 'email', 'phone', 'city', 'postalCode'])
            ->setDefaultSort(['fullName' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $sendResetLink = Action::new('sendResetLink', 'Envoyer un reset', 'fas fa-key')
            ->linkToRoute('admin_account_send_reset_link', static fn (User $user): array => ['id' => $user->getId()]);
        $applyDataLifecycle = Action::new('applyDataLifecycle', 'Traiter RGPD', 'fas fa-user-shield')
            ->linkToRoute('admin_customer_apply_data_lifecycle', static fn (User $user): array => ['id' => $user->getId()]);

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $sendResetLink)
            ->add(Crud::PAGE_INDEX, $applyDataLifecycle)
            ->add(Crud::PAGE_DETAIL, $sendResetLink)
            ->add(Crud::PAGE_DETAIL, $applyDataLifecycle)
            ->disable(Action::NEW, Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('email')
            ->add('city');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield TextField::new('fullName', 'Nom');
        yield EmailField::new('email', 'Email');
        yield TextField::new('phone', 'Telephone');
        yield TextField::new('city', 'Ville');
        yield TextField::new('accountStateLabel', 'Compte');
        yield BooleanField::new('marketingOptIn', 'Mailing actif');
        yield TextField::new('welcomeOfferStatusLabel', 'Bienvenue')
            ->hideOnForm();
        yield TextField::new('loginMethodsSummary', 'Connexion');
        yield BooleanField::new('isVerified', 'Email vérifié');
        yield TextField::new('postalCode', 'Code postal')
            ->hideOnIndex();
        yield TextField::new('defaultAddress', 'Adresse')
            ->hideOnIndex();
        yield DateTimeField::new('marketingConsentAt', 'Consentement le')
            ->hideOnIndex();
        yield DateTimeField::new('marketingRevokedAt', 'Retrait du consentement')
            ->hideOnIndex();
        yield DateTimeField::new('welcomeDiscountUsedAt', 'Bienvenue utilisée le')
            ->hideOnIndex();
        yield DateTimeField::new('accountClosedAt', 'Compte clôturé le')
            ->hideOnIndex();
        yield TextField::new('googleId', 'ID Google')
            ->onlyOnDetail();
        yield TextField::new('appleId', 'ID Apple')
            ->onlyOnDetail();
        yield DateTimeField::new('verifiedAt', 'Vérifié le')
            ->hideOnIndex();
        yield TextField::new('customerSummary', 'Resume')
            ->onlyOnIndex();
        yield AssociationField::new('orders', 'Commandes')
            ->onlyOnDetail();
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        // Fred note: Le champ roles est stocke en JSON texte, donc ce filtre suffit pour notre usage actuel.
        return $queryBuilder
            ->andWhere('entity.roles NOT LIKE :adminRole')
            ->setParameter('adminRole', '%ROLE_ADMIN%');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        throw $this->createAccessDeniedException('Les comptes clients se creent depuis le front ou le checkout.');
    }

    public function batchDelete(\EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        throw $this->createAccessDeniedException('La suppression multiple des clients est desactivee.');
    }
}
