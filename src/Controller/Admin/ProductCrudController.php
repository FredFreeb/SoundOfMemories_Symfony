<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

#[AdminRoute(path: '/produits', name: 'produits')]
// Fred note: J'utilise ce CRUD pour gérer les produits vendables et leur image de couverture depuis l'admin.
final class ProductCrudController extends AbstractCrudController
{
    private const ANIMATION_CHOICES = [
        'Aucune animation' => '',
        'Vinyle rotatif' => 'vinyl',
        'Braises live' => 'embers',
        'Pulse néon' => 'pulse',
        'Glitch analogique' => 'glitch',
    ];

    private const LEVEL_CHOICES = [
        '1 / 5' => 1,
        '2 / 5' => 2,
        '3 / 5' => 3,
        '4 / 5' => 4,
        '5 / 5' => 5,
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setSearchFields(['name', 'slug', 'shortDescription', 'catalogExcerpt', 'description'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('category')
            ->add('isPublished')
            ->add('createdAt');
    }

    public function configureFields(string $pageName): iterable
    {
        // Fred note: Je fais construire le slug depuis le nom pour garder des URLs propres sans ressaisie.
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield TextField::new('name', 'Nom');
        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('name');
        yield AssociationField::new('category', 'Catégorie')
            ->autocomplete();
        yield MoneyField::new('priceCents', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents();
        yield IntegerField::new('stock', 'Stock');
        yield BooleanField::new('isMonthlyOffer', 'Offre du mois')
            ->renderAsSwitch(false)
            ->onlyOnIndex();
        yield BooleanField::new('isPublished', 'Publie');
        yield FormField::addPanel('Visuel et animation')
            ->hideOnIndex();
        yield ImageField::new('coverImage', 'Image de couverture')
            ->setBasePath('uploads/products')
            ->setUploadDir('public/uploads/products')
            ->setUploadedFileNamePattern('[slug]-[timestamp].[extension]')
            ->setRequired(false)
            ->hideOnIndex();
        yield CollectionField::new('galleryImages', 'Galerie du produit')
            ->setHelp('Fred note: J ajoute ici les images secondaires directement dans le produit pour eviter un deuxieme ecran "galerie produits".')
            ->useEntryCrudForm(ProductGalleryImageCrudController::class)
            ->allowAdd()
            ->allowDelete()
            ->renderExpanded()
            ->setEntryIsComplex()
            ->hideOnIndex();
        yield ChoiceField::new('animationKey', 'Animation du visuel')
            ->setChoices(self::ANIMATION_CHOICES)
            ->renderExpanded(false)
            ->hideOnIndex()
            ->setHelp('Choisissez une animation légère à afficher sur la carte produit côté front.');
        yield ImageField::new('coverImage', 'Visuel')
            ->setBasePath('uploads/products')
            ->onlyOnIndex();
        yield TextareaField::new('shortDescription', 'Description courte')
            ->hideOnIndex();
        yield TextareaField::new('catalogExcerpt', 'Description d accueil catalogue')
            ->hideOnIndex()
            ->setHelp('Courte accroche utilisée sur la page merchandising.');
        yield TextareaField::new('description', 'Description')
            ->hideOnIndex();
        yield FormField::addPanel('Repères de lecture')
            ->hideOnIndex();
        yield ChoiceField::new('readingLevel', 'Lecture')
            ->setChoices(self::LEVEL_CHOICES)
            ->hideOnIndex()
            ->setHelp('Peut servir de repère libre: intensité narrative, collection ou édition spéciale.');
        yield ChoiceField::new('difficultyLevel', 'Difficulté')
            ->setChoices(self::LEVEL_CHOICES)
            ->hideOnIndex();
        yield ChoiceField::new('maturityLevel', 'Maturité')
            ->setChoices(self::LEVEL_CHOICES)
            ->hideOnIndex()
            ->setHelp('Repère libre si vous voulez distinguer une édition plus collector ou adulte.');
        yield ChoiceField::new('ambianceLevel', 'Ambiance')
            ->setChoices(self::LEVEL_CHOICES)
            ->hideOnIndex()
            ->setHelp('Repère pratique pour signaler l’intensité visuelle ou l’esprit live du produit.');
        yield FormField::addPanel('Offre du mois')
            ->hideOnIndex();
        yield BooleanField::new('isMonthlyOffer', 'Mettre en avant dans l’offre du mois')
            ->hideOnIndex()
            ->setHelp('Une seule offre du mois doit être active à la fois. Si tu l’actives ici, les autres produits promotionnels seront désactivés.');
        yield TextField::new('offerBannerEyebrow', 'Surtitre de l’offre')
            ->hideOnIndex();
        yield TextField::new('offerBannerTitle', 'Titre de l’offre')
            ->hideOnIndex();
        yield TextareaField::new('offerBannerText', 'Texte de l’offre')
            ->hideOnIndex();
        yield ImageField::new('offerBannerImage', 'Image de l’offre')
            ->setBasePath('uploads/products')
            ->setUploadDir('public/uploads/products')
            ->setUploadedFileNamePattern('[slug]-offer-[timestamp].[extension]')
            ->setRequired(false)
            ->hideOnIndex();
        yield TextField::new('offerBannerPriceBefore', 'Prix avant promotion')
            ->hideOnIndex();
        yield TextField::new('offerBannerPriceAfter', 'Prix promotionnel')
            ->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Cree le')
            ->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')
            ->hideOnForm();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        \assert($entityInstance instanceof Product);

        $this->synchronizeMonthlyOffer($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        \assert($entityInstance instanceof Product);

        $this->synchronizeMonthlyOffer($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function synchronizeMonthlyOffer(Product $current): void
    {
        if (!$current->isMonthlyOffer()) {
            return;
        }

        foreach ($this->entityManager->getRepository(Product::class)->findBy(['isMonthlyOffer' => true]) as $product) {
            if ($product instanceof Product && $product !== $current) {
                $product->setIsMonthlyOffer(false);
            }
        }
    }
}
