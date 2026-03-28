<?php

namespace App\Controller\Admin;

use App\Entity\GalleryPhoto;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[AdminRoute(path: '/photos-galerie', name: 'photos_galerie')]
final class GalleryPhotoCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return GalleryPhoto::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Photo')
            ->setEntityLabelInPlural('Galerie photo')
            ->setSearchFields(['title', 'altText', 'caption', 'imagePath'])
            ->setDefaultSort(['position' => 'ASC', 'createdAt' => 'DESC']);
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
        yield ImageField::new('imagePath', 'Photo')
            ->setBasePath('uploads/gallery')
            ->setUploadDir('public/uploads/gallery')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setRequired(true)
            ->hideOnIndex();
        yield ImageField::new('imagePath', 'Visuel')
            ->setBasePath('uploads/gallery')
            ->onlyOnIndex();
        yield TextField::new('title', 'Titre')
            ->setRequired(false)
            ->setHelp('Optionnel. Sert pour le petit habillage éditorial de la galerie.');
        yield TextField::new('altText', 'Texte alternatif')
            ->setRequired(false)
            ->setHelp('Recommandé pour l’accessibilité et le référencement.');
        yield TextareaField::new('caption', 'Légende')
            ->hideOnIndex()
            ->setRequired(false);
        yield IntegerField::new('position', 'Position')
            ->setRequired(false)
            ->setFormTypeOption('empty_data', '0');
        yield BooleanField::new('isPublished', 'Publiée');
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Mise à jour le')->hideOnForm();
    }
}
