<?php

namespace App\Repository;

use App\Entity\Profession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des professions médicales.
 *
 * @extends ServiceEntityRepository<Profession>
 */
class ProfessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Profession::class);
    }

    /**
     * Retourne un QueryBuilder pour la liste paginée des professions (interface admin).
     *
     * Sans $q : toutes les professions triées par nom. Avec $q : filtrées par nom (LIKE %q%).
     *
     * @param string $q Terme de recherche (chaîne vide pour tout retourner)
     */
    public function queryBuilderSearch(string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')->orderBy('p.nom', 'ASC');
        if ('' !== $q) {
            $qb->andWhere('p.nom LIKE :q')->setParameter('q', '%'.$q.'%');
        }

        return $qb;
    }
}
