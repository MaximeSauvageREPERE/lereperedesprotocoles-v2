<?php

namespace App\Repository;

use App\Entity\DemandeInscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des demandes d'inscription.
 *
 * @extends ServiceEntityRepository<DemandeInscription>
 */
class DemandeInscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeInscription::class);
    }

    /**
     * Retourne les demandes en attente avec email vérifié, sans pagination.
     *
     * @return DemandeInscription[]
     */
    public function findEnAttentePourAdmin(): array
    {
        return $this->queryBuilderEnAttentePourAdmin()->getQuery()->getResult();
    }

    /**
     * Retourne les demandes dont l'email n'est pas encore vérifié, sans pagination.
     * Normalement vide depuis la désactivation de la vérification email.
     *
     * @return DemandeInscription[]
     */
    public function findNonVerifiees(): array
    {
        return $this->queryBuilderNonVerifiees()->getQuery()->getResult();
    }

    /**
     * Retourne un QueryBuilder pour les demandes en attente avec email vérifié.
     *
     * Retourne un QueryBuilder (et non un tableau) pour que KnpPaginator puisse
     * ajouter dynamiquement LIMIT/OFFSET et compter le total sans charger tous les objets en mémoire.
     * Triées des plus anciennes aux plus récentes (ordre d'arrivée pour l'admin).
     *
     * @param string $q Terme de recherche sur nom, prénom et email (chaîne vide pour tout retourner)
     */
    public function queryBuilderEnAttentePourAdmin(string $q = ''): QueryBuilder
    {
        $qb = $this->createQueryBuilder('d')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.emailVerifie = true')
            ->setParameter('statut', DemandeInscription::STATUT_EN_ATTENTE)
            ->orderBy('d.createdAt', 'ASC');

        if ('' !== $q) {
            $qb->andWhere('d.nom LIKE :q OR d.prenom LIKE :q OR d.email LIKE :q')
                ->setParameter('q', '%'.$q.'%');
        }

        return $qb;
    }

    /**
     * Retourne un QueryBuilder pour les demandes soumises mais avec email non vérifié.
     * Normalement vide depuis que la vérification email est désactivée (emailVerifie mis à true à la soumission).
     */
    public function queryBuilderNonVerifiees(): QueryBuilder
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.emailVerifie = false')
            ->setParameter('statut', DemandeInscription::STATUT_EN_ATTENTE)
            ->orderBy('d.createdAt', 'ASC');
    }
}
