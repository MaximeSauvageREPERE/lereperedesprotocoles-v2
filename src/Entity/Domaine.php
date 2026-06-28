<?php

namespace App\Entity;

use App\Repository\DomaineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

// Premier niveau de la hiérarchie de navigation : Domaine → Rubrique → Thème → Protocole.
// Un domaine regroupe plusieurs rubriques via une relation ManyToMany
// (une rubrique peut apparaître dans plusieurs domaines).
#[ORM\Entity(repositoryClass: DomaineRepository::class)]
#[ORM\Index(columns: ['nom'])]
class Domaine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $nom = '';

    #[ORM\Column(length: 255, unique: true)]
    private string $slug = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // Côté inverse du ManyToMany (Rubrique est le propriétaire).
    // OrderBy appliqué automatiquement à chaque chargement de la collection.
    /** @var Collection<int, Rubrique> */
    #[ORM\ManyToMany(targetEntity: Rubrique::class, mappedBy: 'domaines')]
    #[ORM\OrderBy(['nom' => 'ASC'])]
    private Collection $rubriques;

    public function __construct()
    {
        $this->rubriques = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /** @return Collection<int, Rubrique> */
    public function getRubriques(): Collection
    {
        return $this->rubriques;
    }

    // La synchronisation bidirectionnelle est gérée ici : ajouter une rubrique au domaine
    // appelle aussi rubrique->addDomaine($this) pour garder les deux côtés cohérents.
    public function addRubrique(Rubrique $rubrique): static
    {
        if (!$this->rubriques->contains($rubrique)) {
            $this->rubriques->add($rubrique);
            $rubrique->addDomaine($this);
        }

        return $this;
    }

    public function removeRubrique(Rubrique $rubrique): static
    {
        if ($this->rubriques->removeElement($rubrique)) {
            $rubrique->removeDomaine($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom;
    }
}
