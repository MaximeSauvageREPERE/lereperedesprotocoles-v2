<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\UserChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserCheckerTest extends TestCase
{
    private UserChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new UserChecker();
    }

    public function testCheckPreAuthSkipsNonAppUser(): void
    {
        // createStub() au lieu de createMock() : pas d'expectation, juste un faux UserInterface
        // UserChecker doit ignorer silencieusement les utilisateurs non-App\Entity\User
        $user = $this->createStub(UserInterface::class);

        $this->checker->checkPreAuth($user);
        $this->addToAssertionCount(1); // confirme que l'appel s'est terminé sans exception
    }

    public function testCheckPreAuthThrowsWhenUserNotVerified(): void
    {
        $user = new User();
        $user->setIsVerified(false);

        $this->expectException(CustomUserMessageAuthenticationException::class);

        $this->checker->checkPreAuth($user);
    }

    public function testCheckPreAuthAllowsVerifiedUser(): void
    {
        $user = new User();
        $user->setIsVerified(true);

        $this->checker->checkPreAuth($user);
        $this->addToAssertionCount(1);
    }

    public function testCheckPostAuthDoesNothing(): void
    {
        // checkPostAuth() est intentionnellement vide : aucune vérification post-authentification
        $user = new User();

        $this->checker->checkPostAuth($user);
        $this->addToAssertionCount(1);
    }
}
