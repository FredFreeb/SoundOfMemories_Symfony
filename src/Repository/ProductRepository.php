<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
// Fred note: Je garde ici les requetes metier reutilisables pour le catalogue public.
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * @return Product[]
     */
    public function findPublished(?int $limit = null): array
    {
        // Fred note: Je limite le front aux produits explicitement publies pour eviter les brouillons en vitrine.
        $queryBuilder = $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('p.sortPosition', 'ASC')
            ->addOrderBy('p.createdAt', 'DESC');

        if (null !== $limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    public function findCurrentMonthlyOffer(): ?Product
    {
        $products = $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = :published')
            ->andWhere('p.isMonthlyOffer = :monthlyOffer')
            ->setParameter('published', true)
            ->setParameter('monthlyOffer', true)
            ->orderBy('p.sortPosition', 'ASC')
            ->addOrderBy('p.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        foreach ($products as $product) {
            if ($product instanceof Product && $product->isPromotionActive()) {
                return $product;
            }
        }

        return $products[0] ?? null;
    }

    /**
     * @param int[] $excludedProductIds
     * @param int[] $preferredCategoryIds
     *
     * @return Product[]
     */
    public function findCartUpsells(array $excludedProductIds, array $preferredCategoryIds = [], int $limit = 4): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('p.sortPosition', 'ASC')
            ->addOrderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit);

        if ([] !== $excludedProductIds) {
            $queryBuilder
                ->andWhere('p.id NOT IN (:excludedIds)')
                ->setParameter('excludedIds', array_values(array_unique(array_map('intval', $excludedProductIds))));
        }

        if ([] !== $preferredCategoryIds) {
            $queryBuilder
                ->addOrderBy('CASE WHEN IDENTITY(p.category) IN (:preferredCategories) THEN 0 ELSE 1 END', 'ASC')
                ->setParameter('preferredCategories', array_values(array_unique(array_map('intval', $preferredCategoryIds))));
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Product[]
     */
    public function findRelated(Product $product, int $limit = 4): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = :published')
            ->andWhere('p != :product')
            ->setParameter('published', true)
            ->setParameter('product', $product)
            ->orderBy('p.sortPosition', 'ASC')
            ->addOrderBy('p.updatedAt', 'DESC')
            ->setMaxResults($limit);

        if (null !== $product->getCategory()) {
            $queryBuilder
                ->addOrderBy('CASE WHEN p.category = :category THEN 0 ELSE 1 END', 'ASC')
                ->setParameter('category', $product->getCategory());
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }
}
