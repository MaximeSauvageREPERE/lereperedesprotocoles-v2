<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Repository des utilisateurs.
 *
 * Implémente PasswordUpgraderInterface pour que Symfony puisse rehacher automatiquement
 * le mot de passe lors de la connexion si l'algorithme ou le coût bcrypt a changé.
 *
 * @extends ServiceEntityRepository<User>
 * @package App\Repository
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Retourne un QueryBuilder pour la liste paginée des utilisateurs (interface admin).
     *
     * La recherche porte simultanément sur le nom, le prénom et l'email.
     *
     * @param string $q Terme de recherche (chaîne vide pour tout retourner)
     */
    public function queryBuilderSearch(string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('u')->orderBy('u.nom', 'ASC');
        if ('' !== $q) {
            $qb->andWhere('u.nom LIKE :q OR u.prenom LIKE :q OR u.email LIKE :q')
                ->setParameter('q', '%'.$q.'%');
        }

        return $qb;
    }

    /**
     * Appelée automatiquement par Symfony après une connexion réussie si le hash stocké
     * en base est obsolète (algorithme ou coût différent de la configuration actuelle).
     *
     * @throws \InvalidArgumentException si $user n'est pas une instance de User
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }
}
