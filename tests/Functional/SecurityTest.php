<?php

namespace App\Tests\Functional;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityTest extends WebTestCase
{
    public function testLoginFormContainsRequiredFields(): void
    {
        // Vérifie que les noms de champs Symfony (_username, _password, _csrf_token)
        // sont bien présents — le firewall les attend exactement sous ces noms
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('input[name="_username"]');
        $this->assertSelectorExists('input[name="_password"]');
        $this->assertSelectorExists('input[name="_csrf_token"]');
    }

    public function testLoginWithValidCredentials(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/login');

        // Le token CSRF est lu depuis le formulaire rendu (valeur générée par le serveur)
        $csrfToken = $crawler->filter('input[name="_csrf_token"]')->attr('value');

        $client->request('POST', '/login', [
            '_username' => 'user@test.fr',
            '_password' => 'utilisateur',
            '_csrf_token' => $csrfToken,
        ]);

        // Le firewall redirige en 302 vers la page protégée après succès
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

        // Échec → redirection vers /login, puis le bloc d'erreur .bg-red-50 est visible
        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorExists('.bg-red-50');
    }

    public function testLoginThrottlingBlocksAfterFiveFailedAttempts(): void
    {
        $client = static::createClient();

        // Email unique via uniqid() pour isoler ce test des autres runs (compteur de rate limiting par email+IP)
        $email = 'throttle_'.uniqid().'@example.com';

        for ($i = 0; $i < 5; ++$i) {
            $crawler = $client->request('GET', '/login');
            $csrfToken = $crawler->filter('input[name="_csrf_token"]')->attr('value');
            $client->request('POST', '/login', [
                '_username' => $email,
                '_password' => 'wrong',
                '_csrf_token' => $csrfToken,
            ]);
        }

        // La 6e tentative doit être bloquée (rate limiting : 5 tentatives / minute)
        $crawler = $client->request('GET', '/login');
        $csrfToken = $crawler->filter('input[name="_csrf_token"]')->attr('value');
        $client->request('POST', '/login', [
            '_username' => $email,
            '_password' => 'wrong',
            '_csrf_token' => $csrfToken,
        ]);

        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        // Le message de blocage contient "minute" (traduction Symfony du rate limiter)
        $this->assertSelectorTextContains('.bg-red-50', 'minute');
    }

    public function testAlreadyLoggedInUserIsRedirectedFromLogin(): void
    {
        $client = static::createClient();
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => 'user@test.fr']);

        // loginUser() authentifie directement sans soumettre le formulaire (shortcut de test)
        $client->loginUser($user);
        $client->request('GET', '/login');

        $this->assertResponseStatusCodeSame(302);
        $client->followRedirect();
        $this->assertRouteSame('app_home');
    }
}
