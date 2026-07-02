<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Entité représentant un utilisateur authentifié de l'application.
 *
 * Implémente UserInterface et PasswordAuthenticatedUserInterface pour l'intégration
 * avec le firewall Symfony. L'email sert d'identifiant unique (getUserIdentifier).
 * Le nom de table est échappé en backticks car `user` est un mot réservé en SQL.
 *
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
// Index BDD sur nom et prénom pour accélérer les recherches dans la liste admin.
#[ORM\Index(columns: ['nom'])]
#[ORM\Index(columns: ['prenom'])]
#[UniqueEntity(fields: ['email'], message: 'Un compte avec cet email existe déjà.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email = '';

    /**
     * Tableau des rôles Symfony stockés en BDD (ex: ['ROLE_ADMIN']).
     * ROLE_USER est ajouté automatiquement dans {@see getRoles()} — il n'est jamais stocké en doublon.
     *
     * @var list<string>
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * Hash bcrypt du mot de passe — jamais le mot de passe en clair.
     *
     * @var string
     */
    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(length: 100)]
    private string $prenom = '';

    #[ORM\Column(length: 100)]
    private string $nom = '';

    /**
     * Tout utilisateur doit avoir une profession (nullable: false en BDD).
     *
     * @var Profession|null
     */
    #[ORM\ManyToOne(inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Profession $profession = null;

    /**
     * Indique si le compte a été activé par un administrateur.
     * Vérifié par {@see \App\Security\UserChecker} avant l'authentification.
     *
     * @var bool
     */
    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Identifiant utilisé par Symfony pour retrouver l'utilisateur en session.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * Retourne les rôles de l'utilisateur en garantissant que ROLE_USER est toujours présent.
     * Symfony exige que tout utilisateur connecté ait au moins ROLE_USER.
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Appelée par Symfony après l'authentification pour effacer les données sensibles en mémoire.
     * Rien à faire ici car le mot de passe en clair n'est jamais stocké sur l'objet.
     *
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getProfession(): ?Profession
    {
        return $this->profession;
    }

    public function setProfession(?Profession $profession): static
    {
        $this->profession = $profession;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
