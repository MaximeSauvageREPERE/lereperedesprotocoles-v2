<?php

namespace App\Controller\Moderateur;

use App\Entity\Rubrique;
use App\Form\RubriqueType;
use App\Repository\RubriqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/moderateur/rubriques')]
#[IsGranted('ROLE_MODERATEUR')]
class RubriqueController extends AbstractController
{
    #[Route('', name: 'moderateur_rubrique_index', methods: ['GET'])]
    public function index(RubriqueRepository $repo, PaginatorInterface $paginator, Request $request): Response
    {
        $pagination = $paginator->paginate(
            $repo->createQueryBuilder('r')->orderBy('r.nom', 'ASC'),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('moderateur/rubrique/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/nouveau', name: 'moderateur_rubrique_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $rubrique = new Rubrique();
        $form = $this->createForm(RubriqueType::class, $rubrique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rubrique->setSlug($this->slugify($rubrique->getNom()));
            $em->persist($rubrique);
            $em->flush();

            $this->addFlash('success', 'Rubrique créée.');

            return $this->redirectToRoute('moderateur_rubrique_index');
        }

        return $this->render('moderateur/rubrique/new.html.twig', ['form' => $form]);
    }

    #[Route('/{id}/modifier', name: 'moderateur_rubrique_edit', methods: ['GET', 'POST'])]
    public function edit(Rubrique $rubrique, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(RubriqueType::class, $rubrique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $rubrique->setSlug($this->slugify($rubrique->getNom()));
            $em->flush();

            $this->addFlash('success', 'Rubrique modifiée.');

            return $this->redirectToRoute('moderateur_rubrique_index');
        }

        return $this->render('moderateur/rubrique/edit.html.twig', ['form' => $form, 'rubrique' => $rubrique]);
    }

    #[Route('/{id}/supprimer', name: 'moderateur_rubrique_delete', methods: ['POST'])]
    public function delete(Rubrique $rubrique, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_rubrique_'.$rubrique->getId(), $request->request->get('_token'))) {
            $em->remove($rubrique);
            $em->flush();
            $this->addFlash('success', 'Rubrique supprimée.');
        }

        return $this->redirectToRoute('moderateur_rubrique_index');
    }

    private function slugify(string $text): string
    {
        return (new AsciiSlugger('fr'))->slug($text)->lower()->toString();
    }
}
