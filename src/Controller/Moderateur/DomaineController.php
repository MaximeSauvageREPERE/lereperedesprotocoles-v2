<?php

namespace App\Controller\Moderateur;

use App\Entity\Domaine;
use App\Form\DomaineType;
use App\Repository\DomaineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/moderateur/domaines')]
#[IsGranted('ROLE_MODERATEUR')]
class DomaineController extends AbstractController
{
    #[Route('', name: 'moderateur_domaine_index', methods: ['GET'])]
    public function index(DomaineRepository $repo): Response
    {
        return $this->render('moderateur/domaine/index.html.twig', [
            'domaines' => $repo->findBy([], ['nom' => 'ASC']),
        ]);
    }

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

    #[Route('/{id}/supprimer', name: 'moderateur_domaine_delete', methods: ['POST'])]
    public function delete(Domaine $domaine, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_domaine_' . $domaine->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($domaine);
        $em->flush();

        $this->addFlash('success', 'Domaine supprimé.');

        return $this->redirectToRoute('moderateur_domaine_index');
    }

    private function slugify(string $nom): string
    {
        return strtolower((new AsciiSlugger('fr'))->slug($nom)->toString());
    }
}
