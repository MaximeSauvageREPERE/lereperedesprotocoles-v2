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

/**
 * Gère la liste et la modification des comptes utilisateurs par l'administrateur.
 *
 * Permet de modifier le rôle (ROLE_USER / ROLE_MODERATEUR / ROLE_ADMIN) et le mot de passe.
 * Un admin ne peut pas supprimer son propre compte.
 */
#[Route('/admin/utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class UtilisateurController extends AbstractController
{
    /**
     * Liste paginée des utilisateurs avec recherche par nom, prénom et email.
     */
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

    /**
     * Modifie le profil, le rôle et optionnellement le mot de passe d'un utilisateur.
     *
     * Le niveau de rôle est déduit du tableau de rôles stocké en BDD pour pré-remplir
     * le select du formulaire. Si le champ mot de passe est vide, le mot de passe actuel est conservé.
     */
    #[Route('/{id}/modifier', name: 'admin_utilisateur_edit', methods: ['GET', 'POST'])]
    public function edit(
        User $utilisateur,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): Response {
        $form = $this->createForm(UtilisateurType::class, $utilisateur);

        $currentRoles = $utilisateur->getRoles();
        $niveau = match (true) {
            in_array('ROLE_ADMIN', $currentRoles, true) => 'ROLE_ADMIN',
            in_array('ROLE_MODERATEUR', $currentRoles, true) => 'ROLE_MODERATEUR',
            default => 'ROLE_USER',
        };
        $form->get('niveau')->setData($niveau);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $utilisateur->setPassword($hasher->hashPassword($utilisateur, $plainPassword));
            }

            // Symfony ajoute ROLE_USER automatiquement via getRoles() — on stocke uniquement le niveau choisi.
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

    /**
     * Supprime un compte utilisateur après vérification CSRF.
     * Interdit à un admin de supprimer son propre compte.
     *
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException si le token CSRF est invalide
     */
    #[Route('/{id}/supprimer', name: 'admin_utilisateur_delete', methods: ['POST'])]
    public function delete(User $utilisateur, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_user_'.$utilisateur->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

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
