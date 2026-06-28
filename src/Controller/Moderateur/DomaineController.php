<?php

namespace App\Controller\Moderateur;

use App\Entity\Domaine;
use App\Form\DomaineType;
use App\Repository\DomaineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;

// ROLE_MODERATEUR est requis sur toutes les routes de ce controller.
// Les admins y ont aussi accès car leur rôle est hiérarchiquement supérieur (défini dans security.yaml).
#[Route('/moderateur/domaines')]
#[IsGranted('ROLE_MODERATEUR')]
class DomaineController extends AbstractController
{
    #[Route('', name: 'moderateur_domaine_index', methods: ['GET'])]
    public function index(DomaineRepository $repo, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->getString('q', '');
        $pagination = $paginator->paginate(
            // Le QueryBuilder permet à KnpPaginator de compter les résultats et de construire la requête paginée.
            $repo->queryBuilderSearch($q),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('moderateur/domaine/index.html.twig', [
            'pagination' => $pagination,
            'q' => $q,
        ]);
    }

    // GET affiche le formulaire vide, POST le traite — une seule route gère les deux cas.
    #[Route('/nouveau', name: 'moderateur_domaine_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $domaine = new Domaine();
        $form = $this->createForm(DomaineType::class, $domaine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $domaine->setSlug($this->slugify($domaine->getNom()));
            $em->persist($domaine);
            $em->flush();

            $this->addFlash('success', 'Domaine créé.');

            return $this->redirectToRoute('moderateur_domaine_index');
        }

        return $this->render('moderateur/domaine/new.html.twig', [
            'form' => $form,
        ]);
    }

    // Symfony résout automatiquement l'objet Domaine depuis l'id dans l'URL (ParamConverter).
    #[Route('/{id}/modifier', name: 'moderateur_domaine_edit', methods: ['GET', 'POST'])]
    public function edit(Domaine $domaine, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(DomaineType::class, $domaine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Le slug est recalculé à chaque modification du nom.
            $domaine->setSlug($this->slugify($domaine->getNom()));
            $em->flush();

            $this->addFlash('success', 'Domaine modifié.');

            return $this->redirectToRoute('moderateur_domaine_index');
        }

        return $this->render('moderateur/domaine/edit.html.twig', [
            'domaine' => $domaine,
            'form' => $form,
        ]);
    }

    // POST uniquement : la suppression ne doit jamais se faire via un simple lien GET
    // (un bot ou prefetcher pourrait déclencher la suppression en suivant le lien).
    #[Route('/{id}/supprimer', name: 'moderateur_domaine_delete', methods: ['POST'])]
    public function delete(Domaine $domaine, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_domaine_'.$domaine->getId(), $request->request->get('_token'))) {
            $em->remove($domaine);
            $em->flush();

            $this->addFlash('success', 'Domaine supprimé.');
        }

        return $this->redirectToRoute('moderateur_domaine_index');
    }

    // AsciiSlugger gère les caractères spéciaux et accents français (locale 'fr').
    private function slugify(string $nom): string
    {
        return strtolower((new AsciiSlugger('fr'))->slug($nom)->toString());
    }
}
