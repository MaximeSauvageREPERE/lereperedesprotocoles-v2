<?php

namespace App\Tests\Functional;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RoleAccessTest extends WebTestCase
{
    private function loginAs(string $email): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        $client->loginUser($user);

        return $client;
    }

    public function testUserCanAccessParcourir(): void
    {
        $client = $this->loginAs('user@test.fr');
        $client->request('GET', '/parcourir');
        $this->assertResponseIsSuccessful();
    }

    public function testUserCannotAccessModerateurPages(): void
    {
        $client = $this->loginAs('user@test.fr');
        $client->request('GET', '/moderateur/domaines');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserCannotAccessAdminPages(): void
    {
        $client = $this->loginAs('user@test.fr');
        $client->request('GET', '/admin/utilisateurs');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testModerateurCanAccessModerateurPages(): void
    {
        $client = $this->loginAs('modo@test.fr');
        $client->request('GET', '/moderateur/domaines');
        $this->assertResponseIsSuccessful();
    }

    public function testModerateurCannotAccessAdminPages(): void
    {
        $client = $this->loginAs('modo@test.fr');
        $client->request('GET', '/admin/utilisateurs');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminCanAccessAdminPages(): void
    {
        $client = $this->loginAs('admin@test.fr');
        $client->request('GET', '/admin/utilisateurs');
        $this->assertResponseIsSuccessful();
    }

    public function testAdminCanAccessModerateurPagesViaHierarchy(): void
    {
        $client = $this->loginAs('admin@test.fr');
        $client->request('GET', '/moderateur/domaines');
        $this->assertResponseIsSuccessful();
    }
}
