<?php

namespace App\Controller\Admin;

use App\Entity\DemandeInscription;
use App\Repository\DemandeInscriptionRepository;
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
     * Affiche les statistiques globales : demandes en attente, utilisateurs et derniers inscrits.
     */
    #[Route('/dashboard', name: 'admin_dashboard')]
    public function index(
        DemandeInscriptionRepository $demandeRepo,
        UserRepository $userRepo,
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'nbDemandesEnAttente' => $demandeRepo->count(['statut' => DemandeInscription::STATUT_EN_ATTENTE, 'emailVerifie' => true]),
            'nbUtilisateurs' => $userRepo->count([]),
            'derniersUtilisateurs' => $userRepo->findDerniersInscrits(5),
        ]);
    }
}
