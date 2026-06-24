# Guide d'installation — lereperedesprotocoles-v2

Ce guide explique comment récupérer et faire tourner le projet sur une machine Windows from scratch.

---

## Prérequis

Installer les outils suivants dans cet ordre :

| Outil | Version testée | Lien |
|---|---|---|
| **Laragon** | 2026 v8.6.1 | https://laragon.org/download/ |
| **PHP** | 8.3 | Inclus dans Laragon |
| **MySQL** | 8.4 | Inclus dans Laragon |
| **Composer** | 2.x | https://getcomposer.org/download/ |
| **Symfony CLI** | 5.x | https://symfony.com/download |
| **Git** | 2.x | https://git-scm.com/downloads |
| **Node.js** | 18+ | https://nodejs.org/ (pour Tailwind) |

**Vérification rapide (PowerShell) :**
```powershell
php -v
composer -V
symfony version
git --version
node -v
```

---

## 1. Cloner le dépôt

```powershell
cd C:\dev
git clone https://github.com/MaximeSauvageREPERE/lereperedesprotocoles-v2.git
cd lereperedesprotocoles-v2
```

---

## 2. Installer les dépendances PHP

```powershell
composer install
```

Cette commande lit `composer.json` et télécharge tous les packages dans `vendor/`. Elle exécute aussi automatiquement les scripts post-install (cache clear, installation des assets JS via importmap).

---

## 3. Configurer l'environnement

Créer un fichier `.env.local` à la racine du projet (ce fichier ne sera jamais commité) :

```powershell
copy .env .env.local
```

Puis ouvrir `.env.local` et adapter ces deux variables :

```env
APP_SECRET=une_chaine_aleatoire_de_32_caracteres_minimum

DATABASE_URL="mysql://root:@127.0.0.1:3306/lereperedesprotocoles_v2?serverVersion=8.4&charset=utf8mb4"
```

**Explication des variables :**

- `APP_SECRET` : clé secrète utilisée par Symfony pour signer les cookies et les tokens CSRF. Générer une valeur avec : `php -r "echo bin2hex(random_bytes(20));"`
- `DATABASE_URL` : chaîne de connexion MySQL.
  - `root` : utilisateur MySQL de Laragon (pas de mot de passe par défaut → `:@`)
  - `127.0.0.1:3306` : MySQL tourne localement sur le port 3306
  - `lereperedesprotocoles_v2` : nom de la base à créer

**Avec Laragon**, MySQL démarre automatiquement. Les identifiants par défaut sont `root` sans mot de passe.

---

## 4. Créer la base de données

S'assurer que Laragon est démarré (MySQL actif), puis :

```powershell
php bin/console doctrine:database:create
```

Résultat attendu : `Created database "lereperedesprotocoles_v2"`.

---

## 5. Exécuter les migrations

Les migrations créent toutes les tables à partir de l'historique versionné :

```powershell
php bin/console doctrine:migrations:migrate
```

Taper `yes` pour confirmer. Les migrations s'exécutent dans l'ordre et créent les tables `profession`, `user`, `demande_inscription` (avec la colonne `email_verifie`), `domaine`, `rubrique`, `theme`, `protocole` et la table de liaison `rubrique_domaine`.

**Vérification :**
```powershell
php bin/console doctrine:schema:validate
```
Les deux lignes doivent afficher `[OK]`.

---

## 6. Créer les dossiers d'upload

VichUploader a besoin que les dossiers de destination existent (ils sont dans `.gitignore` donc non commités) :

```powershell
mkdir public\uploads\protocoles\pdf
mkdir public\uploads\protocoles\images
```

---

## 7. Installer les assets JavaScript

```powershell
php bin/console importmap:install
```

Cette commande télécharge les dépendances JS déclarées dans `importmap.php` (Stimulus, Turbo) dans `assets/vendor/`.

---

## 8. Compiler Tailwind CSS

Le projet utilise Tailwind via `symfonycasts/tailwind-bundle`. La compilation se fait à la volée en développement :

```powershell
# Option A : compilation en arrière-plan (recommandé en dev)
php bin/console tailwind:build --watch
```

Ou lancer une compilation unique avant de démarrer le serveur :

```powershell
# Option B : compilation unique
php bin/console tailwind:build
```

---

## 9. Démarrer le serveur de développement

Dans un terminal séparé (pour garder Tailwind en watch dans le premier) :

```powershell
symfony server:start
```

Ouvrir http://127.0.0.1:8000 dans le navigateur.

La toolbar Symfony (barre de débogage en bas de page) doit apparaître — c'est le signe que tout fonctionne.

---

## Récapitulatif des commandes

```powershell
# Cloner et entrer dans le dossier
git clone https://github.com/MaximeSauvageREPERE/lereperedesprotocoles-v2.git
cd lereperedesprotocoles-v2

# Dépendances
composer install
php bin/console importmap:install

# Base de données (Laragon doit être démarré)
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Dossiers d'upload (non commités, à créer manuellement)
mkdir public\uploads\protocoles\pdf
mkdir public\uploads\protocoles\images

# Vérification
php bin/console doctrine:schema:validate

# Démarrage (deux terminaux)
php bin/console tailwind:build --watch   # terminal 1
symfony server:start                     # terminal 2
```

---

## Structure du projet

```
lereperedesprotocoles-v2/
├── assets/               Assets JS et CSS source
│   ├── app.js            Point d'entrée JS (Stimulus + Turbo)
│   └── styles/app.css    Point d'entrée CSS (directives Tailwind)
├── config/               Configuration Symfony
│   └── packages/         Un fichier YAML par bundle
├── docs/                 Cette documentation
├── migrations/           Historique des migrations Doctrine
├── public/               Racine web (seul dossier exposé)
├── src/
│   ├── Controller/       Controllers HTTP
│   ├── Entity/           Entités Doctrine (modèle de données)
│   └── Repository/       Classes de requêtes Doctrine
├── templates/            Templates Twig
├── tests/                Tests PHPUnit
├── .env                  Variables d'environnement par défaut (commité)
├── .env.local            Surcharges locales (non commité, à créer)
└── composer.json         Dépendances PHP
```

---

## Stack technique

| Couche | Technologie | Version |
|---|---|---|
| Langage | PHP | 8.3 |
| Framework | Symfony | 7.4 |
| ORM | Doctrine | 3.x |
| Base de données | MySQL | 8.4 |
| CSS | Tailwind CSS | 3.4 |
| JS | Stimulus + Turbo (Hotwired) | via AssetMapper |
| Upload | Vich UploaderBundle | 2.9 |
| Serveur local | Laragon | 2026 / v8.6.1 |

---

## Problèmes fréquents

**`composer install` échoue avec une erreur d'extension PHP**  
Vérifier que les extensions `pdo_mysql`, `ctype`, `iconv` sont activées dans `php.ini` (Laragon : Menu → PHP → Extensions).

**`doctrine:database:create` : accès refusé**  
Vérifier la valeur de `DATABASE_URL` dans `.env.local`. Avec Laragon, l'utilisateur est `root` sans mot de passe : `mysql://root:@127.0.0.1:3306/...`

**`tailwind:build` : Node not found**  
Node.js doit être installé et accessible dans le PATH. Relancer PowerShell après l'installation de Node.

**Page blanche ou erreur 500 après `git pull`**  
Lancer `composer install` (une dépendance a peut-être été ajoutée) puis `php bin/console doctrine:migrations:migrate` (une migration a peut-être été ajoutée).

**Emails non reçus en développement**  
C'est normal. `MAILER_DSN=null://null` intercepte tous les emails. Les consulter dans le Symfony Profiler : barre de débogage en bas → onglet "Emails".
