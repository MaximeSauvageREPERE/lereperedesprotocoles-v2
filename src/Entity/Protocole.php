<?php

namespace App\Entity;

use App\Repository\ProtocoleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

// Feuille de la hiérarchie : Domaine → Rubrique → Thème → Protocole.
// Supporte l'upload de fichiers (PDF + image) via VichUploaderBundle.
#[ORM\Entity(repositoryClass: ProtocoleRepository::class)]
// HasLifecycleCallbacks active les callbacks ORM comme #[ORM\PreUpdate] ci-dessous.
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['titre'])]
// Uploadable indique à VichUploader que cette entité a des champs fichiers à gérer.
#[Vich\Uploadable]
class Protocole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $titre = '';

    #[ORM\Column(length: 255, unique: true)]
    private string $slug = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // Objet File PHP (non persisté en BDD) — VichUploader lit ce champ pour déplacer le fichier uploadé.
    #[Vich\UploadableField(mapping: 'protocole_pdf', fileNameProperty: 'pdfFilename')]
    private ?File $pdfFile = null;

    // Nom du fichier généré par VichUploader et stocké en BDD (ex: "abc123.pdf").
    // Le fichier physique est dans public/uploads/protocoles/pdf/.
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfFilename = null;

    #[Vich\UploadableField(mapping: 'protocole_image', fileNameProperty: 'imageFilename')]
    private ?File $imageFile = null;

    // Nom du fichier image stocké en BDD — fichier physique dans public/uploads/protocoles/images/.
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageFilename = null;

    #[ORM\ManyToOne(inversedBy: 'protocoles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Theme $theme = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    // updatedAt est mis à jour automatiquement à chaque modification via #[ORM\PreUpdate]
    // et aussi manuellement dans setPdfFile/setImageFile pour que VichUploader détecte le changement.
    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Callback Doctrine déclenché automatiquement avant chaque UPDATE SQL.
    // Garantit que updatedAt reflète toujours la dernière modification de l'entité.
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

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

    public function getPdfFile(): ?File
    {
        return $this->pdfFile;
    }

    public function setPdfFile(?File $pdfFile): static
    {
        $this->pdfFile = $pdfFile;

        // VichUploader détecte un nouveau fichier uniquement si updatedAt change —
        // on le force ici pour déclencher le traitement de l'upload lors du flush.
        if (null !== $pdfFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getPdfFilename(): ?string
    {
        return $this->pdfFilename;
    }

    public function setPdfFilename(?string $pdfFilename): static
    {
        $this->pdfFilename = $pdfFilename;

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile): static
    {
        $this->imageFile = $imageFile;

        // Même raison que setPdfFile : force la détection du changement par VichUploader.
        if (null !== $imageFile) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(?string $imageFilename): static
    {
        $this->imageFilename = $imageFilename;

        return $this;
    }

    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    public function setTheme(?Theme $theme): static
    {
        $this->theme = $theme;

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

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->titre;
    }
}
