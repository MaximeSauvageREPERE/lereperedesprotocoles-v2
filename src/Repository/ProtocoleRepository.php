<?php

namespace App\Repository;

use App\Entity\Protocole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Protocole>
 */
class ProtocoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Protocole::class);
    }

    // Retourne un QueryBuilder pour la liste paginée de l'interface modérateur.
    // La recherche porte sur le titre (et non le nom, contrairement aux autres entités).
    public function queryBuilderSearch(string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')->orderBy('p.titre', 'ASC');
        if ('' !== $q) {
            $qb->andWhere('p.titre LIKE :q')->setParameter('q', '%'.$q.'%');
        }

        return $qb;
    }
}
