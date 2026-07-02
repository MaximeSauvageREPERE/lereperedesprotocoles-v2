<?php

namespace App\Controller\Moderateur;

use App\Entity\Protocole;
use App\Form\ProtocoleType;
use App\Repository\ProtocoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * CRUD des protocoles médicaux, accessible aux modérateurs et administrateurs.
 *
 * Même structure que {@see DomaineController}.
 * Différence notable : le slug est généré depuis le titre (et non le nom) du protocole.
 * La gestion des fichiers PDF et image est assurée par VichUploaderBundle via {@see Protocole}.
 *
 * @package App\Controller\Moderateur
 */
#[Route('/moderateur/protocoles')]
#[IsGranted('ROLE_MODERATEUR')]
class ProtocoleController extends AbstractController
{
    /**
     * Liste paginée des protocoles avec recherche par titre.
     */
    #[Route('', name: 'moderateur_protocole_index', methods: ['GET'])]
    public function index(ProtocoleRepository $repo, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->getString('q', '');
        $pagination = $paginator->paginate(
            $repo->queryBuilderSearch($q),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('moderateur/protocole/index.html.twig', [
            'pagination' => $pagination,
            'q' => $q,
        ]);
    }

    /**
     * Affiche le formulaire de création (GET) et persiste le nouveau protocole (POST).
     * Le slug est généré depuis le titre à la soumission.
     */
    #[Route('/nouveau', name: 'moderateur_protocole_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $protocole = new Protocole();
        $form = $this->createForm(ProtocoleType::class, $protocole);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $protocole->setSlug($this->slugify($protocole->getTitre()));
            $em->persist($protocole);
            $em->flush();

            $this->addFlash('success', 'Protocole créé.');

            return $this->redirectToRoute('moderateur_protocole_index');
        }

        return $this->render('moderateur/protocole/new.html.twig', ['form' => $form]);
    }

    /**
     * Affiche le formulaire de modification et met à jour le protocole.
     * Le slug est recalculé à chaque modification du titre.
     */
    #[Route('/{id}/modifier', name: 'moderateur_protocole_edit', methods: ['GET', 'POST'])]
    public function edit(Protocole $protocole, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProtocoleType::class, $protocole);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $protocole->setSlug($this->slugify($protocole->getTitre()));
            $em->flush();

            $this->addFlash('success', 'Protocole modifié.');

            return $this->redirectToRoute('moderateur_protocole_index');
        }

        return $this->render('moderateur/protocole/edit.html.twig', [
            'form' => $form,
            'protocole' => $protocole,
        ]);
    }

    /**
     * Supprime un protocole après vérification du token CSRF.
     * VichUploaderBundle supprime automatiquement les fichiers PDF et image associés.
     */
    #[Route('/{id}/supprimer', name: 'moderateur_protocole_delete', methods: ['POST'])]
    public function delete(Protocole $protocole, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_protocole_'.$protocole->getId(), $request->request->get('_token'))) {
            $em->remove($protocole);
            $em->flush();
            $this->addFlash('success', 'Protocole supprimé.');
        }

        return $this->redirectToRoute('moderateur_protocole_index');
    }

    private function slugify(string $text): string
    {
        return (new AsciiSlugger('fr'))->slug($text)->lower()->toString();
    }
}
