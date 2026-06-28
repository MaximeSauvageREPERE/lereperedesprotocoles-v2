<?php

namespace App\Repository;

use App\Entity\Domaine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Domaine>
 */
class DomaineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Domaine::class);
    }

    // Retourne un QueryBuilder pour la liste paginée de l'interface modérateur.
    // Sans $q : tous les domaines triés par nom.
    // Avec $q : filtrés par nom (recherche partielle insensible à la casse côté MySQL).
    public function queryBuilderSearch(string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('d')->orderBy('d.nom', 'ASC');
        if ('' !== $q) {
            $qb->andWhere('d.nom LIKE :q')->setParameter('q', '%'.$q.'%');
        }

        return $qb;
    }
}
