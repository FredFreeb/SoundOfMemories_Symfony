<?php

namespace App\Controller\Store;

use App\Entity\Product;
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
    public function index(CartService $cart): Response
    {
        return $this->render('store/cart/index.html.twig', [
            'items' => $cart->getDetailedItems(),
            'totalCents' => $cart->getTotalCents(),
        ]);
    }

    #[Route('/ajouter/{id}', name: 'store_cart_add', methods: ['POST'])]
    public function add(Product $product, Request $request, CartService $cart): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('cart_add_' . $product->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if (!$product->isPublished() || $product->getStock() <= 0) {
            $this->addFlash('warning', 'Ce produit n’est pas disponible a la vente.');

            return $this->redirectToRoute('store_shop_index');
        }

        $quantity = max(1, (int) $request->request->get('quantity', 1));
        $cart->add($product, $quantity);

        $this->addFlash('success', sprintf('%s a ete ajoute au panier.', $product->getName()));

        return $this->redirectToRoute('store_cart');
    }

    #[Route('/modifier/{id}', name: 'store_cart_update', methods: ['POST'])]
    public function update(Product $product, Request $request, CartService $cart): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('cart_update_' . $product->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $cart->update($product, (int) $request->request->get('quantity', 1));

        return $this->redirectToRoute('store_cart');
    }

    #[Route('/supprimer/{id}', name: 'store_cart_remove', methods: ['POST'])]
    public function remove(Product $product, Request $request, CartService $cart): RedirectResponse
    {
        if (!$this->isCsrfTokenValid('cart_remove_' . $product->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $cart->remove($product);
        $this->addFlash('success', sprintf('%s a ete retire du panier.', $product->getName()));

        return $this->redirectToRoute('store_cart');
    }
}
