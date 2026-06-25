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
| 9 | CRUD Protocoles + Upload PDF | feature, admin, backend | ✅ Done |
| 10 | Navigation publique (Domaine → Protocole) | feature, frontend | ✅ Done |
| 11 | Templates Twig + Layout général | feature, frontend | ✅ Done |
| 12 | DataFixtures (données de test) | feature, database | ✅ Done |
| 13 | Tests PHPUnit | test | Todo |
| 33 | Frontend : Responsive mobile-first | feature, frontend | ✅ Done |
| 18 | Entité Profession + refactor profession User/DemandeInscription | feature, database | ✅ Done |
| 19 | CRUD Utilisateurs (admin) | feature, admin | ✅ Done |
| 29 | CRUD Professions (admin) | feature, admin | ✅ Done |

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
| `feature/9-crud-protocoles` | #9 | ✅ Mergée |
| `feature/10-navigation-publique` | #10 | ✅ Mergée |
| `feature/19-crud-utilisateurs` | #19 | ✅ Mergée |
| `feature/29-crud-professions` | #29 | ✅ Mergée |
| `feature/33-responsive-mobile-first` | #33 | ✅ Mergée |

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

## 14. CRUD Protocoles (ticket #9)

### Composants créés

- `src/Form/ProtocoleType.php` — champs `titre`, `description`, `theme` (EntityType), `pdfFile` (VichFileType), `imageFile` (VichImageType)
- `src/Controller/Moderateur/ProtocoleController.php` — routes `/moderateur/protocoles` avec `#[IsGranted('ROLE_MODERATEUR')]`
- `templates/moderateur/protocole/index.html.twig` — liste avec thème, domaines associés, liens PDF, miniature image
- `templates/moderateur/protocole/new.html.twig` — formulaire de création avec upload
- `templates/moderateur/protocole/edit.html.twig` — formulaire d'édition avec aperçu des fichiers existants
- `config/packages/vich_uploader.yaml` — mappings `protocole_pdf` et `protocole_image`
- `public/uploads/protocoles/pdf/` et `public/uploads/protocoles/images/` — dossiers de destination (dans `.gitignore`)

### Routes

| Nom | Méthode | URL |
|---|---|---|
| `moderateur_protocole_index` | GET | `/moderateur/protocoles` |
| `moderateur_protocole_new` | GET/POST | `/moderateur/protocoles/nouveau` |
| `moderateur_protocole_edit` | GET/POST | `/moderateur/protocoles/{id}/modifier` |
| `moderateur_protocole_delete` | POST | `/moderateur/protocoles/{id}/supprimer` |

### Choix de conception

- **VichUploaderBundle v2.9** — gère automatiquement le déplacement des fichiers, le renommage unique (`SmartUniqueNamer`) et la suppression sur `remove()`. Utiliser le namespace `Vich\UploaderBundle\Mapping\Attribute` (pas `Annotation`, deprecated en v2.9).
- **Deux mappings séparés** — `protocole_pdf` (10 Mo max, PDF uniquement) et `protocole_image` (20 Mo max, JPG/PNG/WebP). Dossiers de destination distincts pour faciliter la gestion.
- **`updatedAt` mis à jour dans les setters** — requis par VichUploader pour détecter les changements de fichier lors des éditions (`setXxxFile()` met à jour `updatedAt`).
- **Domaines affichés dans l'index** — dérivés via `protocole.theme.rubrique.domaines`, pas de relation directe en base. Affichés en badges gris sous le nom du thème.
- **Navbar mise à jour** — ajout du lien "Protocoles" après "Thèmes".
- **Syntaxe des contraintes** — Symfony 7.4 impose les named arguments : `new Length(min: 2, max: 255)` au lieu de `new Length(['min' => 2])`. Corrigé sur tous les FormTypes.

## 15. Navigation publique (ticket #10)

### Composants créés

- `src/Controller/NavigationController.php` — 5 actions, `#[IsGranted('ROLE_USER')]`
- `templates/navigation/domaines.html.twig` — grille de cards, liste tous les domaines
- `templates/navigation/domaine.html.twig` — rubriques d'un domaine
- `templates/navigation/rubrique.html.twig` — thèmes d'une rubrique
- `templates/navigation/theme.html.twig` — protocoles d'un thème (cards avec image miniature)
- `templates/navigation/protocole.html.twig` — fiche protocole (image zoomable + téléchargement PDF)

### Routes

| Nom | Méthode | URL |
|---|---|---|
| `navigation_domaines` | GET | `/parcourir` |
| `navigation_domaine` | GET | `/domaines/{slug}` |
| `navigation_rubrique` | GET | `/rubriques/{slug}` |
| `navigation_theme` | GET | `/themes/{slug}` |
| `navigation_protocole` | GET | `/protocoles/{slug}` |

### Choix de conception

- **`#[IsGranted('ROLE_USER')]` sur la classe** — toutes les pages requièrent une connexion. Un visiteur non connecté est automatiquement redirigé vers `/login`.
- **Résolution par slug via `findOneBy`** — explicite et fiable. Les routes utilisent `{slug}` et chaque action appelle `$repo->findOneBy(['slug' => $slug])`.
- **Fil d'Ariane cliquable** — reconstruit la hiérarchie complète (Domaines → Domaine → Rubrique → Thème → Protocole) depuis les relations Doctrine, sans donnée supplémentaire en base.
- **Image zoomable** — l'image bannière est enveloppée dans un `<a target="_blank">` avec un overlay au survol ("Voir en taille réelle"). Aucun JS requis.
- **Téléchargement PDF** — attribut HTML `download` pour forcer le téléchargement sans ouvrir d'onglet vide.
- **Lien "Parcourir" dans la navbar** — visible pour tous les utilisateurs connectés (ROLE_USER et au-dessus).

## 16. CRUD Utilisateurs (ticket #19)

### Composants créés

- `src/Form/UtilisateurType.php` — champs `prenom`, `nom`, `email`, `profession` (EntityType), `niveau` (ChoiceType non mappé), `isVerified`, `plainPassword` (non mappé, optionnel)
- `src/Controller/Admin/UtilisateurController.php` — routes `/admin/utilisateurs` avec `#[IsGranted('ROLE_ADMIN')]`
- `templates/admin/utilisateur/index.html.twig` — tableau avec badges rôle/statut, boutons Modifier / Supprimer
- `templates/admin/utilisateur/edit.html.twig` — formulaire d'édition complet

### Routes

| Nom | Méthode | URL |
|---|---|---|
| `admin_utilisateur_index` | GET | `/admin/utilisateurs` |
| `admin_utilisateur_edit` | GET/POST | `/admin/utilisateurs/{id}/modifier` |
| `admin_utilisateur_delete` | POST | `/admin/utilisateurs/{id}/supprimer` |

### Commandes utilisées

```powershell
# Générer le hash d'un mot de passe pour insérer un compte admin en base
php bin/console security:hash-password administrateur
```

```sql
-- Créer le premier compte admin manuellement (aucun workflow de création admin dans l'appli)
INSERT INTO `user` (email, roles, password, prenom, nom, profession_id, is_verified, created_at)
VALUES (
  'admin@test.fr',
  '["ROLE_ADMIN"]',
  '$2y$13$...hash...',
  'Admin',
  'Test',
  (SELECT id FROM profession LIMIT 1),
  1,
  NOW()
);
```

### Choix de conception

- **Pas de route "new"** — les utilisateurs sont créés uniquement via le workflow d'inscription (`/admin/demandes`).
- **Champ `niveau` non mappé** — le rôle est stocké sous forme de tableau en base (`["ROLE_ADMIN"]`). Le formulaire expose un select simple (Utilisateur / Modérateur / Admin) ; le contrôleur pré-remplit ce champ avec le rôle le plus élevé de l'utilisateur et le reconvertit en tableau à la sauvegarde.
- **Changement de mot de passe optionnel** — champ `plainPassword` non mappé. Si laissé vide, le mot de passe existant est conservé.
- **Suppression de son propre compte bloquée** — le contrôleur vérifie `$utilisateur === $this->getUser()` et affiche un flash error.
- **Navbar admin** — lien "Utilisateurs" ajouté. Le bloc `if/elseif` a été remplacé par deux `if` indépendants pour que l'admin voie aussi les liens modérateur (Domaines, Rubriques, Thèmes, Protocoles), auxquels il a déjà accès via la hiérarchie des rôles.

## 17. CRUD Professions (ticket #29)

### Composants créés

- `src/Form/ProfessionType.php` — champ `nom` uniquement, slug non exposé dans le formulaire
- `src/Controller/Admin/ProfessionController.php` — routes `/admin/professions` avec `#[IsGranted('ROLE_ADMIN')]`
- `templates/admin/profession/index.html.twig` — tableau avec slug, nb utilisateurs, nb demandes ; bouton Supprimer grisé si liés
- `templates/admin/profession/new.html.twig` — formulaire de création
- `templates/admin/profession/edit.html.twig` — formulaire d'édition avec slug actuel affiché

### Routes

| Nom | Méthode | URL |
|---|---|---|
| `admin_profession_index` | GET | `/admin/professions` |
| `admin_profession_new` | GET/POST | `/admin/professions/nouveau` |
| `admin_profession_edit` | GET/POST | `/admin/professions/{id}/modifier` |
| `admin_profession_delete` | POST | `/admin/professions/{id}/supprimer` |

### Choix de conception

- **Slug auto-généré** — calculé depuis le `nom` via `AsciiSlugger('fr')` à chaque création/modification. Pas exposé dans le formulaire.
- **Suppression bloquée si liée** — le contrôleur vérifie `$profession->getUsers()->count() > 0 || $profession->getDemandesInscription()->count() > 0` et retourne un flash error. Dans l'index, le bouton Supprimer est également grisé visuellement si la profession est référencée.
- **Lien "Professions" dans la navbar admin** — ajouté après "Utilisateurs" dans le bloc `{% if is_granted('ROLE_ADMIN') %}`.

## 18. Templates Twig + Layout général (ticket #11)

### Composants créés

- `src/Controller/ProfilController.php` — route `/profil` avec `#[IsGranted('ROLE_USER')]`
- `templates/profil/index.html.twig` — fiche utilisateur (avatar initiales, nom, email, profession, rôle badgé, date d'inscription)
- `templates/home/index.html.twig` — hero avec CTAs adaptatifs selon le rôle
- `templates/base.html.twig` — nom de l'utilisateur dans la navbar rendu cliquable (lien vers `/profil`)

### Routes

| Nom | Méthode | URL |
|---|---|---|
| `profil_index` | GET | `/profil` |

### Choix de conception

- **Avatar initiales** — cercle CSS avec les initiales prénom + nom (`|first|upper`), aucun upload d'image requis.
- **CTAs adaptatifs sur la home** — visiteur non connecté voit "Se connecter" / "Demander l'accès" ; user voit "Parcourir" + "Mon profil" ; modérateur voit aussi "Gérer les contenus" ; admin voit aussi "Administration".
- **Nom cliquable dans la navbar** — le `<span>` du nom a été remplacé par un `<a href="{{ path('profil_index') }}">` pour un accès rapide au profil depuis n'importe quelle page.
- **Zone `/profil` déjà déclarée dans `security.yaml`** — l'`access_control` `{ path: ^/profil, roles: ROLE_USER }` était en place depuis le ticket #4 ; le controller vient simplement la couvrir.

## 19. DataFixtures (ticket #12)

### Composants créés

- `src/DataFixtures/ProfessionFixtures.php` — 5 professions (Médecin généraliste, Infirmier, Aide-soignant, Kinésithérapeute, Pharmacien)
- `src/DataFixtures/UserFixtures.php` — 3 comptes de test avec mots de passe hashés, `isVerified: true`
- `src/DataFixtures/DomaineFixtures.php` — 3 domaines (Cardiologie, Neurologie, Urgences)
- `src/DataFixtures/RubriqueFixtures.php` — 3 rubriques reliées aux domaines via ManyToMany
- `src/DataFixtures/ThemeFixtures.php` — 5 thèmes
- `src/DataFixtures/ProtocoleFixtures.php` — 5 protocoles (sans fichiers PDF/image)

### Comptes de test

| Email | Mot de passe | Rôle |
|---|---|---|
| admin@test.fr | administrateur | ROLE_ADMIN |
| modo@test.fr | moderateur | ROLE_MODERATEUR |
| user@test.fr | utilisateur | ROLE_USER |

### Commande

```powershell
php bin/console doctrine:fixtures:load
```

### Bug corrigé — logout déclenché par Turbo

Symfony UX Turbo précharge les liens `<a>` visibles dans la navbar. Le lien de déconnexion était un `<a href="/logout">` (GET) — Turbo le préchargeait automatiquement après le login, déconnectant l'utilisateur immédiatement.

**Correction :** remplacement du lien par un formulaire POST avec token CSRF, et ajout de `csrf_token_manager` dans `security.yaml`.

### Choix de conception

- **`DependentFixtureInterface`** — chaîne les fixtures dans le bon ordre (Profession → User, Domaine → Rubrique → Thème → Protocole) via `getDependencies()`.
- **`addReference` / `getReference`** — partage les entités entre classes de fixtures (ex. `UserFixtures` récupère la `Profession` créée par `ProfessionFixtures`).
- **Protocoles sans fichiers** — les fixtures ne gèrent pas les uploads VichUploader. Les champs `pdfFilename` et `imageFilename` restent `null`.
- **`isVerified: true`** — tous les comptes de test sont directement activés, sans passer par le workflow d'inscription.

## 20. Responsive mobile-first (ticket #33)

### Composants créés / modifiés

- `assets/controllers/navbar_controller.js` — controller Stimulus pour le toggle hamburger (icône ≡ ↔ ✕)
- `templates/base.html.twig` — navbar restructurée en 3 niveaux

### Comportement navbar

| Breakpoint | Comportement |
|---|---|
| `< md` (< 768px) — téléphone | Logo + bouton hamburger. Menu déroulant avec avatar, Parcourir, sections Admin/Modo, Déconnexion |
| `md` à `lg` (768px–1023px) — tablette | Logo + nom utilisateur + Parcourir en ligne + hamburger pour le reste |
| `lg+` (≥ 1024px) — ordinateur | Barre horizontale complète (comportement original) |

### Autres corrections responsive

- **Tables CRUD** (7 fichiers) — enveloppées dans `<div class="overflow-x-auto">` à l'intérieur du wrapper `overflow-hidden`, pour préserver les coins arrondis tout en permettant le scroll horizontal sur mobile
- **Formulaires à 2 colonnes** — `grid-cols-2` → `grid-cols-1 sm:grid-cols-2` sur `inscription/formulaire.html.twig` et `admin/utilisateur/edit.html.twig`
- **En-têtes titre+bouton** — `flex justify-between` → `flex flex-col sm:flex-row` sur les 5 pages index avec un bouton "Nouveau"
- **Home** — `text-4xl` → `text-2xl sm:text-4xl`, `py-16` → `py-8 sm:py-16`
- **Profil** — padding et taille de l'avatar réduits sur mobile

### Choix de conception

- **Stimulus pour le hamburger** — le toggle est géré par `navbar_controller.js` via `classList.toggle('hidden')`. Deux icônes SVG (≡ et ✕) sont permutées à chaque clic via les targets Stimulus `iconOpen` / `iconClose`.
- **`lg:hidden` permanent sur le dropdown** — même si le JS ouvre le menu, `lg:hidden` le masque sur ordinateur. Aucun risque d'affichage parasite sur grand écran.
- **`overflow-x-auto` imbriqué** — le wrapper externe garde `overflow-hidden` pour clipper les coins arrondis du card ; le wrapper interne porte `overflow-x-auto` pour le scroll. Les deux sont nécessaires.

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
