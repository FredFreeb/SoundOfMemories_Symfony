<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\ProductVariant;
use App\Repository\ProductRepository;
use App\Repository\ProductVariantRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    private const CART_KEY = 'cart.items';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ProductRepository $products,
        private readonly ProductVariantRepository $variants,
    ) {
    }

    public function add(Product $product, ?ProductVariant $variant = null, int $quantity = 1): void
    {
        $items = $this->getRawItems();
        $resolvedVariant = $this->resolveVariant($product, $variant);
        $cartKey = $this->buildCartKey($product->getId(), $resolvedVariant?->getId());
        $maxStock = $resolvedVariant?->getStock() ?? $product->getStock();

        if ($maxStock <= 0) {
            return;
        }

        $items[$cartKey] = [
            'productId' => (int) $product->getId(),
            'variantId' => $resolvedVariant?->getId(),
            'quantity' => min(
                $maxStock,
                max(1, (($items[$cartKey]['quantity'] ?? 0) + $quantity)),
            ),
        ];

        $this->save($items);
    }

    public function update(string $itemKey, int $quantity): void
    {
        $items = $this->getRawItems();

        if ($quantity <= 0) {
            unset($items[$itemKey]);
            $this->save($items);

            return;
        }

        if (!isset($items[$itemKey])) {
            return;
        }

        $item = $items[$itemKey];
        $product = $this->products->find((int) $item['productId']);
        if (!$product instanceof Product || !$product->isPublished()) {
            unset($items[$itemKey]);
            $this->save($items);

            return;
        }

        $variant = null !== $item['variantId'] ? $this->variants->find((int) $item['variantId']) : null;
        $resolvedVariant = $this->resolveVariant($product, $variant instanceof ProductVariant ? $variant : null);
        $maxStock = $resolvedVariant?->getStock() ?? $product->getStock();

        if ($maxStock <= 0) {
            unset($items[$itemKey]);
            $this->save($items);

            return;
        }

        $items[$itemKey]['variantId'] = $resolvedVariant?->getId();
        $items[$itemKey]['quantity'] = min($maxStock, $quantity);
        $this->save($items);
    }

    public function remove(string $itemKey): void
    {
        $items = $this->getRawItems();
        unset($items[$itemKey]);

        $this->save($items);
    }

    public function clear(): void
    {
        $this->save([]);
    }

    /**
     * @return array<string, array{productId: int, variantId: ?int, quantity: int}>
     */
    public function getRawItems(): array
    {
        /** @var mixed $storedItems */
        $storedItems = $this->requestStack->getSession()->get(self::CART_KEY, []);

        return $this->normalizeRawItems(\is_array($storedItems) ? $storedItems : []);
    }

    /**
     * @return array<int, array{
     *     cartKey: string,
     *     product: Product,
     *     variant: ?ProductVariant,
     *     quantity: int,
     *     unitPriceCents: int,
     *     compareAtPriceCents: ?int,
     *     totalCents: int,
     *     availableStock: int,
     *     displayName: string
     * }>
     */
    public function getDetailedItems(): array
    {
        $detailedItems = [];

        foreach ($this->getRawItems() as $cartKey => $item) {
            $product = $this->products->find((int) $item['productId']);
            if (!$product instanceof Product || !$product->isPublished()) {
                continue;
            }

            $variant = null !== $item['variantId'] ? $this->variants->find((int) $item['variantId']) : null;
            $resolvedVariant = $this->resolveVariant($product, $variant instanceof ProductVariant ? $variant : null);
            $availableStock = $resolvedVariant?->getStock() ?? $product->getStock();
            $safeQuantity = min($item['quantity'], $availableStock);
            if ($safeQuantity <= 0) {
                continue;
            }

            $unitPriceCents = $resolvedVariant?->getPriceCents() ?? $product->getPriceCents();
            $compareAtPriceCents = $product->isPromotionActive() ? $resolvedVariant?->getCompareAtPriceCents() : null;
            $variantLabel = $resolvedVariant?->getLabel();
            $displayName = $product->getName() ?? 'Produit';

            if (null !== $variantLabel && '' !== trim($variantLabel)) {
                $displayName .= ' · ' . $variantLabel;
            }

            $detailedItems[] = [
                'cartKey' => $cartKey,
                'product' => $product,
                'variant' => $resolvedVariant,
                'quantity' => $safeQuantity,
                'unitPriceCents' => $unitPriceCents,
                'compareAtPriceCents' => $compareAtPriceCents,
                'totalCents' => $unitPriceCents * $safeQuantity,
                'availableStock' => $availableStock,
                'displayName' => $displayName,
            ];
        }

        return $detailedItems;
    }

    public function countItems(): int
    {
        return array_reduce(
            $this->getRawItems(),
            static fn (int $carry, array $item): int => $carry + max(0, (int) ($item['quantity'] ?? 0)),
            0,
        );
    }

    public function getTotalCents(): int
    {
        return array_reduce(
            $this->getDetailedItems(),
            static fn (int $carry, array $item): int => $carry + $item['totalCents'],
            0,
        );
    }

    public function getCompareAtSavingsCents(): int
    {
        return array_reduce(
            $this->getDetailedItems(),
            static function (int $carry, array $item): int {
                $referencePrice = (int) ($item['compareAtPriceCents'] ?? 0);
                $unitPrice = (int) $item['unitPriceCents'];

                if ($referencePrice <= $unitPrice) {
                    return $carry;
                }

                return $carry + (($referencePrice - $unitPrice) * (int) $item['quantity']);
            },
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

    private function buildCartKey(?int $productId, ?int $variantId): string
    {
        return sprintf('%d:%d', (int) $productId, (int) ($variantId ?? 0));
    }

    private function resolveVariant(Product $product, ?ProductVariant $variant): ?ProductVariant
    {
        if ($variant instanceof ProductVariant && $variant->getProduct() === $product && $variant->isPublished()) {
            return $variant;
        }

        return $product->getDefaultVariant();
    }

    /**
     * @param array<mixed> $items
     *
     * @return array<string, array{productId: int, variantId: ?int, quantity: int}>
     */
    private function normalizeRawItems(array $items): array
    {
        $normalizedItems = [];

        foreach ($items as $key => $value) {
            if (\is_array($value) && isset($value['productId'], $value['quantity'])) {
                $productId = (int) $value['productId'];
                $variantId = isset($value['variantId']) && null !== $value['variantId'] ? (int) $value['variantId'] : null;
                $quantity = max(0, (int) $value['quantity']);

                if ($productId <= 0 || $quantity <= 0) {
                    continue;
                }

                $normalizedItems[$this->buildCartKey($productId, $variantId)] = [
                    'productId' => $productId,
                    'variantId' => $variantId,
                    'quantity' => $quantity,
                ];

                continue;
            }

            if ((\is_int($key) || (\is_string($key) && ctype_digit($key))) && \is_numeric($value)) {
                $productId = (int) $key;
                $quantity = max(0, (int) $value);

                if ($productId <= 0 || $quantity <= 0) {
                    continue;
                }

                $normalizedItems[$this->buildCartKey($productId, null)] = [
                    'productId' => $productId,
                    'variantId' => null,
                    'quantity' => $quantity,
                ];
            }
        }

        return $normalizedItems;
    }
}
