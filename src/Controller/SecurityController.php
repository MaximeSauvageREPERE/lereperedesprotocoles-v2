<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Gère l'authentification : affichage du formulaire de connexion et déconnexion.
 *
 * La route /logout est interceptée par le firewall Symfony avant d'atteindre le controller —
 * la méthode logout() ne s'exécute donc jamais.
 *
 * @package App\Controller
 */
class SecurityController extends AbstractController
{
    /**
     * Affiche le formulaire de connexion avec la dernière erreur et le dernier email saisi.
     * Les headers no-cache empêchent le navigateur d'afficher la page connectée après logout.
     */
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $response = $this->render('security/login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'last_username' => $authenticationUtils->getLastUsername(),
        ]);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }

    /**
     * La déconnexion est gérée entièrement par le firewall Symfony — ce code n'est jamais atteint.
     *
     * @throws \LogicException toujours, pour satisfaire le type de retour `never`
     */
    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('This method should never be reached.');
    }
}
