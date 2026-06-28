<?php

namespace App\Entity;

use App\Repository\RubriqueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

// Deuxième niveau de la hiérarchie : Domaine → Rubrique → Thème → Protocole.
// Une rubrique peut appartenir à plusieurs domaines (ManyToMany) et contient plusieurs thèmes (OneToMany).
#[ORM\Entity(repositoryClass: RubriqueRepository::class)]
#[ORM\Index(columns: ['nom'])]
class Rubrique
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

    // Rubrique est le côté propriétaire du ManyToMany : c'est elle qui gère la table de liaison `rubrique_domaine`.
    /** @var Collection<int, Domaine> */
    #[ORM\ManyToMany(targetEntity: Domaine::class, inversedBy: 'rubriques')]
    #[ORM\JoinTable(name: 'rubrique_domaine')]
    private Collection $domaines;

    // cascade: remove → supprimer une rubrique supprime automatiquement ses thèmes (et en cascade leurs protocoles).
    /** @var Collection<int, Theme> */
    #[ORM\OneToMany(targetEntity: Theme::class, mappedBy: 'rubrique', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['nom' => 'ASC'])]
    private Collection $themes;

    public function __construct()
    {
        $this->domaines = new ArrayCollection();
        $this->themes = new ArrayCollection();
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

    /** @return Collection<int, Domaine> */
    public function getDomaines(): Collection
    {
        return $this->domaines;
    }

    // Côté propriétaire du ManyToMany : on n'appelle pas domaine->addRubrique() ici
    // pour éviter une boucle infinie (Domaine->addRubrique appelle déjà Rubrique->addDomaine).
    public function addDomaine(Domaine $domaine): static
    {
        if (!$this->domaines->contains($domaine)) {
            $this->domaines->add($domaine);
        }

        return $this;
    }

    public function removeDomaine(Domaine $domaine): static
    {
        $this->domaines->removeElement($domaine);

        return $this;
    }

    /** @return Collection<int, Theme> */
    public function getThemes(): Collection
    {
        return $this->themes;
    }

    // Synchronisation bidirectionnelle : on met aussi à jour theme->rubrique.
    public function addTheme(Theme $theme): static
    {
        if (!$this->themes->contains($theme)) {
            $this->themes->add($theme);
            $theme->setRubrique($this);
        }

        return $this;
    }

    public function removeTheme(Theme $theme): static
    {
        if ($this->themes->removeElement($theme)) {
            if ($theme->getRubrique() === $this) {
                $theme->setRubrique(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom;
    }
}
