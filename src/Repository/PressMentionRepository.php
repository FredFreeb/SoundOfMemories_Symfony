<?php

namespace App\Repository;

use App\Entity\PressMention;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PressMention>
 */
// Fred note: Je centralise ici les requetes de la revue de presse pour garder un affichage ordonne.
class PressMentionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PressMention::class);
    }

    /**
     * @return PressMention[]
     */
    public function findPublishedOrdered(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('p.position', 'ASC')
            ->addOrderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
