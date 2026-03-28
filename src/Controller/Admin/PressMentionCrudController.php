<?php

namespace App\Controller\Admin;

use App\Entity\PressMention;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
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
            ->setEntityLabelInSingular('Avis / Presse')
            ->setEntityLabelInPlural('Avis / Presse')
            ->setSearchFields(['authorName', 'sourceLabel', 'quotePrimary', 'quoteSecondary', 'linkLabel'])
            ->setDefaultSort(['position' => 'ASC']);
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
        yield TextField::new('authorName', 'Nom / auteur');
        yield TextField::new('sourceLabel', 'Source')->hideOnIndex();
        yield TextareaField::new('quotePrimary', 'Citation principale');
        yield TextareaField::new('quoteSecondary', 'Citation secondaire')->hideOnIndex();
        yield TextField::new('linkLabel', 'Texte du lien')->hideOnIndex();
        yield TextField::new('linkUrl', 'URL du lien')->hideOnIndex();
        yield ImageField::new('photo', 'Photo')
            ->setBasePath('uploads/press')
            ->setUploadDir('public/uploads/press')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setRequired(false)
            ->hideOnIndex();
        yield ImageField::new('photo', 'Photo')
            ->setBasePath('uploads/press')
            ->onlyOnIndex();
        yield IntegerField::new('position', 'Position');
        yield BooleanField::new('isPublished', 'Publie');
    }
}
