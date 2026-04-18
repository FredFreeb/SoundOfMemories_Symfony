<?php

namespace App\Controller\Store;

use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Repository\ProductRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier')]
final class CartController extends AbstractController
{
    #[Route('', name: 'store_cart', methods: ['GET'])]
    public function index(CartService $cart, ProductRepository $products): Response
    {
        $items = $cart->getDetailedItems();
        $excludedProductIds = [];
        $preferredCategoryIds = [];

        foreach ($items as $item) {
            $excludedProductIds[] = $item['product']->getId();
            $categoryId = $item['product']->getCategory()?->getId();

            if (null !== $categoryId) {
                $preferredCategoryIds[] = $categoryId;
            }
        }

        return $this->render('store/cart/index.html.twig', [
            'items' => $items,
            'totalCents' => $cart->getTotalCents(),
            'savingsCents' => $cart->getCompareAtSavingsCents(),
            'recommendedProducts' => $products->findCartUpsells($excludedProductIds, $preferredCategoryIds),
        ]);
    }

    #[Route('/ajouter/{id}', name: 'store_cart_add', methods: ['POST'])]
    public function add(Product $product, Request $request, CartService $cart): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('cart_add_' . $product->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $variant = $this->resolveRequestedVariant($product, $request);

        if (!$product->isPublished() || ($variant instanceof ProductVariant ? $variant->getStock() <= 0 : $product->getDisplayStock() <= 0)) {
            $this->addFlash('warning', 'Ce produit n’est pas disponible a la vente.');

            return $this->redirectToRoute('store_shop_index');
        }

        if ($product->hasVariants() && !($variant instanceof ProductVariant)) {
            $this->addFlash('warning', sprintf('Choisis une option de %s avant d’ajouter ce produit.', mb_strtolower($product->getVariantChoiceLabel())));

            return $this->redirectToRoute('store_shop_show', ['slug' => $product->getSlug()]);
        }

        $quantity = max(1, (int) $request->request->get('quantity', 1));
        $cart->add($product, $variant, $quantity);

        $flashName = $product->getName();
        if ($variant instanceof ProductVariant) {
            $flashName .= sprintf(' (%s)', $variant->getLabel());
        }

        $this->addFlash('success', sprintf('%s a ete ajoute au panier.', $flashName));

        return $this->redirectToRoute('store_cart');
    }

    #[Route('/modifier', name: 'store_cart_update', methods: ['POST'])]
    public function update(Request $request, CartService $cart): RedirectResponse
    {
        $itemKey = trim((string) $request->request->get('itemKey'));

        if (!$this->isCsrfTokenValid('cart_update_' . $itemKey, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $cart->update($itemKey, (int) $request->request->get('quantity', 1));

        return $this->redirectToRoute('store_cart');
    }

    #[Route('/supprimer', name: 'store_cart_remove', methods: ['POST'])]
    public function remove(Request $request, CartService $cart): RedirectResponse
    {
        $itemKey = trim((string) $request->request->get('itemKey'));

        if (!$this->isCsrfTokenValid('cart_remove_' . $itemKey, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $cart->remove($itemKey);
        $this->addFlash('success', 'Le produit a ete retire du panier.');

        return $this->redirectToRoute('store_cart');
    }

    private function resolveRequestedVariant(Product $product, Request $request): ?ProductVariant
    {
        $variantId = (int) $request->request->get('variantId');

        if ($variantId <= 0) {
            return $product->hasVariants() ? null : $product->getDefaultVariant();
        }

        foreach ($product->getVariants() as $variant) {
            if (
                $variant instanceof ProductVariant
                && $variant->getId() === $variantId
                && $variant->isPublished()
            ) {
                return $variant;
            }
        }

        return null;
    }
}
