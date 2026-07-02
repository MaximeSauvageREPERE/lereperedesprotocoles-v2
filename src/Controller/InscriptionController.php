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

/**
 * Gère le flux d'inscription : soumission de la demande d'accès et confirmation par email.
 *
 * La vérification email est actuellement désactivée : emailVerifie est mis à true
 * directement à la soumission, ce qui rend la demande immédiatement visible par l'admin.
 * La route /inscription/confirmer/{token} est conservée pour une réactivation future.
 */
class InscriptionController extends AbstractController
{
    /**
     * Affiche et traite le formulaire de demande d'accès.
     *
     * Vérifie l'absence de doublons (compte existant ou demande en attente pour le même email)
     * avant de persister la demande. Le mot de passe est haché via un User temporaire
     * pour utiliser l'algorithme défini dans security.yaml.
     * Redirige en 303 (PRG) vers /login après soumission réussie.
     */
    #[Route('/inscription', name: 'app_inscription')]
    public function formulaire(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        UserRepository $userRepository,
        DemandeInscriptionRepository $demandeRepository,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $demande = new DemandeInscription();
        $form = $this->createForm(InscriptionType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($userRepository->findOneBy(['email' => $demande->getEmail()])) {
                $this->addFlash('error', 'Un compte existe déjà avec cette adresse email.');

                return $this->redirectToRoute('app_inscription');
            }

            $existante = $demandeRepository->findOneBy(['email' => $demande->getEmail(), 'statut' => DemandeInscription::STATUT_EN_ATTENTE]);
            if ($existante) {
                $this->addFlash('error', 'Une demande est déjà en cours pour cette adresse email.');

                return $this->redirectToRoute('app_inscription');
            }

            // Le hasher nécessite un objet User pour appliquer l'algorithme de security.yaml.
            // Un User temporaire est créé uniquement pour obtenir le hash — il n'est pas persisté.
            $tempUser = new User();
            $demande->setPassword($hasher->hashPassword($tempUser, $form->get('plainPassword')->getData()));

            $demande->setEmailVerifie(true);

            $em->persist($demande);
            $em->flush();

            $this->addFlash('success', "Votre demande d'accès a bien été enregistrée ({$demande->getEmail()}). Un administrateur examinera votre dossier.");

            return $this->redirectToRoute('app_login', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('inscription/formulaire.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Confirme l'adresse email via le token envoyé par email.
     *
     * Route actuellement inaccessible : emailVerifie est mis à true directement à la soumission
     * depuis que la vérification email est désactivée. Conservée pour une réactivation future.
     *
     * @param string $token Token à usage unique extrait de l'URL
     */
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
