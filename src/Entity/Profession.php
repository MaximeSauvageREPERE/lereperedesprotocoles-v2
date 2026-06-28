<?php

namespace App\Entity;

use App\Repository\ProfessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

// Référentiel des professions médicales disponibles à l'inscription.
// Partagée par User et DemandeInscription — on ne peut pas supprimer une profession
// qui a des utilisateurs ou des demandes rattachés (contrôle dans ProfessionController).
#[ORM\Entity(repositoryClass: ProfessionRepository::class)]
#[ORM\Index(columns: ['nom'])]
class Profession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $nom = '';

    // Identifiant URL unique (ex: "medecin-generaliste") — généré depuis le nom dans le controller.
    #[ORM\Column(length: 150, unique: true)]
    private string $slug = '';

    // Côté inverse de la relation : liste des utilisateurs ayant cette profession.
    // Chargé en lazy par défaut — accédé uniquement quand on appelle getUsers().
    /** @var Collection<int, User> */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'profession')]
    private Collection $users;

    /** @var Collection<int, DemandeInscription> */
    #[ORM\OneToMany(targetEntity: DemandeInscription::class, mappedBy: 'profession')]
    private Collection $demandesInscription;

    public function __construct()
    {
        // Doctrine exige des ArrayCollection vides plutôt que des tableaux PHP pour les relations.
        $this->users = new ArrayCollection();
        $this->demandesInscription = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    /** @return Collection<int, User> */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /** @return Collection<int, DemandeInscription> */
    public function getDemandesInscription(): Collection
    {
        return $this->demandesInscription;
    }

    // Utilisé par Symfony pour afficher le nom dans les selects de formulaires.
    public function __toString(): string
    {
        return $this->nom;
    }
}
