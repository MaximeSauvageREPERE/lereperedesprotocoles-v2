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

## 6. Créer un compte administrateur

L'application ne fournit pas de compte par défaut. Créer un compte admin manuellement :

**Étape 1 — Générer le hash du mot de passe :**
```powershell
php bin/console security:hash-password votre_mot_de_passe
```

**Étape 2 — Insérer l'utilisateur en base (MySQL / Laragon) :**
```sql
INSERT INTO `user` (email, roles, password, prenom, nom, profession_id, is_verified, created_at)
VALUES (
  'admin@example.fr',
  '["ROLE_ADMIN"]',
  'HASH_GENERE_ETAPE_1',
  'Admin',
  'Test',
  (SELECT id FROM profession LIMIT 1),
  1,
  NOW()
);
```

> **Note :** `(SELECT id FROM profession LIMIT 1)` suppose qu'au moins une profession existe en base. Si la table est vide, insérer d'abord une profession :
> ```sql
> INSERT INTO profession (nom, slug) VALUES ('Médecin', 'medecin');
> ```
> Une fois connecté en admin, les professions se gèrent directement via l'interface : `/admin/professions`.

---

## 7. Charger les données de test (fixtures)

Le projet inclut des fixtures Doctrine qui peuplent la base avec des données de test :

```powershell
php bin/console doctrine:fixtures:load
```

> **Attention :** cette commande purge toutes les tables avant de recharger. Taper `yes` pour confirmer.

**Comptes créés :**

| Email | Mot de passe | Rôle |
|---|---|---|
| admin@test.fr | administrateur | ROLE_ADMIN |
| modo@test.fr | moderateur | ROLE_MODERATEUR |
| user@test.fr | utilisateur | ROLE_USER |

**Données de contenu créées :** 5 professions, 3 domaines, 3 rubriques, 5 thèmes, 5 protocoles.

---

## 8. Créer les dossiers d'upload

VichUploader a besoin que les dossiers de destination existent (ils sont dans `.gitignore` donc non commités) :

```powershell
mkdir public\uploads\protocoles\pdf
mkdir public\uploads\protocoles\images
```

---

## 9. Installer les assets JavaScript

```powershell
php bin/console importmap:install
```

Cette commande télécharge les dépendances JS déclarées dans `importmap.php` (Stimulus, Turbo) dans `assets/vendor/`.

---

## 10. Compiler Tailwind CSS

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

## 11. Démarrer le serveur de développement

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

## Outils qualité

### PHPStan — analyse statique

```powershell
php bin/console cache:warmup   # nécessaire pour que PHPStan lise le container Symfony
vendor/bin/phpstan analyse     # doit retourner "No errors"
```

La configuration se trouve dans `phpstan.dist.neon` (niveau 5).

### PHP CS Fixer — formatage du code

```powershell
vendor/bin/php-cs-fixer fix --dry-run --diff   # prévisualiser les changements
vendor/bin/php-cs-fixer fix                    # appliquer les corrections
```

La configuration se trouve dans `.php-cs-fixer.dist.php` (règles `@Symfony`, cible `src/` et `tests/`).  
Le fichier `.php-cs-fixer.cache` est gitignorés (accélère les relances).

### GitHub Actions CI

Chaque push et chaque pull request sur `main` déclenchent automatiquement le pipeline défini dans `.github/workflows/ci.yml`.

**Jobs exécutés en parallèle :**

| Job | Étapes |
|---|---|
| **Qualité du code** | PHP CS Fixer · PHPStan · PHPUnit Unit |
| **Tests fonctionnels** | MySQL 8.0 · Création BDD/schéma/fixtures · PHPUnit Functional |

**Voir les résultats :** onglet *Actions* du dépôt GitHub. Le badge en haut du README reflète le statut du dernier run sur `main`.

---

### PHPUnit — tests unitaires et fonctionnels

```powershell
vendor/bin/phpunit --testsuite Unit            # tests unitaires (pas de BDD requise)
vendor/bin/phpunit --testsuite Functional      # tests fonctionnels (BDD test requise)
vendor/bin/phpunit                             # tous les tests
vendor/bin/phpunit --testdox                   # affichage lisible des noms de tests
```

La configuration se trouve dans `phpunit.xml.dist`.

**Initialisation de la BDD de test (une seule fois) :**

```powershell
# 1. Copier le fichier de template et l'adapter avec tes credentials
copy .env.test.local.dist .env.test.local
# Éditer .env.test.local : remplacer TON_USER/TON_PASSWORD par tes credentials MySQL

# 2. Créer la BDD et charger le schéma + fixtures
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:schema:create --env=test
php bin/console doctrine:fixtures:load --env=test --no-interaction
```

Le dossier `.phpunit.cache/`, le fichier `phpunit.xml` (overrides locaux) et `.env.test.local` sont gitignorés.

---

## Problèmes fréquents

**`composer require` échoue avec "zip extension and unzip/7z commands are both missing"**  
Activer l'extension `zip` dans `php.ini` : trouver la ligne `;extension=zip` et retirer le `;`. Le fichier `php.ini` est dans `C:\laragon\bin\php\php-8.3.x\php.ini`.

**`composer install` échoue avec une erreur d'extension PHP**  
Vérifier que les extensions `pdo_mysql`, `ctype`, `iconv`, `zip` sont activées dans `php.ini` (Laragon : Menu → PHP → Extensions).

**`doctrine:database:create` : accès refusé**  
Vérifier la valeur de `DATABASE_URL` dans `.env.local`. Avec Laragon, l'utilisateur est `root` sans mot de passe : `mysql://root:@127.0.0.1:3306/...`

**`tailwind:build` : Node not found**  
Node.js doit être installé et accessible dans le PATH. Relancer PowerShell après l'installation de Node.

**Page blanche ou erreur 500 après `git pull`**  
Lancer `composer install` (une dépendance a peut-être été ajoutée) puis `php bin/console doctrine:migrations:migrate` (une migration a peut-être été ajoutée).

**Emails non reçus en développement**  
C'est normal. `MAILER_DSN=null://null` intercepte tous les emails. Les consulter dans le Symfony Profiler : barre de débogage en bas → onglet "Emails".  
Voir [docs/emails.md](emails.md) pour le détail complet du système d'emails.
