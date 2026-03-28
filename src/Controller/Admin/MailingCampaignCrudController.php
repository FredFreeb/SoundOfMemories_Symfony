<?php

namespace App\Controller\Admin;

use App\Entity\MailingCampaign;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[AdminRoute(path: '/mailings', name: 'mailings')]
// Fred note: Je centralise ici mes campagnes de communication pour les lancements, promotions et fetes.
final class MailingCampaignCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return MailingCampaign::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Campagne mail')
            ->setEntityLabelInPlural('Mailings')
            ->setSearchFields(['title', 'subject', 'audienceLabel', 'status'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('status')
            ->add('scheduledAt')
            ->add('sentAt');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield TextField::new('title', 'Nom interne');
        yield TextField::new('subject', 'Objet du mail');
        yield TextField::new('previewText', 'Texte d apercu')
            ->hideOnIndex();
        yield TextField::new('audienceLabel', 'Audience');
        yield ChoiceField::new('status', 'Statut')
            ->setChoices([
                'Brouillon' => 'draft',
                'Programme' => 'scheduled',
                'Pret a envoyer' => 'ready',
                'Envoye' => 'sent',
            ]);
        yield DateTimeField::new('createdAt', 'Cree le')
            ->hideOnForm();
        yield DateTimeField::new('scheduledAt', 'Envoi prevu');
        yield DateTimeField::new('sentAt', 'Envoye le')
            ->hideOnIndex();
        yield TextareaField::new('content', 'Contenu')
            ->setNumOfRows(18)
            ->hideOnIndex();
    }
}
