<?php

namespace App\Controller\Admin;

use App\Entity\CustomerConversationMessage;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

// Fred note: Ce mini CRUD me sert seulement pour embarquer les messages dans une conversation client.
final class CustomerConversationMessageCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CustomerConversationMessage::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield ChoiceField::new('authorType', 'Auteur')
            ->setChoices([
                'Client' => 'client',
                'Admin' => 'admin',
            ]);
        yield TextField::new('authorName', 'Nom');
        yield TextareaField::new('body', 'Message')
            ->setNumOfRows(6);
        yield DateTimeField::new('createdAt', 'Date')
            ->hideOnForm();
    }
}
