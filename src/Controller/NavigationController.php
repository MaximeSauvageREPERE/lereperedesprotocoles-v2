<?php

namespace App\Controller;

use App\Repository\DomaineRepository;
use App\Repository\ProtocoleRepository;
use App\Repository\RubriqueRepository;
use App\Repository\ThemeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class NavigationController extends AbstractController
{
    #[Route('/parcourir', name: 'navigation_domaines')]
    public function domaines(DomaineRepository $repo): Response
    {
        return $this->render('navigation/domaines.html.twig', [
            'domaines' => $repo->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/domaines/{slug}', name: 'navigation_domaine')]
    public function domaine(string $slug, DomaineRepository $repo): Response
    {
        $domaine = $repo->findOneBy(['slug' => $slug]);
        if (!$domaine) {
            throw $this->createNotFoundException('Domaine introuvable.');
        }

        return $this->render('navigation/domaine.html.twig', [
            'domaine' => $domaine,
        ]);
    }

    #[Route('/rubriques/{slug}', name: 'navigation_rubrique')]
    public function rubrique(string $slug, RubriqueRepository $repo): Response
    {
        $rubrique = $repo->findOneBy(['slug' => $slug]);
        if (!$rubrique) {
            throw $this->createNotFoundException('Rubrique introuvable.');
        }

        return $this->render('navigation/rubrique.html.twig', [
            'rubrique' => $rubrique,
        ]);
    }

    #[Route('/themes/{slug}', name: 'navigation_theme')]
    public function theme(string $slug, ThemeRepository $repo): Response
    {
        $theme = $repo->findOneBy(['slug' => $slug]);
        if (!$theme) {
            throw $this->createNotFoundException('Thème introuvable.');
        }

        return $this->render('navigation/theme.html.twig', [
            'theme' => $theme,
        ]);
    }

    #[Route('/protocoles/{slug}', name: 'navigation_protocole')]
    public function protocole(string $slug, ProtocoleRepository $repo): Response
    {
        $protocole = $repo->findOneBy(['slug' => $slug]);
        if (!$protocole) {
            throw $this->createNotFoundException('Protocole introuvable.');
        }

        return $this->render('navigation/protocole.html.twig', [
            'protocole' => $protocole,
        ]);
    }
}
