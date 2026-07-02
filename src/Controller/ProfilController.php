<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Affiche la page de profil de l'utilisateur connecté.
 */
#[Route('/profil')]
#[IsGranted('ROLE_USER')]
class ProfilController extends AbstractController
{
    /**
     * Page de profil — affiche les informations du compte connecté.
     */
    #[Route('', name: 'profil_index')]
    public function index(): Response
    {
        return $this->render('profil/index.html.twig');
    }
}
