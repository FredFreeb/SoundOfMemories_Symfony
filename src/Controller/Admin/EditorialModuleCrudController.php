<?php

namespace App\Controller\Admin;

use App\Entity\EditorialModule;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

#[AdminRoute(path: '/modules-editoriaux', name: 'modules_editoriaux')]
final class EditorialModuleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return EditorialModule::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Module éditorial')
            ->setEntityLabelInPlural('Modules éditoriaux')
            ->setDefaultRowAction(Action::EDIT)
            ->setSearchFields(['pageKey', 'sectionKey', 'title', 'eyebrow', 'items.title', 'items.subtitle'])
            ->setDefaultSort(['pageKey' => 'ASC', 'position' => 'ASC', 'createdAt' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, static fn (Action $action): Action => $action
                ->setLabel('Gérer')
                ->setIcon('fas fa-layer-group'));
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('pageKey')
            ->add('moduleType')
            ->add('backgroundSlot')
            ->add('isPublished');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();

        if (Crud::PAGE_INDEX === $pageName) {
            yield TextField::new('title', 'Module')
                ->formatValue(function ($value, EditorialModule $module): string {
                    return sprintf(
                        '<strong>%s</strong><br><small>%s · %s</small>',
                        htmlspecialchars($module->getTitle(), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($module->getPageKey(), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($module->getSectionKey(), ENT_QUOTES, 'UTF-8'),
                    );
                })
                ->renderAsHtml();
            yield ChoiceField::new('moduleType', 'Type')
                ->setChoices(array_flip(EditorialModule::getModuleTypeChoices()));
            yield ChoiceField::new('backgroundSlot', 'Fond')
                ->setChoices(array_flip(EditorialModule::getBackgroundChoices()));
            yield IntegerField::new('position', 'Ordre');
            yield BooleanField::new('isPublished', 'Publié')
                ->renderAsSwitch(false);

            return;
        }

        yield ChoiceField::new('pageKey', 'Page')
            ->setChoices(EditorialModule::getPageChoices());
        yield TextField::new('sectionKey', 'Clé de section')
            ->setHelp('Exemples : hero, story, lineup, discography.');
        yield ChoiceField::new('moduleType', 'Type de module')
            ->setChoices(EditorialModule::getModuleTypeChoices());
        yield IntegerField::new('position', 'Ordre')
            ->setFormTypeOption('empty_data', '0');
        yield BooleanField::new('isPublished', 'Publié');

        yield TextField::new('eyebrow', 'Eyebrow')
            ->setRequired(false);
        yield TextField::new('title', 'Titre');
        yield TextareaField::new('leadText', 'Lead / intro')
            ->setRequired(false)
            ->hideOnIndex();
        yield TextareaField::new('bodyText', 'Corps du texte')
            ->setRequired(false)
            ->hideOnIndex()
            ->setHelp('Pour les blocs narratifs, séparez les paragraphes par une ligne vide.');

        yield ImageField::new('imagePath', 'Image principale')
            ->setBasePath('uploads/editorial')
            ->setUploadDir('public/uploads/editorial')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setRequired(false)
            ->hideOnIndex();
        yield ImageField::new('backgroundImagePath', 'Image de fond')
            ->setBasePath('uploads/editorial')
            ->setUploadDir('public/uploads/editorial')
            ->setUploadedFileNamePattern('[slug]-bg-[timestamp].[extension]')
            ->setRequired(false)
            ->hideOnIndex();

        yield TextField::new('metaPrimary', 'Meta 1')
            ->setRequired(false)
            ->hideOnIndex();
        yield TextField::new('metaSecondary', 'Meta 2')
            ->setRequired(false)
            ->hideOnIndex();
        yield TextField::new('metaTertiary', 'Meta 3')
            ->setRequired(false)
            ->hideOnIndex();

        yield ChoiceField::new('accentTone', 'Accent')
            ->setChoices(EditorialModule::getToneChoices())
            ->hideOnIndex();
        yield ChoiceField::new('layoutPreset', 'Preset de layout')
            ->setChoices(EditorialModule::getLayoutChoices())
            ->hideOnIndex();
        yield ChoiceField::new('backgroundSlot', 'Fond de section')
            ->setChoices(EditorialModule::getBackgroundChoices())
            ->hideOnIndex();

        yield TextField::new('ctaLabel', 'Libellé CTA')
            ->setRequired(false)
            ->hideOnIndex();
        yield UrlField::new('ctaUrl', 'URL CTA')
            ->setRequired(false)
            ->hideOnIndex();

        yield CollectionField::new('items', 'Éléments du module')
            ->useEntryCrudForm(EditorialModuleItemCrudController::class)
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->setEntryIsComplex()
            ->hideOnIndex();

        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')
            ->hideOnForm();
    }
}
