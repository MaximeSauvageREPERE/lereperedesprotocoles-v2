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
        $demandesPagination = $paginator->paginate(
            $repo->queryBuilderEnAttentePourAdmin($q),
            $request->query->getInt('page', 1),
            20
        );

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
        if (!$this->isCsrfTokenValid('approuver_'.$demande->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        if (!$demande->isEmailVerifie() || DemandeInscription::STATUT_EN_ATTENTE !== $demande->getStatut()) {
            $this->addFlash('error', 'Cette demande ne peut pas être approuvée.');

            return $this->redirectToRoute('admin_demande_index');
        }

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
        $demande->setUtilisateur($user);

        $em->persist($user);
        $em->flush();

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
        if (DemandeInscription::STATUT_EN_ATTENTE !== $demande->getStatut()) {
            $this->addFlash('error', 'Cette demande a déjà été traitée.');

            return $this->redirectToRoute('admin_demande_index');
        }

        $form = $this->createForm(RefuserDemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $demande->setStatut(DemandeInscription::STATUT_REFUSEE);
            $demande->setTraiteeAt(new \DateTimeImmutable());
            $em->flush();

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
