<?php

namespace App\Tests\Unit\Entity;

use App\Entity\DemandeInscription;
use PHPUnit\Framework\TestCase;

class DemandeInscriptionTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $demande = new DemandeInscription();

        $this->assertNull($demande->getId());
        $this->assertSame(DemandeInscription::STATUT_EN_ATTENTE, $demande->getStatut());
        $this->assertFalse($demande->isEmailVerifie());
        $this->assertNull($demande->getToken());
        $this->assertNull($demande->getTokenExpiresAt());
        $this->assertNull($demande->getMotifRejet());
        $this->assertNull($demande->getTraiteeAt());
        $this->assertNull($demande->getUtilisateur());
        $this->assertInstanceOf(\DateTimeImmutable::class, $demande->getCreatedAt());
    }

    public function testStatutConstants(): void
    {
        $this->assertSame('en_attente', DemandeInscription::STATUT_EN_ATTENTE);
        $this->assertSame('approuvee', DemandeInscription::STATUT_APPROUVEE);
        $this->assertSame('refusee', DemandeInscription::STATUT_REFUSEE);
    }

    public function testIsTokenValideReturnsTrueWhenFutureExpiry(): void
    {
        $demande = new DemandeInscription();
        $demande->setToken('abc123');
        $demande->setTokenExpiresAt(new \DateTimeImmutable('+1 hour'));

        $this->assertTrue($demande->isTokenValide());
    }

    public function testIsTokenValideReturnsFalseWhenExpired(): void
    {
        $demande = new DemandeInscription();
        $demande->setToken('abc123');
        $demande->setTokenExpiresAt(new \DateTimeImmutable('-1 second'));

        $this->assertFalse($demande->isTokenValide());
    }

    public function testIsTokenValideReturnsFalseWhenNoExpiryDate(): void
    {
        $demande = new DemandeInscription();
        $demande->setToken('abc123');

        $this->assertFalse($demande->isTokenValide());
    }

    public function testStatutCanBeChangedToApprouvee(): void
    {
        $demande = new DemandeInscription();
        $demande->setStatut(DemandeInscription::STATUT_APPROUVEE);

        $this->assertSame(DemandeInscription::STATUT_APPROUVEE, $demande->getStatut());
    }

    public function testStatutCanBeChangedToRefusee(): void
    {
        $demande = new DemandeInscription();
        $demande->setStatut(DemandeInscription::STATUT_REFUSEE);

        $this->assertSame(DemandeInscription::STATUT_REFUSEE, $demande->getStatut());
    }
}
