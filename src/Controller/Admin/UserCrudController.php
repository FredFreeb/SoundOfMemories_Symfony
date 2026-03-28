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
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AdminRoute(path: '/administrateurs', name: 'administrateurs')]
// Fred note: Ce CRUD gere les comptes qui ont acces au back-office.
final class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Administrateur')
            ->setEntityLabelInPlural('Administrateurs')
            ->setSearchFields(['fullName', 'email'])
            ->setDefaultSort(['fullName' => 'ASC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('email');
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        return $queryBuilder
            ->andWhere('entity.roles LIKE :adminRole')
            ->setParameter('adminRole', '%ROLE_ADMIN%');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        // Fred note: Le mot de passe n'apparait que dans les formulaires et reste optionnel en modification.
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield TextField::new('fullName', 'Nom');
        yield EmailField::new('email', 'Email');
        yield TextField::new('phone', 'Telephone')
            ->hideOnIndex();
        yield TextField::new('roleSummary', 'Acces')
            ->hideOnForm();
        yield TextField::new('loginMethodsSummary', 'Connexion')
            ->hideOnForm();
        yield BooleanField::new('isVerified', 'Email vérifié')
            ->hideOnForm();
        yield TextField::new('googleId', 'ID Google')
            ->hideOnForm()
            ->hideOnIndex();
        yield TextField::new('appleId', 'ID Apple')
            ->hideOnForm()
            ->hideOnIndex();
        yield DateTimeField::new('verifiedAt', 'Vérifié le')
            ->hideOnForm()
            ->hideOnIndex();
        yield Field::new('plainPassword', 'Mot de passe')
            ->setFormType(RepeatedType::class)
            ->setFormTypeOptions([
                'type' => PasswordType::class,
                'required' => Crud::PAGE_NEW === $pageName,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Confirmation',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Les deux mots de passe doivent être identiques.',
            ])
            ->setHelp(Crud::PAGE_NEW === $pageName ? 'Choisis le mot de passe du compte admin.' : 'Laisse vide pour conserver le mot de passe actuel.')
            ->onlyOnForms();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        \assert($entityInstance instanceof User);

        $entityInstance->setRoles(['ROLE_ADMIN']);
        $entityInstance->setIsVerified(true);
        $this->hashPasswordIfProvided($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        \assert($entityInstance instanceof User);

        $entityInstance->setRoles(['ROLE_ADMIN']);
        $entityInstance->setIsVerified(true);
        $this->hashPasswordIfProvided($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function delete(AdminContext $context)
    {
        $user = $context->getEntity()->getInstance();

        if ($user instanceof User && $this->isCurrentUser($user)) {
            throw $this->createAccessDeniedException('Tu ne peux pas supprimer le compte avec lequel tu es connecte.');
        }

        return parent::delete($context);
    }

    public function batchDelete(AdminContext $context, BatchActionDto $batchActionDto): Response
    {
        throw $this->createAccessDeniedException('La suppression multiple des administrateurs est desactivee.');
    }

    public function detail(AdminContext $context)
    {
        $user = $context->getEntity()->getInstance();

        if (!$user instanceof User || !$user->isAdmin()) {
            throw $this->createNotFoundException('Ce compte n\'est pas un administrateur.');
        }

        return parent::detail($context);
    }

    private function hashPasswordIfProvided(User $user): void
    {
        // Fred note: Si aucun nouveau mot de passe n'est saisi, on garde simplement l'ancien hash.
        $plainPassword = trim((string) $user->getPlainPassword());

        if ('' === $plainPassword) {
            $user->eraseCredentials();

            return;
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $user->eraseCredentials();
    }

    private function isCurrentUser(User $user): bool
    {
        // Fred note: Cette verification evite qu'un admin supprime son propre compte par erreur.
        $currentUser = $this->getUser();

        return $currentUser instanceof User && null !== $currentUser->getId() && $currentUser->getId() === $user->getId();
    }
}
