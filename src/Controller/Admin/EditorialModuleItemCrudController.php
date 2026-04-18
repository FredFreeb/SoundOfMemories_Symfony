<?php

namespace App\Controller\Admin;

use App\Entity\EditorialModuleItem;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

#[AdminRoute(path: '/modules-editoriaux/elements', name: 'modules_editoriaux_elements')]
final class EditorialModuleItemCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EditorialModuleItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Élément éditorial')
            ->setEntityLabelInPlural('Éléments éditoriaux')
            ->setDefaultRowAction(Action::EDIT)
            ->setSearchFields(['title', 'subtitle', 'eyebrow', 'metaPrimary', 'metaSecondary'])
            ->setDefaultSort(['position' => 'ASC', 'createdAt' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, static fn (Action $action): Action => $action
                ->setLabel('Gérer')
                ->setIcon('fas fa-grip'));
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('module')
            ->add('isPublished');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield AssociationField::new('module', 'Module')
            ->autocomplete()
            ->hideOnForm();
        yield TextField::new('eyebrow', 'Eyebrow')
            ->setRequired(false);
        yield TextField::new('title', 'Titre');
        yield TextField::new('subtitle', 'Sous-titre')
            ->setRequired(false)
            ->hideOnIndex();
        yield TextareaField::new('bodyText', 'Texte')
            ->setRequired(false)
            ->hideOnIndex();
        yield ImageField::new('imagePath', 'Image')
            ->setBasePath('uploads/editorial')
            ->setUploadDir('public/uploads/editorial')
            ->setUploadedFileNamePattern('[slug]-item-[timestamp].[extension]')
            ->setRequired(false)
            ->hideOnIndex();
        yield ImageField::new('imagePath', 'Visuel')
            ->setBasePath('uploads/editorial')
            ->onlyOnIndex();
        yield TextField::new('metaPrimary', 'Meta 1')
            ->setRequired(false)
            ->hideOnIndex();
        yield TextField::new('metaSecondary', 'Meta 2')
            ->setRequired(false)
            ->hideOnIndex();
        yield TextField::new('linkLabel', 'Texte du lien')
            ->setRequired(false)
            ->hideOnIndex();
        yield UrlField::new('linkUrl', 'URL du lien')
            ->setRequired(false)
            ->hideOnIndex();
        yield IntegerField::new('position', 'Ordre')
            ->setFormTypeOption('empty_data', '0');
        yield BooleanField::new('isPublished', 'Publié');
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm()->hideOnIndex();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')->hideOnForm()->hideOnIndex();
    }
}
