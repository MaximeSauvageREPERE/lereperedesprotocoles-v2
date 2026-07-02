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

/**
 * CRUD des domaines, accessible aux modérateurs et administrateurs.
 *
 * ROLE_MODERATEUR est requis — les admins y ont aussi accès car leur rôle est
 * hiérarchiquement supérieur (défini dans security.yaml).
 * La suppression via POST empêche les bots ou prefetchers de déclencher l'action via un lien GET.
 */
#[Route('/moderateur/domaines')]
#[IsGranted('ROLE_MODERATEUR')]
class DomaineController extends AbstractController
{
    /**
     * Liste paginée des domaines avec recherche par nom.
     */
    #[Route('', name: 'moderateur_domaine_index', methods: ['GET'])]
    public function index(DomaineRepository $repo, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->getString('q', '');
        $pagination = $paginator->paginate(
            $repo->queryBuilderSearch($q),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('moderateur/domaine/index.html.twig', [
            'pagination' => $pagination,
            'q' => $q,
        ]);
    }

    /**
     * Affiche le formulaire de création (GET) et persiste le nouveau domaine (POST).
     * Le slug est généré depuis le nom à la soumission.
     */
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

    /**
     * Affiche le formulaire de modification et met à jour le domaine.
     * Symfony résout automatiquement l'objet Domaine depuis l'id dans l'URL (ParamConverter).
     * Le slug est recalculé à chaque modification du nom.
     */
    #[Route('/{id}/modifier', name: 'moderateur_domaine_edit', methods: ['GET', 'POST'])]
    public function edit(Domaine $domaine, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(DomaineType::class, $domaine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

    /**
     * Supprime un domaine après vérification du token CSRF.
     * Route POST uniquement pour éviter une suppression accidentelle via un lien GET.
     */
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

    /**
     * Convertit un nom en slug URL-compatible en gérant les accents et caractères spéciaux français.
     *
     * @param string $nom Nom brut (ex: "Cardiologie & Vasculaire")
     *
     * @return string Slug normalisé (ex: "cardiologie-vasculaire")
     */
    private function slugify(string $nom): string
    {
        return strtolower((new AsciiSlugger('fr'))->slug($nom)->toString());
    }
}
