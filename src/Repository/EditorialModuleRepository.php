<?php

namespace App\Repository;

use App\Entity\EditorialModule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EditorialModule>
 */
class EditorialModuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EditorialModule::class);
    }

    /**
     * @return EditorialModule[]
     */
    public function findPublishedForPage(string $pageKey): array
    {
        return $this->createQueryBuilder('module')
            ->andWhere('module.pageKey = :pageKey')
            ->andWhere('module.isPublished = :published')
            ->setParameter('pageKey', $pageKey)
            ->setParameter('published', true)
            ->orderBy('module.position', 'ASC')
            ->addOrderBy('module.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<string, EditorialModule>
     */
    public function findPublishedMapForPage(string $pageKey): array
    {
        $modules = $this->findPublishedForPage($pageKey);
        $moduleMap = [];

        foreach ($modules as $module) {
            $moduleMap[$module->getSectionKey()] = $module;
        }

        return $moduleMap;
    }
}
