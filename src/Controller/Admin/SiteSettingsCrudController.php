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
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

#[AdminRoute(path: '/identite-visuelle', name: 'identite_visuelle')]
final class SiteSettingsCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
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
            ->setDefaultRowAction(Action::EDIT)
            ->setHelp(Crud::PAGE_EDIT, 'Pilotez ici la bibliothèque visuelle du site en choisissant les images déjà rangées dans les bons dossiers.')
            ->setHelp(Crud::PAGE_NEW, 'Créez une variante visuelle active pour préparer une nouvelle saison ou une nouvelle tournée.')
            ->setDefaultSort(['id' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, static fn (Action $action): Action => $action
                ->setLabel('Gérer')
                ->setIcon('fas fa-sliders'))
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
        yield $this->buildSiteImageChoiceField('headerLogo', 'Logo', 'logo', 6);
        yield FormField::addPanel('Page d accueil');
        yield TextField::new('homeHeroTitle', 'Titre hero')
            ->setColumns(6)
            ->hideOnIndex();
        yield TextareaField::new('homeHeroText', 'Texte hero')
            ->setColumns(6)
            ->hideOnIndex();
        yield $this->buildSiteImageChoiceField('homeHeroBackground', 'Fond hero par défaut', 'hero/background', 6);
        yield $this->buildSiteImageChoiceField('homeHeroVisual', 'Visuel principal', 'hero/visual', 6);
        yield $this->buildSiteImageChoiceField('homeHeroSlideOne', 'Slide hero #1', 'hero/slides', 3);
        yield $this->buildSiteImageChoiceField('homeHeroSlideTwo', 'Slide hero #2', 'hero/slides', 3);
        yield $this->buildSiteImageChoiceField('homeHeroSlideThree', 'Slide hero #3', 'hero/slides', 3);
        yield $this->buildSiteImageChoiceField('homeHeroSlideFour', 'Slide hero #4', 'hero/slides', 3);
        yield FormField::addPanel('Presentation de la home');
        yield $this->buildSiteImageChoiceField('homeOverviewImageOne', 'Visuel bloc 1', 'overview', 4);
        yield $this->buildSiteImageChoiceField('homeOverviewImageTwo', 'Visuel bloc 2', 'overview', 4);
        yield $this->buildSiteImageChoiceField('homeOverviewImageThree', 'Visuel bloc 3', 'overview', 4);
        yield FormField::addPanel('Fonds de section');
        yield $this->buildSiteImageChoiceField('sectionBackgroundPrimary', 'Fond section #1', 'background/section', 4, 'Ce fond sera utilisé par les sections marquées `section-bg-1`.');
        yield $this->buildSiteImageChoiceField('sectionBackgroundSecondary', 'Fond section #2', 'background/section', 4, 'Ce fond sera utilisé par les sections marquées `section-bg-2`.');
        yield $this->buildSiteImageChoiceField('sectionBackgroundTertiary', 'Fond section #3', 'background/section', 4, 'Ce fond sera utilisé par les sections marquées `section-bg-3`.');
        yield FormField::addPanel('Merchandising');
        yield $this->buildSiteImageChoiceField('shopHeroBackground', 'Fond page boutique', 'background/shop', 6);
        yield FormField::addPanel('Plateformes du footer');
        yield TextField::new('soundcloudUrl', 'Lien SoundCloud')
            ->setColumns(6)
            ->hideOnIndex();
        yield TextField::new('spotifyUrl', 'Lien Spotify')
            ->setColumns(6)
            ->hideOnIndex();
        yield TextField::new('appleMusicUrl', 'Lien Apple Music')
            ->setColumns(6)
            ->hideOnIndex();
        yield TextField::new('youtubeMusicUrl', 'Lien YouTube Music')
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

    private function buildSiteImageChoiceField(string $property, string $label, string $relativeDirectory, int $columns = 6, ?string $help = null): ChoiceField
    {
        $directoryHelp = sprintf('Dossier : /public/uploads/site/%s', trim($relativeDirectory, '/'));

        return ChoiceField::new($property, $label)
            ->setChoices($this->listSiteImageChoices($relativeDirectory))
            ->setRequired(false)
            ->setColumns($columns)
            ->setFormTypeOption('placeholder', 'Aucune image')
            ->setFormTypeOption('choice_translation_domain', false)
            ->setHelp(trim(sprintf('%s %s', $help ?? '', $directoryHelp)))
            ->hideOnIndex();
    }

    /**
     * @return array<string, string>
     */
    private function listSiteImageChoices(string $relativeDirectory): array
    {
        $absoluteDirectory = sprintf('%s/public/uploads/site/%s', rtrim($this->projectDir, '/'), trim($relativeDirectory, '/'));
        if (!is_dir($absoluteDirectory)) {
            return [];
        }

        $entries = scandir($absoluteDirectory);
        if (false === $entries) {
            return [];
        }

        $choices = [];
        foreach ($entries as $entry) {
            if ('.' === $entry || '..' === $entry || str_starts_with($entry, '.')) {
                continue;
            }

            if (!is_file($absoluteDirectory . '/' . $entry)) {
                continue;
            }

            $choices[$this->humanizeImageLabel($entry)] = trim($relativeDirectory, '/') . '/' . $entry;
        }

        ksort($choices, \SORT_NATURAL | \SORT_FLAG_CASE);

        return $choices;
    }

    private function humanizeImageLabel(string $filename): string
    {
        $name = pathinfo($filename, \PATHINFO_FILENAME);
        $name = preg_replace('/[-_]+/', ' ', $name) ?? $name;

        return ucfirst(trim($name));
    }
}
