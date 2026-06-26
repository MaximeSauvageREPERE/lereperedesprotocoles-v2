<?php

namespace App\Repository;

use App\Entity\Profession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Profession>
 */
class ProfessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Profession::class);
    }

    public function queryBuilderSearch(string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')->orderBy('p.nom', 'ASC');
        if ('' !== $q) {
            $qb->andWhere('p.nom LIKE :q')->setParameter('q', '%'.$q.'%');
        }

        return $qb;
    }
}
