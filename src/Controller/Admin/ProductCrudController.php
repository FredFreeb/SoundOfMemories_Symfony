<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Form\ProductVariantType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
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
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

    private const BADGE_TONE_CHOICES = [
        'Acier' => 'steel',
        'Braise' => 'ember',
        'Sable' => 'sand',
        'Forêt' => 'forest',
    ];

    private const LEVEL_CHOICES = [
        '1 / 5' => 1,
        '2 / 5' => 2,
        '3 / 5' => 3,
        '4 / 5' => 4,
        '5 / 5' => 5,
    ];

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setDefaultRowAction(Action::EDIT)
            ->setSearchFields(['name', 'slug', 'shortDescription', 'catalogExcerpt', 'description'])
            ->setDefaultSort(['sortPosition' => 'ASC', 'createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $previewAction = Action::new('previewStoreProduct', 'Voir la fiche', 'fas fa-arrow-up-right-from-square')
            ->linkToUrl(fn (Product $product): string => $this->getUrlGenerator()->generate('store_shop_show', ['slug' => $product->getSlug()]))
            ->setHtmlAttributes(['target' => '_blank', 'rel' => 'noopener']);

        return $actions
            ->add(Crud::PAGE_INDEX, $previewAction)
            ->add(Crud::PAGE_EDIT, $previewAction)
            ->update(Crud::PAGE_INDEX, Action::EDIT, static fn (Action $action): Action => $action
                ->setLabel('Gérer')
                ->setIcon('fas fa-shirt'));
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('category')
            ->add('isPublished')
            ->add('isMonthlyOffer')
            ->add('createdAt');
    }

    public function configureFields(string $pageName): iterable
    {
        if (Crud::PAGE_INDEX === $pageName) {
            yield ImageField::new('coverImage', 'Visuel')
                ->setBasePath('uploads/products');
            yield TextField::new('name', 'Produit')
                ->formatValue(function ($value, Product $product): string {
                    $badge = $product->getMerchBadge();
                    $suffix = '';

                    if (null !== $badge && '' !== trim($badge)) {
                        $suffix = sprintf(' · %s', $badge);
                    }

                    return sprintf(
                        '<strong>%s</strong><br><small>%s%s</small>',
                        htmlspecialchars((string) $product->getName(), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars((string) $product->getSlug(), ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($suffix, ENT_QUOTES, 'UTF-8'),
                    );
                })
                ->renderAsHtml();
            yield TextField::new('category', 'Catégorie')
                ->formatValue(fn ($value, Product $product): string => $product->getCategory()?->getName() ?: 'Merch');
            yield TextField::new('variantChoiceLabel', 'Options')
                ->formatValue(function ($value, Product $product): string {
                    $variantCount = count($product->getPublishedVariants());

                    if ($variantCount <= 0) {
                        return 'Edition unique';
                    }

                    return sprintf(
                        '%d %s · %s',
                        $variantCount,
                        $variantCount > 1 ? 'variantes' : 'variante',
                        mb_strtolower($product->getVariantChoiceLabel()),
                    );
                });
            yield MoneyField::new('priceCents', 'Prix départ')
                ->setCurrency('EUR')
                ->setStoredAsCents();
            yield TextField::new('stock', 'Stock')
                ->formatValue(fn ($value, Product $product): string => sprintf('%d dispo', $product->getDisplayStock()));
            yield IntegerField::new('sortPosition', 'Ordre');
            yield TextField::new('promotionStartsAt', 'Fenêtre promo')
                ->formatValue(function ($value, Product $product): string {
                    if (!$product->hasPromotionSchedule()) {
                        return 'Libre';
                    }

                    $parts = [];

                    if ($product->getPromotionStartsAt() instanceof \DateTimeImmutable) {
                        $parts[] = 'du ' . $product->getPromotionStartsAt()->format('d/m');
                    }

                    if ($product->getPromotionEndsAt() instanceof \DateTimeImmutable) {
                        $parts[] = 'au ' . $product->getPromotionEndsAt()->format('d/m');
                    }

                    return implode(' ', $parts);
                });
            yield TextField::new('promotionStateLabel', 'Promo')
                ->formatValue(fn ($value, Product $product): string => $this->renderAdminStateBadge(
                    $product->getPromotionStateLabel(),
                    match ($product->getPromotionStateLabel()) {
                        'Active', 'Toujours active' => 'success',
                        'Programmée' => 'warning',
                        'Terminée' => 'secondary',
                        default => 'light',
                    }
                ))
                ->renderAsHtml();
            yield BooleanField::new('isMonthlyOffer', 'Offre du mois')
                ->renderAsSwitch(false);
            yield BooleanField::new('isPublished', 'Publié')
                ->renderAsSwitch(false);

            return;
        }

        // Fred note: Je fais construire le slug depuis le nom pour garder des URLs propres sans ressaisie.
        yield IdField::new('id')->hideOnForm()->hideOnIndex();
        yield TextField::new('name', 'Nom');
        yield SlugField::new('slug', 'Slug')
            ->setTargetFieldName('name');
        yield IntegerField::new('sortPosition', 'Ordre vitrine')
            ->setHelp('Plus la valeur est petite, plus le produit remonte dans la boutique.');
        yield AssociationField::new('category', 'Catégorie')
            ->autocomplete();
        yield TextField::new('merchBadge', 'Badge vitrine')
            ->setHelp('Exemples: Nouveau drop, Édition limitée, Best seller.')
            ->hideOnIndex();
        yield ChoiceField::new('merchBadgeTone', 'Couleur du badge')
            ->setChoices(self::BADGE_TONE_CHOICES)
            ->hideOnIndex();
        yield TextField::new('variantChoiceLabel', 'Nom du selecteur')
            ->hideOnIndex()
            ->setHelp('Exemples: Taille, Format, Edition. Si vide, la boutique affichera "Taille".');
        yield MoneyField::new('priceCents', 'Prix')
            ->setCurrency('EUR')
            ->setStoredAsCents()
            ->setHelp('Prix de base utilise si le produit n’a pas de variantes.');
        yield IntegerField::new('stock', 'Stock')
            ->setHelp('Stock de base utilise si le produit n’a pas de variantes.');
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
        yield CollectionField::new('variants', 'Variantes vendues')
            ->setHelp('Ajoutez ici les tailles, formats ou éditions. Dès qu’une variante existe, la boutique utilise ses prix et stocks.')
            ->setEntryType(ProductVariantType::class)
            ->setFormTypeOption('by_reference', false)
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
        yield TextareaField::new('shortDescription', 'Description courte')
            ->hideOnIndex();
        yield TextareaField::new('catalogExcerpt', 'Description d accueil catalogue')
            ->hideOnIndex()
            ->setHelp('Courte accroche utilisée sur la page merchandising.');
        yield TextareaField::new('description', 'Description')
            ->hideOnIndex();
        yield FormField::addPanel('Arguments de vente')
            ->hideOnIndex();
        yield TextField::new('featureOne', 'Point fort 1')
            ->hideOnIndex();
        yield TextField::new('featureTwo', 'Point fort 2')
            ->hideOnIndex();
        yield TextField::new('featureThree', 'Point fort 3')
            ->hideOnIndex();
        yield TextField::new('fitDetails', 'Coupe / format')
            ->hideOnIndex()
            ->setHelp('Exemple: Coupe standard, unisexe, poster 50 x 70 cm.');
        yield TextField::new('materialDetails', 'Matière / finition')
            ->hideOnIndex();
        yield TextField::new('sizeGuideText', 'Guide taille / choix')
            ->hideOnIndex();
        yield TextField::new('shippingDetails', 'Info expédition')
            ->hideOnIndex()
            ->setHelp('Exemple: Expédition sous 3 à 5 jours ouvrés.');
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
        yield DateTimeField::new('promotionStartsAt', 'Début de la promo')
            ->hideOnIndex()
            ->setHelp('Laissez vide pour une promo immédiatement visible.');
        yield DateTimeField::new('promotionEndsAt', 'Fin de la promo')
            ->hideOnIndex()
            ->setHelp('Laissez vide pour une promo sans date de fin.');
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

    public function createEntity(string $entityFqcn): Product
    {
        $product = new Product();
        $product->setVariantChoiceLabel('Taille');

        return $product;
    }

    private function synchronizeMonthlyOffer(Product $current): void
    {
        $this->synchronizeVariants($current);

        if (!$current->isMonthlyOffer()) {
            return;
        }

        foreach ($this->getEntityManager()->getRepository(Product::class)->findBy(['isMonthlyOffer' => true]) as $product) {
            if ($product instanceof Product && $product !== $current) {
                $product->setIsMonthlyOffer(false);
            }
        }
    }

    private function renderAdminStateBadge(string $label, string $tone): string
    {
        return sprintf(
            '<span class="badge badge-%s">%s</span>',
            $tone,
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
        );
    }

    private function synchronizeVariants(Product $product): void
    {
        $defaultVariant = null;

        foreach ($product->getVariants() as $variant) {
            \assert($variant instanceof ProductVariant);
            $variant->setProduct($product);

            if ($variant->isDefault() && $variant->isPublished()) {
                if (null === $defaultVariant) {
                    $defaultVariant = $variant;
                } else {
                    $variant->setIsDefault(false);
                }
            }
        }

        if (null === $defaultVariant && $product->hasVariants()) {
            $defaultVariant = $product->getDefaultVariant();

            if ($defaultVariant instanceof ProductVariant) {
                $defaultVariant->setIsDefault(true);
            }
        }

        if ($defaultVariant instanceof ProductVariant) {
            $product
                ->setPriceCents($defaultVariant->getPriceCents())
                ->setStock($product->getDisplayStock());
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getEntityManager(): EntityManagerInterface
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get(EntityManagerInterface::class);

        return $entityManager;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getUrlGenerator(): UrlGeneratorInterface
    {
        /** @var UrlGeneratorInterface $urlGenerator */
        $urlGenerator = $this->container->get('router');

        return $urlGenerator;
    }
}
