<?php

namespace App\Controller\Store;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\SiteSettingsProvider;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/boutique')]
// Fred note: J'utilise ce controleur pour piloter la liste des enquêtes et leur fiche detaillee.
final class ShopController extends AbstractController
{
    #[Route('', name: 'store_shop_index', methods: ['GET'])]
    public function index(ProductRepository $products, SiteSettingsProvider $siteSettings): Response
    {
        $publishedProducts = $products->findPublished();
        $categories = [];
        $productsByCategory = [];

        // Fred note: Je reconstruis ici les collections visibles a partir des produits publies pour enrichir la page catalogue.
        foreach ($publishedProducts as $product) {
            $category = $product->getCategory();
            if (null === $category) {
                $categoryKey = 'merch';
                $productsByCategory[$categoryKey]['category'] = null;
                $productsByCategory[$categoryKey]['products'][] = $product;

                continue;
            }

            $categoryKey = $category->getSlug() ?? $category->getName() ?? (string) $category->getId();
            $categories[$categoryKey] = $category;
            $productsByCategory[$categoryKey]['category'] = $category;
            $productsByCategory[$categoryKey]['products'][] = $product;
        }

        return $this->render('store/shop/index.html.twig', [
            'products' => $publishedProducts,
            'categories' => array_values($categories),
            'productsByCategory' => array_values($productsByCategory),
            'featuredProduct' => $products->findCurrentMonthlyOffer() ?? $publishedProducts[0] ?? null,
            'siteSettings' => $siteSettings->getCurrent(),
        ]);
    }

    #[Route('/{slug}', name: 'store_shop_show', methods: ['GET'])]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] Product $product,
        ProductRepository $products,
    ): Response
    {
        // Fred note: Meme si le slug existe, je masque les produits non publies au public.
        if (!$product->isPublished()) {
            throw $this->createNotFoundException();
        }

        $variants = $product->getPublishedVariants();
        $defaultVariant = $product->getDefaultVariant();

        return $this->render('store/shop/show.html.twig', [
            'product' => $product,
            'variants' => $variants,
            'defaultVariant' => $defaultVariant,
            'relatedProducts' => $products->findRelated($product),
        ]);
    }
}
