<?php

namespace App\Controller\Admin;

use App\Entity\DemandeInscription;
use App\Repository\DemandeInscriptionRepository;
use App\Repository\DomaineRepository;
use App\Repository\ProtocoleRepository;
use App\Repository\RubriqueRepository;
use App\Repository\ThemeRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Tableau de bord de l'administrateur — vue d'ensemble de l'activité de la plateforme.
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class DashboardController extends AbstractController
{
    /**
     * Affiche les statistiques globales : demandes en attente, utilisateurs, contenu.
     */
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(
        DemandeInscriptionRepository $demandeRepo,
        UserRepository $userRepo,
        ProtocoleRepository $protocoleRepo,
        DomaineRepository $domaineRepo,
        RubriqueRepository $rubriqueRepo,
        ThemeRepository $themeRepo,
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'nbDemandesEnAttente' => $demandeRepo->count(['statut' => DemandeInscription::STATUT_EN_ATTENTE, 'emailVerifie' => true]),
            'nbUtilisateurs'      => $userRepo->count([]),
            'nbProtocoles'        => $protocoleRepo->count([]),
            'nbDomaines'          => $domaineRepo->count([]),
            'nbRubriques'         => $rubriqueRepo->count([]),
            'nbThemes'            => $themeRepo->count([]),
        ]);
    }
}
