<?php

namespace App\Repository;

use App\Entity\Theme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Theme>
 */
class ThemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Theme::class);
    }

    public function queryBuilderSearch(string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')->orderBy('t.nom', 'ASC');
        if ('' !== $q) {
            $qb->andWhere('t.nom LIKE :q')->setParameter('q', '%'.$q.'%');
        }

        return $qb;
    }
}
