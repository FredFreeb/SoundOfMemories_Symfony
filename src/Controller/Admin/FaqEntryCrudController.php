<?php

namespace App\Controller\Admin;

use App\Entity\FaqEntry;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[AdminRoute(path: '/faq', name: 'faq')]
// Fred note: Ce CRUD permet de maintenir la FAQ sans retoucher les templates a la main.
final class FaqEntryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FaqEntry::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Entree FAQ')
            ->setEntityLabelInPlural('FAQ')
            ->setSearchFields(['question', 'answer'])
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
        yield TextField::new('question', 'Question');
        yield TextareaField::new('answer', 'Réponse');
        yield IntegerField::new('position', 'Position');
        yield BooleanField::new('isPublished', 'Publie');
    }
}
