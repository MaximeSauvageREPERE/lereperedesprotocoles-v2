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
| 3 | Entités Doctrine + Migrations | feature, database | 🔵 In Progress |
| 4 | Authentification (login/logout/sécurité) | feature, auth | Todo |
| 5 | Workflow d'inscription (DemandeInscription) | feature, auth | Todo |
| 6 | CRUD Domaines (admin) | feature, admin | Todo |
| 7 | CRUD Thèmes (admin) | feature, admin | Todo |
| 8 | CRUD Rubriques (admin) | feature, admin | Todo |
| 9 | CRUD Protocoles + Upload PDF | feature, admin, backend | Todo |
| 10 | Navigation publique (Domaine → Protocole) | feature, frontend | Todo |
| 11 | Templates Twig + Layout général | feature, frontend | Todo |
| 12 | DataFixtures (données de test) | feature, database | Todo |
| 13 | Tests PHPUnit | test | Todo |

## 7. Branches Git

| Branche | Ticket | Statut |
|---|---|---|
| `main` | — | Base du projet |
| `feature/1-setup-symfony-docker-mysql` | #1 | ✅ Mergée |
| `feature/2-tailwind-css` | #2 | ✅ Mergée |
| `feature/3-entites-doctrine-migrations` | #3 | 🔵 Active |

## 8. Modèle de données (ticket #3)

### Hiérarchie du contenu

```
Domaine ↔(ManyToMany)↔ Rubrique ──(OneToMany)──► Thème ──(OneToMany)──► Protocole
```

### Entités créées

| Entité | Table SQL | Description |
|---|---|---|
| `User` | `user` | Utilisateur authentifié (email, rôles, profession) |
| `DemandeInscription` | `demande_inscription` | Demande d'accès (workflow admin) |
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
- **`DemandeInscription`** enrichie : `profession`, `token`, `tokenExpiresAt`, `motifRejet`, `traiteeAt`, lien `OneToOne → User`

## 9. Leçons apprises

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
