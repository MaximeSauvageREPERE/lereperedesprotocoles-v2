<?php

namespace App\Tests\Functional;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityTest extends WebTestCase
{
    public function testLoginFormContainsRequiredFields(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
        $this->assertSelectorExists('input[name="_csrf_token"]');
    }

    public function testLoginWithValidCredentials(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $csrfToken = $crawler->filter('input[name="_csrf_token"]')->attr('value');

        $client->request('POST', '/login', [
            '_username' => 'user@test.fr',
            '_password' => 'utilisateur',
            '_csrf_token' => $csrfToken,
        ]);

        $this->assertResponseStatusCodeSame(302);
        $client->followRedirect();
        $this->assertRouteSame('app_home');
    }

    public function testLoginWithWrongPasswordStaysOnLoginPage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        $csrfToken = $crawler->filter('input[name="_csrf_token"]')->attr('value');

        $client->request('POST', '/login', [
            '_username' => 'user@test.fr',
            '_password' => 'mauvais_mot_de_passe',
            '_csrf_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorExists('.bg-red-50');
    }

    public function testAlreadyLoggedInUserIsRedirectedFromLogin(): void
    {
        $client = static::createClient();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'user@test.fr']);

        $client->loginUser($user);
        $client->request('GET', '/login');

        $this->assertResponseStatusCodeSame(302);
        $client->followRedirect();
        $this->assertRouteSame('app_home');
    }
}
