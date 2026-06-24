# Journal de démarrage — lereperedesprotocoles-v2

## 1. Outils installés sur la machine

| Outil | Version | Rôle |
|---|---|---|
| Git | 2.54.0 | Versioning du code |
| GitHub CLI (`gh`) | 2.95.0 | Gestion GitHub en ligne de commande |
| Laragon | 2026 v8.6.1 | Environnement PHP local sur Windows |
| PHP | 8.3.30 | Langage du projet |
| Composer | 2.9.4 | Gestionnaire de dépendances PHP |
| Scoop | — | Gestionnaire de paquets Windows |
| Symfony CLI | 5.17.1 | Création et gestion du projet Symfony |

## 2. Configuration Git

```
user.name  = Maxime Sauvage
user.email = maxime-sauvage@live.fr
```

## 3. Repo GitHub

- **URL :** github.com/MaximeSauvageREPERE/lereperedesprotocoles-v2
- **Visibilité :** Public
- **Branche principale :** `main`
- **Local :** `C:\dev\lereperedesprotocoles-v2`

## 4. Stack technique retenue

- PHP 8.3 / Symfony 7.4
- MySQL 8.4 (via Laragon)
- Tailwind CSS v3.4
- Stimulus + Turbo (Hotwired)
- Vich UploaderBundle (upload PDF)
- Docker (à configurer)

## 5. Labels GitHub créés

| Label | Couleur | Usage |
|---|---|---|
| `feature` | Bleu | Nouvelle fonctionnalité |
| `backend` | Jaune | Logique PHP/Symfony |
| `frontend` | Vert | Templates Twig, Tailwind |
| `database` | Orange | Entités, migrations, fixtures |
| `auth` | Rouge | Authentification, inscriptions |
| `admin` | Violet | Interfaces d'administration |
| `test` | Gris | Tests PHPUnit |

## 6. Tickets (Issues GitHub)

| # | Titre | Labels | Statut |
|---|---|---|---|
| 1 | Setup projet : Symfony 7.4 + Docker + MySQL | feature, backend | ✅ Done |
| 2 | Configuration Tailwind CSS v3.4 | feature, frontend | ✅ Done |
| 3 | Entités Doctrine + Migrations | feature, database | ✅ Done |
| 4 | Authentification (login/logout/sécurité) | feature, auth | ✅ Done |
| 5 | Workflow d'inscription (DemandeInscription) | feature, auth | ✅ Done |
| 6 | CRUD Domaines (modérateur) | feature, admin | ✅ Done |
| 7 | CRUD Rubriques (admin) | feature, admin | ✅ Done |
| 8 | CRUD Thèmes (admin) | feature, admin | ✅ Done |
| 9 | CRUD Protocoles + Upload PDF | feature, admin, backend | Todo |
| 10 | Navigation publique (Domaine → Protocole) | feature, frontend | Todo |
| 11 | Templates Twig + Layout général | feature, frontend | Todo |
| 12 | DataFixtures (données de test) | feature, database | Todo |
| 13 | Tests PHPUnit | test | Todo |
| 18 | Entité Profession + refactor profession User/DemandeInscription | feature, database | ✅ Done |
| 19 | CRUD Utilisateurs (admin) | feature, admin | Todo |

## 7. Branches Git

| Branche | Ticket | Statut |
|---|---|---|
| `main` | — | Base du projet |
| `feature/1-setup-symfony-docker-mysql` | #1 | ✅ Mergée |
| `feature/2-tailwind-css` | #2 | ✅ Mergée |
| `feature/3-entites-doctrine-migrations` | #3 | ✅ Mergée |
| `feature/18-entite-profession` | #18 | ✅ Mergée |
| `feature/4-authentification` | #4 | ✅ Mergée |
| `feature/5-workflow-inscription` | #5 | ✅ Mergée |
| `feature/6-crud-domaines` | #6 | ✅ Mergée |
| `feature/8-crud-themes` | #8 | ✅ Mergée |
| `feature/7-crud-rubriques` | #7 | ✅ Mergée |

## 8. Modèle de données (ticket #3)

### Hiérarchie du contenu

```
Domaine ↔(ManyToMany)↔ Rubrique ──(OneToMany)──► Thème ──(OneToMany)──► Protocole
```

### Entités créées

| Entité | Table SQL | Description |
|---|---|---|
| `User` | `user` | Utilisateur authentifié (email, rôles, FK → Profession) |
| `DemandeInscription` | `demande_inscription` | Demande d'accès (workflow admin, FK → Profession) |
| `Profession` | `profession` | Profession médicale (liste déroulante, extensible par admin) |
| `Domaine` | `domaine` | Domaine médical (ex : Cardiologie) |
| `Rubrique` | `rubrique` | Regroupement thématique au sein d'un domaine |
| `Theme` | `theme` | Thème au sein d'une rubrique |
| `Protocole` | `protocole` | Protocole médical avec PDF |

### Choix de conception (différences vs v1)

- **Pas d'entité `Admin`** — le rôle admin est géré via `ROLE_ADMIN` dans `User.roles` (plus simple, standard Symfony)
- **`User` au lieu de `Utilisateur`** — nom anglais, convention Symfony MakerBundle
- **`Protocole.titre`** au lieu de `nom` — plus clair pour un protocole médical
- **Slugs sur toutes les entités naviguables** — `Domaine`, `Rubrique`, `Thème`, `Protocole` — pour les URLs propres
- **`UniqueEntity` sur `User.email`** — validation côté formulaire, pas seulement DB
- **`DemandeInscription`** enrichie : `token`, `tokenExpiresAt`, `motifRejet`, `traiteeAt`, lien `OneToOne → User`
- **`Profession` entité dédiée** (ticket #18) — `User.profession` et `DemandeInscription.profession` sont des FK vers `Profession` plutôt que des chaînes libres, pour permettre une liste déroulante extensible par l'admin

## 9. Authentification et rôles (ticket #4)

### Hiérarchie des rôles

```
ROLE_USER
    └── ROLE_MODERATEUR
            └── ROLE_ADMIN
```

Chaque rôle hérite des permissions du rôle inférieur. Configuré via `role_hierarchy` dans `security.yaml`.

### Zones URL par rôle

| Rôle | Préfixe | Périmètre |
|---|---|---|
| `ROLE_USER` | `/profil/*` | Consulter et télécharger les protocoles |
| `ROLE_MODERATEUR` | `/moderateur/*` | CRUD Domaine, Rubrique, Thème, Protocole |
| `ROLE_ADMIN` | `/admin/*` | CRUD Utilisateurs, Professions + tout le reste |

### Composants créés

- `src/Controller/SecurityController.php` — routes `app_login` (`/login`) et `app_logout` (`/logout`)
- `src/Controller/HomeController.php` — route `app_home` (`/`)
- `templates/security/login.html.twig` — formulaire de connexion avec CSRF
- `templates/home/index.html.twig` — page d'accueil
- `templates/base.html.twig` — layout Tailwind complet (navbar, flash messages, footer)

### Choix de conception

- **3 rôles au lieu de 2** — un rôle `ROLE_MODERATEUR` intermédiaire permet aux soignants référents de gérer le contenu sans avoir accès à la gestion des utilisateurs. Différence avec la v1 qui n'avait qu'admin/utilisateur.
- **Provider Doctrine** — Symfony charge les utilisateurs depuis la base via `User.email`. Remplace le provider `users_in_memory` par défaut.
- **CSRF activé sur le formulaire de login** — protection contre les attaques CSRF sur la route d'authentification (`enable_csrf: true`).
- **Redirection post-login vers `app_home`** — `default_target_path: app_home`. Si l'utilisateur tentait d'accéder à une page protégée, Symfony le redirige automatiquement vers cette page après connexion.
- **`UserChecker`** (`src/Security/UserChecker.php`) — vérifie `isVerified` avant chaque connexion. Un utilisateur dont le compte n'est pas encore activé reçoit un message d'erreur clair et ne peut pas se connecter.
- **Double protection obligatoire pour chaque nouveau controller protégé** — `access_control` dans `security.yaml` protège les zones par préfixe d'URL (filet global), mais chaque controller admin/modérateur doit aussi porter `#[IsGranted]` en attribut de classe pour une protection explicite au niveau du code :

```php
#[Route('/moderateur/domaines')]
#[IsGranted('ROLE_MODERATEUR')]
class DomaineController extends AbstractController {}

#[Route('/admin/utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class UtilisateurController extends AbstractController {}
```

## 10. Workflow d'inscription (ticket #5)

### Flux complet

```
[Visiteur] /inscription ──► DemandeInscription (statut=en_attente, emailVerifie=false, token généré)
                ▼ email envoyé
[Visiteur] /inscription/confirmer/{token} ──► emailVerifie=true, token=null
                ▼
[Admin] /admin/demandes ──► liste des demandes email-vérifiées en attente
          ├── Approuver ──► User créé (isVerified=true), statut=approuvee, email de bienvenue
          └── Refuser   ──► motifRejet saisi, statut=refusee, email de refus
```

### Composants créés

- `src/Entity/DemandeInscription.php` — champ `emailVerifie` ajouté
- `src/Form/InscriptionType.php` — prenom, nom, email, profession, plainPassword (RepeatedType)
- `src/Form/RefuserDemandeType.php` — motifRejet (textarea)
- `src/Controller/InscriptionController.php` — routes publiques `/inscription` et `/inscription/confirmer/{token}`
- `src/Controller/Admin/DemandeController.php` — routes `/admin/demandes` avec `#[IsGranted('ROLE_ADMIN')]`
- `src/Repository/DemandeInscriptionRepository.php` — `findEnAttentePourAdmin()`, `findNonVerifiees()`
- Templates inscription : formulaire, succes, email_verifie, token_invalide
- Templates admin : index (deux tableaux : à traiter / email non vérifié), refuser
- Templates emails HTML : confirmation, approbation, refus

### Choix de conception

- **`emailVerifie` séparé du `statut`** — le statut (`en_attente/approuvee/refusee`) est une décision admin ; la vérification email est une étape technique distincte. L'admin ne voit que les demandes avec `emailVerifie=true`.
- **Mot de passe haché dès la soumission** — stocké dans `DemandeInscription.password` et copié tel quel vers `User.password` lors de l'approbation. Évite de stocker un mot de passe en clair.
- **CSRF sur le bouton Approuver** — action irréversible (création d'un compte), protégée via `isCsrfTokenValid()`.
- **`MAILER_DSN=null://null`** — en développement, les emails sont interceptés et visibles dans le Symfony Profiler (onglet "Emails"). Aucun email n'est réellement envoyé.
- **Vérification de doublon email** — avant de créer la demande, on vérifie qu'il n'existe ni `User` ni `DemandeInscription en_attente` avec le même email.

## 11. CRUD Domaines (ticket #6)

### Composants créés

- `src/Form/DomaineType.php` — champs `nom` (obligatoire) et `description` (optionnelle)
- `src/Controller/Moderateur/DomaineController.php` — routes `/moderateur/domaines` avec `#[IsGranted('ROLE_MODERATEUR')]`
- `templates/moderateur/domaine/index.html.twig` — liste avec boutons Modifier / Supprimer
- `templates/moderateur/domaine/new.html.twig` — formulaire de création
- `templates/moderateur/domaine/edit.html.twig` — formulaire d'édition

### Routes

| Nom | Méthode | URL |
|---|---|---|
| `moderateur_domaine_index` | GET | `/moderateur/domaines` |
| `moderateur_domaine_new` | GET/POST | `/moderateur/domaines/nouveau` |
| `moderateur_domaine_edit` | GET/POST | `/moderateur/domaines/{id}/modifier` |
| `moderateur_domaine_delete` | POST | `/moderateur/domaines/{id}/supprimer` |

### Choix de conception

- **Slug auto-généré** — calculé depuis le `nom` via `AsciiSlugger('fr')` à chaque création/modification. Pas exposé dans le formulaire.
- **CSRF sur la suppression** — token `delete_domaine_{id}` validé côté controller. Le bouton Supprimer est dans un `<form>` POST inline avec confirmation JavaScript.
- **Lien "Modération" dans la navbar** — pointe désormais vers `moderateur_domaine_index` (remplace le `/moderateur` en dur).

## 12. CRUD Rubriques (ticket #7)

### Composants créés

- `src/Form/RubriqueType.php` — champs `nom`, `description` (optionnelle), `domaines` (EntityType multiple, cases à cocher)
- `src/Controller/Moderateur/RubriqueController.php` — routes `/moderateur/rubriques` avec `#[IsGranted('ROLE_MODERATEUR')]`
- `templates/moderateur/rubrique/index.html.twig` — liste avec domaines en badges et nb thèmes, boutons Modifier / Supprimer
- `templates/moderateur/rubrique/new.html.twig` — formulaire de création
- `templates/moderateur/rubrique/edit.html.twig` — formulaire d'édition

### Routes

| Nom | Méthode | URL |
|---|---|---|
| `moderateur_rubrique_index` | GET | `/moderateur/rubriques` |
| `moderateur_rubrique_new` | GET/POST | `/moderateur/rubriques/nouveau` |
| `moderateur_rubrique_edit` | GET/POST | `/moderateur/rubriques/{id}/modifier` |
| `moderateur_rubrique_delete` | POST | `/moderateur/rubriques/{id}/supprimer` |

### Choix de conception

- **ManyToMany Domaine/Rubrique via cases à cocher** — `EntityType` avec `multiple=true` et `expanded=true`. Plus ergonomique qu'un `<select multiple>` pour une liste courte de domaines.
- **Domaines en badges dans la liste** — chaque domaine associé est affiché comme un tag gris dans le tableau index.
- **Slug auto-généré** — même logique que les autres entités, via `AsciiSlugger('fr')`.
- **CSRF sur la suppression** — token `delete_rubrique_{id}`.
- **Navbar mise à jour** — ajout du lien "Rubriques" entre "Domaines" et "Thèmes".

## 13. CRUD Thèmes (ticket #8)

### Composants créés

- `src/Form/ThemeType.php` — champs `nom` (obligatoire) et `rubrique` (EntityType, obligatoire)
- `src/Controller/Moderateur/ThemeController.php` — routes `/moderateur/themes` avec `#[IsGranted('ROLE_MODERATEUR')]`
- `templates/moderateur/theme/index.html.twig` — liste avec rubrique parente, boutons Modifier / Supprimer
- `templates/moderateur/theme/new.html.twig` — formulaire de création
- `templates/moderateur/theme/edit.html.twig` — formulaire d'édition

### Routes

| Nom | Méthode | URL |
|---|---|---|
| `moderateur_theme_index` | GET | `/moderateur/themes` |
| `moderateur_theme_new` | GET/POST | `/moderateur/themes/nouveau` |
| `moderateur_theme_edit` | GET/POST | `/moderateur/themes/{id}/modifier` |
| `moderateur_theme_delete` | POST | `/moderateur/themes/{id}/supprimer` |

### Choix de conception

- **`rubrique` obligatoire dans le formulaire** — un thème sans rubrique parente n'a pas de sens dans la hiérarchie. `EntityType` avec placeholder vide + contrainte `NotBlank`.
- **Slug auto-généré** — même logique que les Domaines, via `AsciiSlugger('fr')`.
- **CSRF sur la suppression** — token `delete_theme_{id}`.
- **Navbar mise à jour** — lien "Modération" remplacé par deux liens séparés "Domaines" et "Thèmes".

## 13. Leçons apprises

### Ordre de création d'un projet

**À éviter :** Créer le repo GitHub avec README avant d'installer Symfony — les deux outils refusent un dossier non vide.

**Bon ordre pour les prochains projets :**
```
1. symfony new mon-projet --version="7.4.*" --webapp
2. cd mon-projet
3. git remote add origin https://github.com/MonCompte/mon-projet.git
4. git push -u origin main
```

### Page d'accueil Symfony par défaut

La page `/` générée par Symfony (`templates/bundles/TwigBundle/Exception/error.html.twig`) n'étend pas `base.html.twig` et injecte son propre CSS. Tester Tailwind visuellement n'est possible qu'avec de vraies templates (ticket #11).

### `git add .` vs fichiers spécifiques

`git add .` est sûr si `.gitignore` est correct (il exclut `.env.local`, `var/`, etc.). Lister les fichiers un par un est plus rigoureux mais rarement nécessaire.

### PR via GitHub CLI vs interface web

- `gh pr create` → crée uniquement la PR
- `gh pr merge --merge --delete-branch` → merge + supprime la branche source
- Supprimer une branche mergée est sans risque (le code est dans `main`)
- `gh pr create` échoue avec les caractères spéciaux (accents, `→`) dans PowerShell — utiliser l'interface web GitHub à la place.

### Commit messages multi-lignes en PowerShell

PowerShell 5.1 ne supporte pas les heredocs bash (`<<'EOF'`). Utiliser les here-strings PowerShell :
```powershell
git commit -m @'
Mon message
sur plusieurs lignes
'@
```
