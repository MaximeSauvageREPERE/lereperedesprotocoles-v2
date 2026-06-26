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

    /** Demandes dont l'email est vérifié et en attente de traitement admin */
    public function findEnAttentePourAdmin(): array
    {
        return $this->queryBuilderEnAttentePourAdmin()->getQuery()->getResult();
    }

    /** Demandes soumises mais email non encore vérifié */
    public function findNonVerifiees(): array
    {
        return $this->queryBuilderNonVerifiees()->getQuery()->getResult();
    }

    public function queryBuilderEnAttentePourAdmin(): QueryBuilder
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.emailVerifie = true')
            ->setParameter('statut', DemandeInscription::STATUT_EN_ATTENTE)
            ->orderBy('d.createdAt', 'ASC');
    }

    public function queryBuilderNonVerifiees(): QueryBuilder
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.statut = :statut')
            ->andWhere('d.emailVerifie = false')
            ->setParameter('statut', DemandeInscription::STATUT_EN_ATTENTE)
            ->orderBy('d.createdAt', 'ASC');
    }
}
