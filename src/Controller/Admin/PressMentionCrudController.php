<?php

namespace App\Controller\Admin;

use App\Entity\PressMention;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

#[AdminRoute(path: '/avis-et-presse', name: 'avis_presse')]
// Fred note: Ce CRUD me permet de gérer les retours externes, citations et photos sans toucher aux templates.
final class PressMentionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return PressMention::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article de presse')
            ->setEntityLabelInPlural('Revue de presse')
            ->setDefaultRowAction(Action::EDIT)
            ->setSearchFields(['authorName', 'sourceLabel', 'quotePrimary', 'quoteSecondary', 'linkLabel', 'linkUrl'])
            ->setDefaultSort(['position' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, static fn (Action $action): Action => $action
                ->setLabel('Gérer')
                ->setIcon('fas fa-newspaper'));
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('isPublished')
            ->add('position');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield TextField::new('authorName', 'Titre de l article')
            ->setHelp('Le titre principal affiche sur la carte presse.');
        yield TextField::new('sourceLabel', 'Magazine / media')
            ->setHelp('Exemple : Metallian, Rock Hard, webzine local.');
        yield TextareaField::new('quotePrimary', 'Chapô / extrait')
            ->setHelp('Petit resume ou phrase d accroche visible sur la carte.');
        yield TextareaField::new('quoteSecondary', 'Accroche secondaire')
            ->setHelp('Optionnel. Petite ligne supplementaire au-dessus du resume.')
            ->hideOnIndex();
        yield TextField::new('linkLabel', 'Texte du lien')
            ->setHelp('Optionnel. Exemple : Lire l article.')
            ->hideOnIndex();
        yield TextField::new('linkUrl', 'URL de l article')
            ->setHelp('Lien externe vers l article complet.')
            ->hideOnIndex();
        yield ImageField::new('photo', 'Visuel article')
            ->setBasePath('uploads/press')
            ->setUploadDir('public/uploads/press')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setRequired(false)
            ->hideOnIndex();
        yield ImageField::new('photo', 'Visuel')
            ->setBasePath('uploads/press')
            ->onlyOnIndex();
        yield IntegerField::new('position', 'Position');
        yield BooleanField::new('isPublished', 'Publie');
    }
}
