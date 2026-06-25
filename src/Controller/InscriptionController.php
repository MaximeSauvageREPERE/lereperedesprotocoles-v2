<?php

namespace App\Controller;

use App\Entity\DemandeInscription;
use App\Entity\User;
use App\Form\InscriptionType;
use App\Repository\DemandeInscriptionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\ByteString;

class InscriptionController extends AbstractController
{
    #[Route('/inscription', name: 'app_inscription')]
    public function formulaire(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        MailerInterface $mailer,
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

            $tempUser = new User();
            $demande->setPassword($hasher->hashPassword($tempUser, $form->get('plainPassword')->getData()));

            $token = ByteString::fromRandom(32)->toString();
            $demande->setToken($token);
            $demande->setTokenExpiresAt(new \DateTimeImmutable('+24 hours'));

            $em->persist($demande);
            $em->flush();

            $email = (new TemplatedEmail())
                ->from('noreply@lereperedesprotocoles.fr')
                ->to($demande->getEmail())
                ->subject('Confirmez votre adresse email')
                ->htmlTemplate('emails/inscription_confirmation.html.twig')
                ->context(['demande' => $demande, 'token' => $token]);
            $mailer->send($email);

            return $this->render('inscription/succes.html.twig', [
                'email' => $demande->getEmail(),
            ]);
        }

        return $this->render('inscription/formulaire.html.twig', [
            'form' => $form,
        ]);
    }

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
