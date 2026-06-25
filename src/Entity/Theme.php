<?php

namespace App\Entity;

use App\Repository\ThemeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ThemeRepository::class)]
class Theme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $nom = '';

    #[ORM\Column(length: 255, unique: true)]
    private string $slug = '';

    #[ORM\ManyToOne(inversedBy: 'themes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Rubrique $rubrique = null;

    /** @var Collection<int, Protocole> */
    #[ORM\OneToMany(targetEntity: Protocole::class, mappedBy: 'theme', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['titre' => 'ASC'])]
    private Collection $protocoles;

    public function __construct()
    {
        $this->protocoles = new ArrayCollection();
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

    public function getRubrique(): ?Rubrique
    {
        return $this->rubrique;
    }

    public function setRubrique(?Rubrique $rubrique): static
    {
        $this->rubrique = $rubrique;

        return $this;
    }

    /** @return Collection<int, Protocole> */
    public function getProtocoles(): Collection
    {
        return $this->protocoles;
    }

    public function addProtocole(Protocole $protocole): static
    {
        if (!$this->protocoles->contains($protocole)) {
            $this->protocoles->add($protocole);
            $protocole->setTheme($this);
        }

        return $this;
    }

    public function removeProtocole(Protocole $protocole): static
    {
        if ($this->protocoles->removeElement($protocole)) {
            if ($protocole->getTheme() === $this) {
                $protocole->setTheme(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nom;
    }
}
