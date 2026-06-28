<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $user = new User();

        $this->assertNull($user->getId());
        $this->assertSame('', $user->getEmail());
        $this->assertSame('', $user->getPassword());
        $this->assertSame('', $user->getPrenom());
        $this->assertSame('', $user->getNom());
        // isVerified vaut false par défaut — l'admin doit approuver la demande
        $this->assertFalse($user->isVerified());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
    }

    public function testGetRolesAlwaysIncludesRoleUser(): void
    {
        // ROLE_USER n'est pas stocké en BDD : getRoles() l'ajoute systématiquement
        $user = new User();

        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testGetRolesWithAdminRole(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $roles = $user->getRoles();

        // Un admin reçoit ROLE_ADMIN (stocké) + ROLE_USER (ajouté par getRoles())
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testGetRolesNoDuplicates(): void
    {
        // Si ROLE_USER est déjà dans les rôles stockés, getRoles() ne le duplique pas
        $user = new User();
        $user->setRoles(['ROLE_USER']);

        $roles = $user->getRoles();

        $this->assertCount(1, $roles);
    }

    public function testGetUserIdentifierReturnsEmail(): void
    {
        // getUserIdentifier() est l'identifiant Symfony utilisé par le firewall (remplace getUsername())
        $user = new User();
        $user->setEmail('test@example.fr');

        $this->assertSame('test@example.fr', $user->getUserIdentifier());
    }

    public function testSettersReturnStatic(): void
    {
        // Les setters retournent $this pour permettre le chaînage fluent
        $user = new User();

        $this->assertSame($user, $user->setEmail('a@b.fr'));
        $this->assertSame($user, $user->setPrenom('Jean'));
        $this->assertSame($user, $user->setNom('Dupont'));
        $this->assertSame($user, $user->setPassword('hash'));
        $this->assertSame($user, $user->setIsVerified(true));
    }

    public function testEraseCredentialsIsNoop(): void
    {
        // eraseCredentials() ne doit rien effacer : le hash en BDD est déjà le stockage permanent
        $user = new User();
        $user->setPassword('secret_hash');
        $user->eraseCredentials();

        $this->assertSame('secret_hash', $user->getPassword());
    }
}
