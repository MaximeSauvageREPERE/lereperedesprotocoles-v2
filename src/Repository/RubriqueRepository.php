<?php

namespace App\Repository;

use App\Entity\Rubrique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Rubrique>
 */
class RubriqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rubrique::class);
    }

    // Retourne un QueryBuilder pour la liste paginée de l'interface modérateur.
    // Sans $q : toutes les rubriques triées par nom.
    // Avec $q : filtrées par nom (recherche partielle).
    public function queryBuilderSearch(string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('r')->orderBy('r.nom', 'ASC');
        if ('' !== $q) {
            $qb->andWhere('r.nom LIKE :q')->setParameter('q', '%'.$q.'%');
        }

        return $qb;
    }
}
