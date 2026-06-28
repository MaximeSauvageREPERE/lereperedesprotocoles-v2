<?php

namespace App\Controller\Admin;

use App\Entity\DemandeInscription;
use App\Entity\User;
use App\Form\RefuserDemandeType;
use App\Repository\DemandeInscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/demandes')]
#[IsGranted('ROLE_ADMIN')]
class DemandeController extends AbstractController
{
    #[Route('', name: 'admin_demande_index')]
    public function index(DemandeInscriptionRepository $repo, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->getString('q', '');

        // Deux tableaux paginés indépendants sur la même page, chacun avec son propre paramètre de page.
        $demandesPagination = $paginator->paginate(
            $repo->queryBuilderEnAttentePourAdmin($q),
            $request->query->getInt('page', 1),
            20
        );

        // Section secondaire : demandes dont l'email n'est pas encore vérifié
        // (normalement vide depuis la désactivation de la vérification email).
        $emailPagination = $paginator->paginate(
            $repo->queryBuilderNonVerifiees(),
            $request->query->getInt('page_email', 1),
            20,
            ['pageParameterName' => 'page_email']
        );

        return $this->render('admin/demande/index.html.twig', [
            'demandes_pagination' => $demandesPagination,
            'email_pagination' => $emailPagination,
            'q' => $q,
        ]);
    }

    #[Route('/{id}/approuver', name: 'admin_demande_approuver', methods: ['POST'])]
    public function approuver(
        DemandeInscription $demande,
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
    ): Response {
        // Protection CSRF : vérifie que la requête vient bien du formulaire de l'application
        // et non d'un site tiers (Cross-Site Request Forgery).
        if (!$this->isCsrfTokenValid('approuver_'.$demande->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        // Double sécurité : on ne peut approuver que si l'email est vérifié ET que la demande est encore en attente.
        if (!$demande->isEmailVerifie() || DemandeInscription::STATUT_EN_ATTENTE !== $demande->getStatut()) {
            $this->addFlash('error', 'Cette demande ne peut pas être approuvée.');

            return $this->redirectToRoute('admin_demande_index');
        }

        // Création du compte utilisateur à partir des données de la demande.
        // Le mot de passe est déjà haché dans la demande (fait à l'inscription), on le copie directement.
        $user = new User();
        $user->setEmail($demande->getEmail());
        $user->setPrenom($demande->getPrenom());
        $user->setNom($demande->getNom());
        $user->setProfession($demande->getProfession());
        $user->setPassword($demande->getPassword());
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);

        $demande->setStatut(DemandeInscription::STATUT_APPROUVEE);
        $demande->setTraiteeAt(new \DateTimeImmutable());
        // Lien entre la demande et le compte créé, utile pour l'historique.
        $demande->setUtilisateur($user);

        $em->persist($user);
        $em->flush();

        // Email de notification envoyé à l'utilisateur : son accès est accordé.
        $email = (new TemplatedEmail())
            ->from('noreply@lereperedesprotocoles.fr')
            ->to($demande->getEmail())
            ->subject('Votre accès au Repère des Protocoles a été approuvé')
            ->htmlTemplate('emails/inscription_approuvee.html.twig')
            ->context(['demande' => $demande]);
        $mailer->send($email);

        $this->addFlash('success', "Compte créé pour {$demande->getPrenom()} {$demande->getNom()}.");

        return $this->redirectToRoute('admin_demande_index');
    }

    #[Route('/{id}/refuser', name: 'admin_demande_refuser', methods: ['GET', 'POST'])]
    public function refuser(
        DemandeInscription $demande,
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
    ): Response {
        // Empêche de refuser une demande déjà traitée (déjà approuvée ou refusée).
        if (DemandeInscription::STATUT_EN_ATTENTE !== $demande->getStatut()) {
            $this->addFlash('error', 'Cette demande a déjà été traitée.');

            return $this->redirectToRoute('admin_demande_index');
        }

        // Le formulaire de refus demande un motif qui sera inclus dans l'email envoyé au candidat.
        $form = $this->createForm(RefuserDemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $demande->setStatut(DemandeInscription::STATUT_REFUSEE);
            $demande->setTraiteeAt(new \DateTimeImmutable());
            $em->flush();

            // Email de notification avec le motif de refus.
            $email = (new TemplatedEmail())
                ->from('noreply@lereperedesprotocoles.fr')
                ->to($demande->getEmail())
                ->subject("Votre demande d'accès au Repère des Protocoles")
                ->htmlTemplate('emails/inscription_refusee.html.twig')
                ->context(['demande' => $demande]);
            $mailer->send($email);

            $this->addFlash('success', "Demande de {$demande->getPrenom()} {$demande->getNom()} refusée.");

            return $this->redirectToRoute('admin_demande_index');
        }

        return $this->render('admin/demande/refuser.html.twig', [
            'demande' => $demande,
            'form' => $form,
        ]);
    }
}
