<?php

namespace App\Repository;

use App\Entity\Protocole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des protocoles médicaux.
 *
 * @extends ServiceEntityRepository<Protocole>
 * @package App\Repository
 */
class ProtocoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Protocole::class);
    }

    /**
     * Retourne un QueryBuilder pour la liste paginée des protocoles (interface modérateur).
     *
     * La recherche porte sur le titre (contrairement aux autres entités qui filtrent sur le nom).
     * Sans $q : tous les protocoles triés par titre. Avec $q : filtrés par titre (LIKE %q%).
     *
     * @param string $q Terme de recherche (chaîne vide pour tout retourner)
     */
    public function queryBuilderSearch(string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')->orderBy('p.titre', 'ASC');
        if ('' !== $q) {
            $qb->andWhere('p.titre LIKE :q')->setParameter('q', '%'.$q.'%');
        }

        return $qb;
    }
}
