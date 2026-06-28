<?php

namespace App\Repository;

use App\Entity\DemandeInscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DemandeInscription>
 */
class DemandeInscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeInscription::class);
    }

    // Retourne directement le tableau de résultats — utilisé quand on n'a pas besoin de pagination.
    public function findEnAttentePourAdmin(): array
    {
        return $this->queryBuilderEnAttentePourAdmin()->getQuery()->getResult();
    }

    // Retourne directement le tableau — utilisé quand on n'a pas besoin de pagination.
    public function findNonVerifiees(): array
    {
        return $this->queryBuilderNonVerifiees()->getQuery()->getResult();
    }

    // Retourne un QueryBuilder (et non un tableau) pour que KnpPaginator puisse
    // ajouter dynamiquement LIMIT/OFFSET et compter le total sans charger tous les objets en mémoire.
    // Le filtre $q est optionnel : sans lui, toutes les demandes en attente sont retournées.
    public function queryBuilderEnAttentePourAdmin(string $q = ''): QueryBuilder
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.emailVerifie = true')
            ->setParameter('statut', DemandeInscription::STATUT_EN_ATTENTE)
            // Les plus anciennes en premier : l'admin traite dans l'ordre d'arrivée.
            ->orderBy('d.createdAt', 'ASC');

        if ('' !== $q) {
            // Recherche partielle (LIKE %q%) sur nom, prénom et email simultanément.
            $qb->andWhere('d.nom LIKE :q OR d.prenom LIKE :q OR d.email LIKE :q')
                ->setParameter('q', '%'.$q.'%');
        }

        return $qb;
    }

    // Demandes soumises mais email pas encore vérifié — normalement vide depuis
    // que la vérification email est désactivée (emailVerifie mis à true à la soumission).
    public function queryBuilderNonVerifiees(): QueryBuilder
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.emailVerifie = false')
            ->setParameter('statut', DemandeInscription::STATUT_EN_ATTENTE)
            ->orderBy('d.createdAt', 'ASC');
    }
}
