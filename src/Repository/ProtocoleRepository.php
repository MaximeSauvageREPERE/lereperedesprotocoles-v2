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

    /**
     * Recherche globale dans tous les protocoles (navigation publique).
     *
     * Porte sur le titre et la description. Les JOINs pré-chargent theme, rubrique et domaines
     * pour éviter les requêtes N+1 lors de l'affichage du fil d'Ariane dans les résultats.
     *
     * @return Protocole[]
     */
    public function search(string $q): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.titre LIKE :q OR p.description LIKE :q')
            ->setParameter('q', '%'.$q.'%')
            ->orderBy('p.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
