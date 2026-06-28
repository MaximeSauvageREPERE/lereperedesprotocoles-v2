<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Vérifie l'état du compte utilisateur avant et après l'authentification.
 * Déclaré dans security.yaml via : user_checker: App\Security\UserChecker.
 */
class UserChecker implements UserCheckerInterface
{
    /**
     * Appelé par le firewall AVANT la vérification du mot de passe.
     * Bloque les comptes inactifs (isVerified = false) avec un message lisible.
     * CustomUserMessageAuthenticationException affiche le message tel quel dans le template login
     * (contrairement à AuthenticationException qui affiche une clé de traduction).
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAuthenticationException('Votre compte n\'est pas encore activé. Vérifiez votre email ou contactez un administrateur.');
        }
    }

    // Appelé après l'authentification réussie — aucune vérification supplémentaire nécessaire.
    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
    }
}
