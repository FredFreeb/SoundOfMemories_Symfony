<?php

namespace App\Controller\Admin;

use App\Entity\SiteSettings;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[AdminRoute(path: '/identite-visuelle', name: 'identite_visuelle')]
final class SiteSettingsCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return SiteSettings::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Paramètre visuel')
            ->setEntityLabelInPlural('Paramètres visuels')
            ->setHelp(Crud::PAGE_EDIT, 'Pilotez ici le logo, les fonds et les visuels principaux de la fan base.')
            ->setHelp(Crud::PAGE_NEW, 'Créez une variante visuelle active pour préparer une nouvelle saison ou une nouvelle tournée.')
            ->setDefaultSort(['id' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->disable(Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield TextField::new('presetName', 'Nom de la variante');
        yield TextField::new('presetKey', 'Cle saisonniere')
            ->setHelp('Exemple: default, tournee-ete, black-album.')
            ->setColumns(6)
            ->hideOnIndex();
        yield BooleanField::new('isActive', 'Variante active')
            ->setColumns(6);
        yield TextField::new('siteName', 'Nom du site')
            ->setColumns(6);
        yield TextareaField::new('tagline', 'Texte de marque')
            ->setColumns(6)
            ->hideOnIndex();
        yield FormField::addPanel('Navigation et logo');
        yield ImageField::new('headerLogo', 'Logo')
            ->setBasePath('uploads/site')
            ->setUploadDir('public/uploads/site')
            ->setUploadedFileNamePattern('logo-[timestamp].[extension]')
            ->setRequired(false)
            ->setColumns(6);
        yield FormField::addPanel('Page d accueil');
        yield TextField::new('homeHeroTitle', 'Titre hero')
            ->setColumns(6)
            ->hideOnIndex();
        yield TextareaField::new('homeHeroText', 'Texte hero')
            ->setColumns(6)
            ->hideOnIndex();
        yield ImageField::new('homeHeroBackground', 'Fond hero')
            ->setBasePath('uploads/site')
            ->setUploadDir('public/uploads/site')
            ->setUploadedFileNamePattern('home-bg-[timestamp].[extension]')
            ->setRequired(false)
            ->setColumns(6)
            ->hideOnIndex();
        yield ImageField::new('homeHeroVisual', 'Visuel principal')
            ->setBasePath('uploads/site')
            ->setUploadDir('public/uploads/site')
            ->setUploadedFileNamePattern('home-visual-[timestamp].[extension]')
            ->setRequired(false)
            ->setColumns(6)
            ->hideOnIndex();
        yield FormField::addPanel('Container type 2 · Présentation');
        yield ChoiceField::new('homeIntroStylePreset', 'Style du container')
            ->setChoices(SiteSettings::getSectionStyleChoices())
            ->setHelp('Utilisé sur le bloc de présentation du groupe et de la fan base.')
            ->setColumns(12)
            ->hideOnIndex();
        yield FormField::addPanel('Presentation de la home');
        yield ImageField::new('homeOverviewImageOne', 'Visuel bloc 1')
            ->setBasePath('uploads/site')
            ->setUploadDir('public/uploads/site')
            ->setUploadedFileNamePattern('home-overview-1-[timestamp].[extension]')
            ->setRequired(false)
            ->setColumns(4)
            ->hideOnIndex();
        yield ImageField::new('homeOverviewImageTwo', 'Visuel bloc 2')
            ->setBasePath('uploads/site')
            ->setUploadDir('public/uploads/site')
            ->setUploadedFileNamePattern('home-overview-2-[timestamp].[extension]')
            ->setRequired(false)
            ->setColumns(4)
            ->hideOnIndex();
        yield ImageField::new('homeOverviewImageThree', 'Visuel bloc 3')
            ->setBasePath('uploads/site')
            ->setUploadDir('public/uploads/site')
            ->setUploadedFileNamePattern('home-overview-3-[timestamp].[extension]')
            ->setRequired(false)
            ->setColumns(4)
            ->hideOnIndex();
        yield FormField::addPanel('Container type 1 · Appel à l’action');
        yield ChoiceField::new('homeArchiveCtaStylePreset', 'Style du container')
            ->setChoices(SiteSettings::getSectionStyleChoices())
            ->setHelp('Utilisé sur le grand appel à l’action final en bas de l’accueil.')
            ->setColumns(12)
            ->hideOnIndex();
        yield FormField::addPanel('Le groupe · Containers');
        yield ChoiceField::new('aboutPrimaryStylePreset', 'Containers dominants')
            ->setChoices(SiteSettings::getSectionStyleChoices())
            ->setHelp('Utilisé pour les sections fortes de la page groupe.')
            ->setColumns(6)
            ->hideOnIndex();
        yield ChoiceField::new('aboutSecondaryStylePreset', 'Containers éditoriaux')
            ->setChoices(SiteSettings::getSectionStyleChoices())
            ->setHelp('Utilisé pour les sections éditoriales plus calmes.')
            ->setColumns(6)
            ->hideOnIndex();
        yield FormField::addPanel('Merchandising');
        yield ImageField::new('shopHeroBackground', 'Fond page boutique')
            ->setBasePath('uploads/site')
            ->setUploadDir('public/uploads/site')
            ->setUploadedFileNamePattern('shop-bg-[timestamp].[extension]')
            ->setRequired(false)
            ->setColumns(6)
            ->hideOnIndex();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')
            ->hideOnForm();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        \assert($entityInstance instanceof SiteSettings);

        $this->synchronizeActiveVariant($entityManager, $entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        \assert($entityInstance instanceof SiteSettings);

        $this->synchronizeActiveVariant($entityManager, $entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function synchronizeActiveVariant(EntityManagerInterface $entityManager, SiteSettings $current): void
    {
        if (!$current->isActive()) {
            return;
        }

        foreach ($entityManager->getRepository(SiteSettings::class)->findAll() as $settings) {
            if ($settings instanceof SiteSettings && $settings !== $current && $settings->isActive()) {
                $settings->setIsActive(false);
            }
        }
    }
}
