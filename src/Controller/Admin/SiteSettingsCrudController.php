<?php

namespace App\Controller\Admin;

use App\Entity\SiteSettings;
use App\Repository\SiteSettingsRepository;
use App\Service\UploadedImageWebpConverter;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;

#[AdminRoute(path: '/identite-visuelle', name: 'identite_visuelle')]
final class SiteSettingsCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly AdminUrlGeneratorInterface $adminUrlGenerator,
        private readonly string $projectDir,
        private readonly SluggerInterface $slugger,
        private readonly UploadedImageWebpConverter $uploadedImageWebpConverter,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return SiteSettings::class;
    }

    public function index(AdminContext $context): RedirectResponse
    {
        $current = $this->siteSettingsRepository->findCurrent();

        $url = (clone $this->adminUrlGenerator)
            ->unsetAll()
            ->setDashboard(DashboardController::class)
            ->setController(self::class);

        if ($current instanceof SiteSettings && null !== $current->getId()) {
            $url->setAction(Action::EDIT)->setEntityId($current->getId());
        } else {
            $url->setAction(Action::NEW);
        }

        return $this->redirect($url->generateUrl());
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Direction visuelle')
            ->setEntityLabelInPlural('Direction visuelle')
            ->setDefaultRowAction(Action::EDIT)
            ->setHelp(Crud::PAGE_EDIT, 'Gérez ici le logo et les trois fonds de section. Les images ajoutées à la bibliothèque sont automatiquement converties en WebP.')
            ->setHelp(Crud::PAGE_NEW, 'Créez la configuration visuelle principale du site.')
            ->setDefaultSort(['id' => 'ASC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::DELETE, Action::BATCH_DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield TextField::new('siteName', 'Nom du site')
            ->setColumns(4);
        yield BooleanField::new('isActive', 'Configuration active')
            ->setColumns(4)
            ->setHelp('Cette configuration est celle utilisée par le front.')
            ->hideOnIndex();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')
            ->setColumns(4)
            ->hideOnForm();

        yield $this->buildSiteImageChoiceField('headerLogo', 'Logo du groupe', 'logo', 6, 'Le hero vidéo est fixe, donc ici on ne garde que le logo du site.');
        yield $this->buildSiteLibraryUploadField('sectionBackgroundLibraryUploads', 'Ajouter des images à la bibliothèque des fonds', 12);
        yield $this->buildSiteImageChoiceField('sectionBackgroundPrimary', 'Fond de section 1', 'background/section', 4, 'Utilisé pour toutes les sections marquées `section-bg-1`.');
        yield $this->buildSiteImageChoiceField('sectionBackgroundSecondary', 'Fond de section 2', 'background/section', 4, 'Utilisé pour toutes les sections marquées `section-bg-2`.');
        yield $this->buildSiteImageChoiceField('sectionBackgroundTertiary', 'Fond de section 3', 'background/section', 4, 'Utilisé pour toutes les sections marquées `section-bg-3`.');
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        \assert($entityInstance instanceof SiteSettings);

        $entityInstance->setIsActive(true);
        $this->processSectionBackgroundLibraryUploads($entityInstance);
        $this->synchronizeActiveVariant($entityManager, $entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        \assert($entityInstance instanceof SiteSettings);

        $entityInstance->setIsActive(true);
        $this->processSectionBackgroundLibraryUploads($entityInstance);
        $this->synchronizeActiveVariant($entityManager, $entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function synchronizeActiveVariant(EntityManagerInterface $entityManager, SiteSettings $current): void
    {
        foreach ($entityManager->getRepository(SiteSettings::class)->findAll() as $settings) {
            if (!$settings instanceof SiteSettings || $settings === $current) {
                continue;
            }

            if ($settings->isActive()) {
                $settings->setIsActive(false);
            }
        }
    }

    private function buildSiteImageChoiceField(string $property, string $label, string $relativeDirectory, int $columns = 6, ?string $help = null): ChoiceField
    {
        $directoryHelp = sprintf('Bibliothèque : /public/uploads/site/%s', trim($relativeDirectory, '/'));

        return ChoiceField::new($property, $label)
            ->setChoices($this->listSiteImageChoices($relativeDirectory))
            ->setRequired(false)
            ->setColumns($columns)
            ->setFormTypeOption('placeholder', 'Aucune image')
            ->setFormTypeOption('choice_translation_domain', false)
            ->setHelp(trim(sprintf('%s %s', $help ?? '', $directoryHelp)))
            ->hideOnIndex();
    }

    private function buildSiteLibraryUploadField(string $property, string $label, int $columns = 12): Field
    {
        return Field::new($property, $label)
            ->setFormType(FileType::class)
            ->setFormTypeOption('required', false)
            ->setFormTypeOption('multiple', true)
            ->setFormTypeOption('attr.accept', 'image/jpeg,image/png,image/webp')
            ->setFormTypeOption('constraints', [
                new All([
                    'constraints' => [
                        new Image(
                            maxSize: '10M',
                            mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                            mimeTypesMessage: 'Merci d’utiliser une image JPEG, PNG ou WebP.',
                        ),
                    ],
                ]),
            ])
            ->setColumns($columns)
            ->setHelp('Ajoutez une ou plusieurs images sans vous soucier du slot. Elles seront converties en WebP puis disponibles dans les trois sélecteurs ci-dessous.')
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

    private function processSectionBackgroundLibraryUploads(SiteSettings $siteSettings): void
    {
        foreach ($siteSettings->getSectionBackgroundLibraryUploads() as $uploadedFile) {
            if (!$uploadedFile instanceof UploadedFile) {
                continue;
            }

            $this->storeUploadedSiteImage($uploadedFile, 'background/section');
        }

        $siteSettings->setSectionBackgroundLibraryUploads([]);
    }

    private function storeUploadedSiteImage(UploadedFile $uploadedFile, string $relativeDirectory): string
    {
        $allowedExtensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $mimeType = (string) $uploadedFile->getMimeType();
        $extension = $allowedExtensions[$mimeType] ?? 'jpg';

        $originalName = pathinfo($uploadedFile->getClientOriginalName(), \PATHINFO_FILENAME);
        $safeName = trim((string) $this->slugger->slug('' !== $originalName ? $originalName : 'section-background'), '-');
        $baseName = sprintf(
            '%s-%s',
            '' !== $safeName ? $safeName : 'section-background',
            (new \DateTimeImmutable())->format('YmdHis') . '-' . bin2hex(random_bytes(3)),
        );

        $absoluteDirectory = sprintf('%s/public/uploads/site/%s', rtrim($this->projectDir, '/'), trim($relativeDirectory, '/'));
        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            throw new \RuntimeException(sprintf('Impossible de créer le dossier "%s".', $absoluteDirectory));
        }

        $temporaryFile = $uploadedFile->move($absoluteDirectory, sprintf('%s.%s', $baseName, $extension));
        $webpPath = $this->uploadedImageWebpConverter->convertToWebp($temporaryFile->getPathname());

        return trim($relativeDirectory, '/') . '/' . basename($webpPath);
    }
}
