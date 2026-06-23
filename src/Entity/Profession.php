<?php

namespace App\Entity;

use App\Repository\ProfessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfessionRepository::class)]
class Profession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $nom = null;

    #[ORM\Column(length: 150, unique: true)]
    private ?string $slug = null;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'profession')]
    private Collection $users;

    #[ORM\OneToMany(targetEntity: DemandeInscription::class, mappedBy: 'profession')]
    private Collection $demandesInscription;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->demandesInscription = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getSlug(): ?string
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

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}
