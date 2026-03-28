<?php

namespace App\Controller\Admin;

use App\Entity\Concert;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

#[AdminRoute(path: '/concerts', name: 'concerts')]
final class ConcertCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Concert::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Concert')
            ->setEntityLabelInPlural('Concerts')
            ->setSearchFields(['title', 'venue', 'city', 'country', 'details'])
            ->setDefaultSort(['concertAt' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield TextField::new('title', 'Intitulé');
        yield TextField::new('venue', 'Salle / festival');
        yield TextField::new('city', 'Ville');
        yield TextField::new('country', 'Pays');
        yield DateTimeField::new('concertAt', 'Date et heure');
        yield ChoiceField::new('status', 'Statut')
            ->setChoices(Concert::getStatusChoices());
        yield UrlField::new('ticketUrl', 'Lien billetterie')
            ->hideOnIndex()
            ->setRequired(false);
        yield TextareaField::new('details', 'Infos pratiques')
            ->hideOnIndex();
        yield BooleanField::new('isHighlighted', 'Mettre en avant');
        yield BooleanField::new('isPublished', 'Publié');
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')->hideOnForm();
    }
}
