<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Évite d'afficher le formulaire à un utilisateur déjà connecté.
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $response = $this->render('security/login.html.twig', [
            // Récupère l'erreur de la tentative précédente (mauvais mot de passe, etc.)
            'error' => $authenticationUtils->getLastAuthenticationError(),
            // Pré-remplit le champ email avec ce que l'utilisateur avait saisi.
            'last_username' => $authenticationUtils->getLastUsername(),
        ]);

        // Empêche le navigateur de mettre la page de login en cache,
        // sinon le bouton "Précédent" après logout peut afficher la page connectée.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        // Le firewall Symfony intercepte cette URL avant d'atteindre le controller.
        // Ce code ne s'exécute jamais — l'exception est là pour satisfaire le type de retour.
        throw new \LogicException('This method should never be reached.');
    }
}
