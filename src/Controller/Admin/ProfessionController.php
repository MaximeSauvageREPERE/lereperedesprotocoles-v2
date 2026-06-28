<?php

namespace App\Controller\Admin;

use App\Entity\Profession;
use App\Form\ProfessionType;
use App\Repository\ProfessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/admin/professions')]
#[IsGranted('ROLE_ADMIN')]
class ProfessionController extends AbstractController
{
    #[Route('', name: 'admin_profession_index', methods: ['GET'])]
    public function index(ProfessionRepository $repo, PaginatorInterface $paginator, Request $request): Response
    {
        $q = $request->query->getString('q', '');
        $pagination = $paginator->paginate(
            $repo->queryBuilderSearch($q),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/profession/index.html.twig', [
            'pagination' => $pagination,
            'q' => $q,
        ]);
    }

    #[Route('/nouveau', name: 'admin_profession_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $profession = new Profession();
        $form = $this->createForm(ProfessionType::class, $profession);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Le slug est généré automatiquement à partir du nom (ex: "Médecin généraliste" → "medecin-generaliste").
            // Il sert d'identifiant lisible dans les URLs et doit être unique.
            $profession->setSlug($this->slugify($profession->getNom()));
            $em->persist($profession);
            $em->flush();

            $this->addFlash('success', 'Profession créée.');

            return $this->redirectToRoute('admin_profession_index');
        }

        return $this->render('admin/profession/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/modifier', name: 'admin_profession_edit', methods: ['GET', 'POST'])]
    public function edit(Profession $profession, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProfessionType::class, $profession);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profession->setSlug($this->slugify($profession->getNom()));
            $em->flush();

            $this->addFlash('success', 'Profession modifiée.');

            return $this->redirectToRoute('admin_profession_index');
        }

        return $this->render('admin/profession/edit.html.twig', [
            'profession' => $profession,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_profession_delete', methods: ['POST'])]
    public function delete(Profession $profession, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_profession_'.$profession->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        // Interdit la suppression si des utilisateurs ou des demandes sont rattachés à cette profession
        // pour ne pas laisser d'enregistrements orphelins en base.
        if ($profession->getUsers()->count() > 0 || $profession->getDemandesInscription()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer cette profession : des utilisateurs ou des demandes y sont rattachés.');

            return $this->redirectToRoute('admin_profession_index');
        }

        $nom = $profession->getNom();
        $em->remove($profession);
        $em->flush();

        $this->addFlash('success', "Profession « $nom » supprimée.");

        return $this->redirectToRoute('admin_profession_index');
    }

    // Convertit un nom en slug URL-compatible, en gérant les accents et caractères spéciaux français.
    private function slugify(string $nom): string
    {
        return strtolower((new AsciiSlugger('fr'))->slug($nom)->toString());
    }
}
