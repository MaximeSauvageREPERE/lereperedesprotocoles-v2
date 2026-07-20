# Le Repère des Protocoles v2

[![CI](https://github.com/MaximeSauvageREPERE/lereperedesprotocoles-v2/actions/workflows/ci.yml/badge.svg)](https://github.com/MaximeSauvageREPERE/lereperedesprotocoles-v2/actions/workflows/ci.yml)

Application Symfony 7.4 de gestion et de consultation de protocoles médicaux. Les utilisateurs accèdent aux protocoles après validation de leur inscription par un administrateur. Les contenus (domaines, rubriques, thèmes, protocoles) sont gérés par des modérateurs.

## Stack technique

| Couche | Technologie |
|---|---|
| Langage | PHP 8.3 |
| Framework | Symfony 7.4 |
| ORM | Doctrine 3 + Migrations |
| Base de données | MySQL 8.4 |
| CSS | Tailwind CSS 3.4 (via symfonycasts/tailwind-bundle) |
| JS | Stimulus (Hotwired, via AssetMapper) |
| Lecteur PDF | PDF.js v4 (Mozilla, intégré en iframe) |
| Upload | Vich UploaderBundle v2.9 |
| Pagination | KnpPaginatorBundle |
| Serveur local | Laragon 2026 |

## Installation

Voir [docs/installation.md](docs/installation.md) pour le guide complet.

```powershell
git clone https://github.com/MaximeSauvageREPERE/lereperedesprotocoles-v2.git
cd lereperedesprotocoles-v2
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console tailwind:build --watch   # terminal 1
symfony server:start                     # terminal 2
```

## Rôles utilisateurs

| Rôle | Zone URL | Permissions |
|---|---|---|
| `ROLE_USER` | `/profil/*` `/recherche` | Consulter, rechercher et télécharger les protocoles |
| `ROLE_MODERATEUR` | `/moderateur/*` | + Tableau de bord, CRUD Domaine, Rubrique, Thème, Protocole |
| `ROLE_ADMIN` | `/admin/*` | + Tableau de bord, CRUD Utilisateurs, Professions, validation des inscriptions |

La hiérarchie est cumulative : `ROLE_ADMIN` hérite de `ROLE_MODERATEUR`, qui hérite de `ROLE_USER`.

## Modèle de données

```
Profession ──► User
Profession ──► DemandeInscription

Domaine ↔(ManyToMany)↔ Rubrique ──► Thème ──► Protocole
```

Voir [docs/entites.md](docs/entites.md) pour le détail de chaque entité.

## État d'avancement

| Ticket | Titre | Statut |
|---|---|---|
| #1 | Setup Symfony + MySQL | ✅ |
| #2 | Tailwind CSS | ✅ |
| #3 | Entités Doctrine + Migrations | ✅ |
| #18 | Entité Profession | ✅ |
| #4 | Authentification login/logout | ✅ |
| #5 | Workflow d'inscription | ✅ |
| #6 | CRUD Domaines (modérateur) | ✅ |
| #7 | CRUD Rubriques (modérateur) | ✅ |
| #8 | CRUD Thèmes (modérateur) | ✅ |
| #9 | CRUD Protocoles + Upload PDF | ✅ |
| #10 | Navigation publique | ✅ |
| #19 | CRUD Utilisateurs (admin) | ✅ |
| #29 | CRUD Professions (admin) | ✅ |
| #11 | Templates Twig + Layout général | ✅ |
| #12 | DataFixtures | ✅ |
| #33 | Responsive mobile-first | ✅ |
| #36 | PHPStan niveau 5 | ✅ |
| #37 | PHP CS Fixer (formatage @Symfony) | ✅ |
| #13 | Tests PHPUnit (tests unitaires) | ✅ |
| #35 | Tests fonctionnels (HTTP + rôles) | ✅ |
| #40 | GitHub Actions CI | ✅ |
| #44 | Emails transactionnels | ✅ |
| #41 | Pagination des listes CRUD | ✅ |
| #43 | Recherche/filtre dans les listes CRUD | ✅ |
| #42 | Index Doctrine sur les colonnes filtrées | ✅ |
| #38 | Rate limiting (brute force login) | ✅ |
| #39 | Validation uploads (type MIME réel) | ✅ |
| #45 | Charte graphique — refonte frontend | ✅ |
| #46 | Lecteur PDF intégré (PDF.js, plein écran) | ✅ |
| #60 | Améliorations UX viewer PDF + formulaire protocole | ✅ |
| #61 | Recherche globale des protocoles | ✅ |
| #66 | Tableaux de bord admin et modérateur | ✅ |
| #68 | Correction stats de langages GitHub (.gitattributes) | ✅ |