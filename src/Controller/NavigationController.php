<?php

namespace App\Controller;

use App\Repository\DomaineRepository;
use App\Repository\ProtocoleRepository;
use App\Repository\RubriqueRepository;
use App\Repository\ThemeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Gère la navigation hiérarchique dans le catalogue : Domaines → Rubriques → Thèmes → Protocoles.
 *
 * Tout le contenu est réservé aux utilisateurs connectés (ROLE_USER).
 * Chaque niveau est identifié par son slug dans l'URL.
 */
#[IsGranted('ROLE_USER')]
class NavigationController extends AbstractController
{
    /**
     * Point d'entrée de la navigation : liste tous les domaines disponibles.
     */
    #[Route('/parcourir', name: 'navigation_domaines')]
    public function domaines(DomaineRepository $repo): Response
    {
        return $this->render('navigation/domaines.html.twig', [
            'domaines' => $repo->findBy([], ['nom' => 'ASC']),
        ]);
    }

    /**
     * Affiche les rubriques d'un domaine identifié par son slug.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException si le domaine est introuvable
     */
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

    /**
     * Affiche les thèmes d'une rubrique identifiée par son slug.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException si la rubrique est introuvable
     */
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

    /**
     * Affiche les protocoles d'un thème identifié par son slug.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException si le thème est introuvable
     */
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

    /**
     * Recherche globale dans tous les protocoles par titre ou description.
     *
     * La requête est transmise via le paramètre GET "q". Une requête vide affiche le formulaire
     * sans résultats. Les résultats incluent le fil d'Ariane complet (thème → rubrique → domaine).
     */
    #[Route('/recherche', name: 'navigation_recherche')]
    public function recherche(Request $request, ProtocoleRepository $repo): Response
    {
        $q = trim((string) $request->query->get('q', ''));
        $resultats = '' !== $q ? $repo->search($q) : [];

        return $this->render('navigation/recherche.html.twig', [
            'q' => $q,
            'resultats' => $resultats,
        ]);
    }

    /**
     * Affiche la fiche complète d'un protocole (PDF, image, description).
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException si le protocole est introuvable
     */
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
