<?php

namespace App\Controller;

use App\Entity\DemandeInscription;
use App\Entity\User;
use App\Form\InscriptionType;
use App\Repository\DemandeInscriptionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class InscriptionController extends AbstractController
{
    #[Route('/inscription', name: 'app_inscription')]
    public function formulaire(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserRepository $userRepository,
        DemandeInscriptionRepository $demandeRepository,
    ): Response {
        // Redirige vers l'accueil si l'utilisateur est déjà connecté.
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $demande = new DemandeInscription();
        $form = $this->createForm(InscriptionType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Bloque les doublons : un compte existe déjà avec cet email.
            if ($userRepository->findOneBy(['email' => $demande->getEmail()])) {
                $this->addFlash('error', 'Un compte existe déjà avec cette adresse email.');

                return $this->redirectToRoute('app_inscription');
            }

            // Bloque les doublons : une demande est déjà en attente pour cet email.
            $existante = $demandeRepository->findOneBy(['email' => $demande->getEmail(), 'statut' => DemandeInscription::STATUT_EN_ATTENTE]);
            if ($existante) {
                $this->addFlash('error', 'Une demande est déjà en cours pour cette adresse email.');

                return $this->redirectToRoute('app_inscription');
            }

            // Le hasher a besoin d'un objet User pour appliquer l'algorithme défini dans security.yaml.
            // On en crée un temporaire — seul le hash résultant est conservé dans la demande.
            $tempUser = new User();
            $demande->setPassword($hasher->hashPassword($tempUser, $form->get('plainPassword')->getData()));

            // La vérification email est désactivée : on marque directement l'email comme vérifié
            // pour que la demande soit immédiatement traitable par l'admin.
            $demande->setEmailVerifie(true);

            $em->persist($demande);
            $em->flush();

            $this->addFlash('success', "Votre demande d'accès a bien été enregistrée ({$demande->getEmail()}). Un administrateur examinera votre dossier.");

            // PRG (Post-Redirect-Get) : le 303 force le navigateur à faire un GET sur /login,
            // ce qui évite de re-soumettre le formulaire si l'utilisateur recharge la page.
            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('inscription/formulaire.html.twig', [
            'form' => $form,
        ]);
    }

    // Route de confirmation par email — actuellement inaccessible car setEmailVerifie(true)
    // est fait directement à la soumission. Conservée si la vérification email est réactivée.
    #[Route('/inscription/confirmer/{token}', name: 'app_inscription_confirmer')]
    public function confirmer(
        string $token,
        DemandeInscriptionRepository $demandeRepository,
        EntityManagerInterface $em,
    ): Response {
        $demande = $demandeRepository->findOneBy(['token' => $token]);

        if (!$demande || !$demande->isTokenValide()) {
            return $this->render('inscription/token_invalide.html.twig');
        }

        if ($demande->isEmailVerifie()) {
            return $this->redirectToRoute('app_login');
        }

        $demande->setEmailVerifie(true);
        $demande->setToken(null);
        $demande->setTokenExpiresAt(null);
        $em->flush();

        return $this->render('inscription/email_verifie.html.twig');
    }
}
