<?php

namespace App\Controller\Moderateur;

use App\Repository\DomaineRepository;
use App\Repository\ProtocoleRepository;
use App\Repository\RubriqueRepository;
use App\Repository\ThemeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Tableau de bord du modérateur — aperçu du contenu et des protocoles à compléter.
 */
#[IsGranted('ROLE_MODERATEUR')]
#[Route('/moderateur')]
class DashboardController extends AbstractController
{
    /**
     * Affiche les compteurs de contenu et les protocoles récemment modifiés.
     */
    #[Route('/dashboard', name: 'moderateur_dashboard')]
    public function index(
        ProtocoleRepository $protocoleRepo,
        DomaineRepository $domaineRepo,
        RubriqueRepository $rubriqueRepo,
        ThemeRepository $themeRepo,
    ): Response {
        return $this->render('moderateur/dashboard.html.twig', [
            'nbSansPdf' => $protocoleRepo->count(['pdfFilename' => null]),
            'nbSansImage' => $protocoleRepo->count(['imageFilename' => null]),
            'nbProtocoles' => $protocoleRepo->count([]),
            'nbDomaines' => $domaineRepo->count([]),
            'nbRubriques' => $rubriqueRepo->count([]),
            'nbThemes' => $themeRepo->count([]),
            'derniersProtocoles' => $protocoleRepo->findDerniersAjoutes(5),
        ]);
    }
}
