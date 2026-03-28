<?php

namespace App\Controller\Admin;

use App\Entity\ProductGalleryImage;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[AdminRoute(path: '/galerie-produits', name: 'galerie_produits')]
// Fred note: Ce CRUD me permet de gérer les photos secondaires des produits sans melanger ca avec l image principale.
final class ProductGalleryImageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductGalleryImage::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Image galerie')
            ->setEntityLabelInPlural('Galerie produits')
            ->setSearchFields(['altText', 'imagePath', 'product.name'])
            ->setDefaultSort(['position' => 'ASC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('product')
            ->add('position');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield AssociationField::new('product', 'Produit')
            ->autocomplete()
            ->hideOnForm();
        yield ImageField::new('imagePath', 'Image')
            ->setBasePath('uploads/product-gallery')
            ->setUploadDir('public/uploads/product-gallery')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setRequired(true)
            ->hideOnIndex();
        yield ImageField::new('imagePath', 'Visuel')
            ->setBasePath('uploads/product-gallery')
            ->onlyOnIndex();
        yield TextField::new('altText', 'Texte alternatif')
            ->setHelp('Fred note: Je renseigne ici une vraie description courte de l image pour l accessibilite et le SEO.');
        yield IntegerField::new('position', 'Position')
            ->setRequired(false)
            ->setFormTypeOption('empty_data', '0')
            ->setHelp('Fred note: Je peux laisser ce champ vide si l ordre m importe peu, il retombera automatiquement a 0.');
    }
}
