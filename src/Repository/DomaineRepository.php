<?php

namespace App\Repository;

use App\Entity\Domaine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des domaines.
 *
 * @extends ServiceEntityRepository<Domaine>
 * @package App\Repository
 */
class DomaineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Domaine::class);
    }

    /**
     * Retourne un QueryBuilder pour la liste paginée des domaines (interface modérateur).
     *
     * Sans $q : tous les domaines triés par nom.
     * Avec $q : filtrés par nom (LIKE %q%, insensible à la casse côté MySQL).
     *
     * @param string $q Terme de recherche (chaîne vide pour tout retourner)
     */
    public function queryBuilderSearch(string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('d')->orderBy('d.nom', 'ASC');
        if ('' !== $q) {
            $qb->andWhere('d.nom LIKE :q')->setParameter('q', '%'.$q.'%');
        }

        return $qb;
    }
}
