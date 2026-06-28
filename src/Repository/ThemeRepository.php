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

    // Retourne un QueryBuilder pour la liste paginée de l'interface modérateur.
    // Sans $q : tous les thèmes triés par nom.
    // Avec $q : filtrés par nom (recherche partielle).
    public function queryBuilderSearch(string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')->orderBy('t.nom', 'ASC');
        if ('' !== $q) {
            $qb->andWhere('t.nom LIKE :q')->setParameter('q', '%'.$q.'%');
        }

        return $qb;
    }
}
