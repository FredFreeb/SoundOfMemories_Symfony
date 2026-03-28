<?php

namespace App\Repository;

use App\Entity\SiteSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteSettings>
 */
class SiteSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteSettings::class);
    }

    public function findCurrent(): ?SiteSettings
    {
        // Fred note: Je privilegie toujours la variante active, puis je retombe sur la premiere si besoin.
        return $this->createQueryBuilder('settings')
            ->orderBy('settings.isActive', 'DESC')
            ->addOrderBy('settings.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
