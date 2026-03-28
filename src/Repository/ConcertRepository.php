<?php

namespace App\Repository;

use App\Entity\Concert;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Concert>
 */
class ConcertRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Concert::class);
    }

    /**
     * @return Concert[]
     */
    public function findUpcomingPublished(int $limit = 6): array
    {
        return $this->createQueryBuilder('concert')
            ->andWhere('concert.isPublished = :published')
            ->andWhere('concert.concertAt >= :now')
            ->setParameter('published', true)
            ->setParameter('now', new \DateTimeImmutable('-6 hours'))
            ->orderBy('concert.isHighlighted', 'DESC')
            ->addOrderBy('concert.concertAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Concert[]
     */
    public function findPublishedChronological(): array
    {
        return $this->createQueryBuilder('concert')
            ->andWhere('concert.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('concert.concertAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
