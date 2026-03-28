<?php

namespace App\Repository;

use App\Entity\GalleryPhoto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GalleryPhoto>
 */
class GalleryPhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GalleryPhoto::class);
    }

    /**
     * @return list<GalleryPhoto>
     */
    public function findPublishedOrdered(): array
    {
        return $this->createQueryBuilder('photo')
            ->andWhere('photo.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('photo.position', 'ASC')
            ->addOrderBy('photo.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
