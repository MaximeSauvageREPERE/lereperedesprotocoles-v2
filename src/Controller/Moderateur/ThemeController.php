<?php

namespace App\Controller\Moderateur;

use App\Entity\Theme;
use App\Form\ThemeType;
use App\Repository\ThemeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;

// CRUD identique à DomaineController — voir ce fichier pour les commentaires détaillés.
#[Route('/moderateur/themes')]
#[IsGranted('ROLE_MODERATEUR')]
class ThemeController extends AbstractController
{
    #[Route('', name: 'moderateur_theme_index', methods: ['GET'])]
    public function index(ThemeRepository $repo, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->getString('q', '');
        $pagination = $paginator->paginate(
            $repo->queryBuilderSearch($q),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('moderateur/theme/index.html.twig', [
            'pagination' => $pagination,
            'q' => $q,
        ]);
    }

    #[Route('/nouveau', name: 'moderateur_theme_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $theme = new Theme();
        $form = $this->createForm(ThemeType::class, $theme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $theme->setSlug($this->slugify($theme->getNom()));
            $em->persist($theme);
            $em->flush();

            $this->addFlash('success', 'Thème créé.');

            return $this->redirectToRoute('moderateur_theme_index');
        }

        return $this->render('moderateur/theme/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/modifier', name: 'moderateur_theme_edit', methods: ['GET', 'POST'])]
    public function edit(Theme $theme, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ThemeType::class, $theme);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $theme->setSlug($this->slugify($theme->getNom()));
            $em->flush();

            $this->addFlash('success', 'Thème modifié.');

            return $this->redirectToRoute('moderateur_theme_index');
        }

        return $this->render('moderateur/theme/edit.html.twig', ['form' => $form, 'theme' => $theme]);
    }

    #[Route('/{id}/supprimer', name: 'moderateur_theme_delete', methods: ['POST'])]
    public function delete(Theme $theme, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_theme_'.$theme->getId(), $request->request->get('_token'))) {
            $em->remove($theme);
            $em->flush();
            $this->addFlash('success', 'Thème supprimé.');
        }

        return $this->redirectToRoute('moderateur_theme_index');
    }

    private function slugify(string $text): string
    {
        return (new AsciiSlugger('fr'))->slug($text)->lower()->toString();
    }
}
