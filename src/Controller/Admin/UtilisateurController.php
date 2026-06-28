<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UtilisateurType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class UtilisateurController extends AbstractController
{
    #[Route('', name: 'admin_utilisateur_index', methods: ['GET'])]
    public function index(UserRepository $repo, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->getString('q', '');
        $pagination = $paginator->paginate(
            $repo->queryBuilderSearch($q),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/utilisateur/index.html.twig', [
            'pagination' => $pagination,
            'q' => $q,
        ]);
    }

    #[Route('/{id}/modifier', name: 'admin_utilisateur_edit', methods: ['GET', 'POST'])]
    public function edit(
        User $utilisateur,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        $form = $this->createForm(UtilisateurType::class, $utilisateur);

        // Symfony stocke les rôles comme un tableau (ex: ['ROLE_ADMIN', 'ROLE_USER']).
        // On en déduit un niveau unique pour pré-remplir le select du formulaire.
        $currentRoles = $utilisateur->getRoles();
        $niveau = match (true) {
            in_array('ROLE_ADMIN', $currentRoles, true) => 'ROLE_ADMIN',
            in_array('ROLE_MODERATEUR', $currentRoles, true) => 'ROLE_MODERATEUR',
            default => 'ROLE_USER',
        };
        $form->get('niveau')->setData($niveau);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Le champ mot de passe est optionnel : si vide, on conserve le mot de passe actuel.
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $utilisateur->setPassword($hasher->hashPassword($utilisateur, $plainPassword));
            }

            // On remplace le tableau de rôles entier par le niveau sélectionné.
            // Symfony ajoute ROLE_USER automatiquement via getRoles() pour tout utilisateur connecté.
            $utilisateur->setRoles([$form->get('niveau')->getData()]);

            $em->flush();
            $this->addFlash('success', 'Utilisateur modifié.');

            return $this->redirectToRoute('admin_utilisateur_index');
        }

        return $this->render('admin/utilisateur/edit.html.twig', [
            'utilisateur' => $utilisateur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_utilisateur_delete', methods: ['POST'])]
    public function delete(User $utilisateur, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_user_'.$utilisateur->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        // Un admin ne peut pas se supprimer lui-même pour éviter de se bloquer hors de l'application.
        if ($utilisateur === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');

            return $this->redirectToRoute('admin_utilisateur_index');
        }

        $nom = $utilisateur->getPrenom().' '.$utilisateur->getNom();
        $em->remove($utilisateur);
        $em->flush();

        $this->addFlash('success', "Utilisateur $nom supprimé.");

        return $this->redirectToRoute('admin_utilisateur_index');
    }
}
