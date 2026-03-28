<?php

namespace App\Repository;

use App\Entity\FaqEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FaqEntry>
 */
// Fred note: Ce repository sert surtout a preparer une FAQ propre pour le front.
class FaqEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FaqEntry::class);
    }

    /**
     * @return FaqEntry[]
     */
    public function findPublishedOrdered(): array
    {
        // Fred note: On trie d'abord par position pour laisser le back-office decider de l'ordre d'affichage.
        return $this->createQueryBuilder('f')
            ->andWhere('f.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('f.position', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
