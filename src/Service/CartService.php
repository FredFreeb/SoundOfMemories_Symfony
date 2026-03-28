<?php

namespace App\Service;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private const CART_KEY = 'cart.items';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ProductRepository $products,
    ) {
    }

    public function add(Product $product, int $quantity = 1): void
    {
        $items = $this->getRawItems();
        $items[$product->getId()] = min(
            $product->getStock(),
            max(1, ($items[$product->getId()] ?? 0) + $quantity),
        );

        $this->save($items);
    }

    public function update(Product $product, int $quantity): void
    {
        $items = $this->getRawItems();

        if ($quantity <= 0) {
            unset($items[$product->getId()]);
            $this->save($items);

            return;
        }

        $items[$product->getId()] = min($product->getStock(), $quantity);
        $this->save($items);
    }

    public function remove(Product $product): void
    {
        $items = $this->getRawItems();
        unset($items[$product->getId()]);

        $this->save($items);
    }

    public function clear(): void
    {
        $this->save([]);
    }

    /**
     * @return array<int, int>
     */
    public function getRawItems(): array
    {
        return $this->requestStack->getSession()->get(self::CART_KEY, []);
    }

    /**
     * @return array<int, array{product: Product, quantity: int, totalCents: int}>
     */
    public function getDetailedItems(): array
    {
        $detailedItems = [];

        foreach ($this->getRawItems() as $productId => $quantity) {
            $product = $this->products->find((int) $productId);
            if (!$product instanceof Product || !$product->isPublished()) {
                continue;
            }

            $safeQuantity = min($quantity, $product->getStock());
            if ($safeQuantity <= 0) {
                continue;
            }

            $detailedItems[] = [
                'product' => $product,
                'quantity' => $safeQuantity,
                'totalCents' => $product->getPriceCents() * $safeQuantity,
            ];
        }

        return $detailedItems;
    }

    public function countItems(): int
    {
        return array_sum($this->getRawItems());
    }

    public function getTotalCents(): int
    {
        return array_reduce(
            $this->getDetailedItems(),
            static fn (int $carry, array $item): int => $carry + $item['totalCents'],
            0,
        );
    }

    public function isEmpty(): bool
    {
        return [] === $this->getDetailedItems();
    }

    /**
     * @param array<int, int> $items
     */
    private function save(array $items): void
    {
        $this->requestStack->getSession()->set(self::CART_KEY, $items);
    }
}
