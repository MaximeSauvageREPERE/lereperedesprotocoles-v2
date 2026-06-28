<?php

namespace App\Entity;

use App\Repository\DemandeInscriptionRepository;
use Doctrine\ORM\Mapping as ORM;

// Représente une demande d'accès soumise via /inscription.
// Elle passe par trois statuts : en_attente → approuvee ou refusee.
// À l'approbation, un User est créé et lié à cette demande.
#[ORM\Entity(repositoryClass: DemandeInscriptionRepository::class)]
// Index BDD pour accélérer la recherche dans la liste admin (filtre par nom/prénom/email).
#[ORM\Index(columns: ['nom'])]
#[ORM\Index(columns: ['prenom'])]
#[ORM\Index(columns: ['email'])]
class DemandeInscription
{
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_APPROUVEE  = 'approuvee';
    public const STATUT_REFUSEE    = 'refusee';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $email = '';

    #[ORM\Column(length: 100)]
    private string $prenom = '';

    #[ORM\Column(length: 100)]
    private string $nom = '';

    #[ORM\ManyToOne(inversedBy: 'demandesInscription')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Profession $profession = null;

    // Mot de passe haché à l'inscription — copié tel quel vers User lors de l'approbation
    // pour ne pas redemander le mot de passe à l'utilisateur.
    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(length: 20)]
    private string $statut = self::STATUT_EN_ATTENTE;

    // Token à usage unique envoyé par email pour confirmer l'adresse.
    // Null quand la vérification email est désactivée ou après utilisation.
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $token = null;

    // Date d'expiration du token — au-delà, le lien de confirmation n'est plus valide.
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $tokenExpiresAt = null;

    // Motif renseigné par l'admin lors d'un refus — inclus dans l'email envoyé au candidat.
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motifRejet = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    // Date à laquelle l'admin a traité la demande (approbation ou refus).
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $traiteeAt = null;

    // true dès que l'email est confirmé (ou immédiatement si la vérification est désactivée).
    // Seules les demandes avec emailVerifie = true sont visibles par l'admin.
    #[ORM\Column]
    private bool $emailVerifie = false;

    // Lien vers le User créé après approbation. SET NULL si le User est supprimé
    // pour conserver l'historique de la demande.
    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $utilisateur = null;

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

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->tokenExpiresAt;
    }

    public function setTokenExpiresAt(?\DateTimeImmutable $tokenExpiresAt): static
    {
        $this->tokenExpiresAt = $tokenExpiresAt;

        return $this;
    }

    // Retourne true si le token existe et n'est pas encore expiré.
    public function isTokenValide(): bool
    {
        return null !== $this->tokenExpiresAt && $this->tokenExpiresAt > new \DateTimeImmutable();
    }

    public function getMotifRejet(): ?string
    {
        return $this->motifRejet;
    }

    public function setMotifRejet(?string $motifRejet): static
    {
        $this->motifRejet = $motifRejet;

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

    public function getTraiteeAt(): ?\DateTimeImmutable
    {
        return $this->traiteeAt;
    }

    public function setTraiteeAt(?\DateTimeImmutable $traiteeAt): static
    {
        $this->traiteeAt = $traiteeAt;

        return $this;
    }

    public function isEmailVerifie(): bool
    {
        return $this->emailVerifie;
    }

    public function setEmailVerifie(bool $emailVerifie): static
    {
        $this->emailVerifie = $emailVerifie;

        return $this;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }
}
