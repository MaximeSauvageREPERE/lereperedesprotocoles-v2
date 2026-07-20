<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Affiche la page d'accueil publique de l'application.
 */
class HomeController extends AbstractController
{
    /**
     * Page d'accueil — redirige les modérateurs/admins vers leur tableau de bord.
     */
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }
        if ($this->isGranted('ROLE_MODERATEUR')) {
            return $this->redirectToRoute('moderateur_dashboard');
        }

        return $this->render('home/index.html.twig');
    }
}
