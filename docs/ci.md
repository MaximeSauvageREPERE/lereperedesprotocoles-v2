# Comprendre la CI avec GitHub Actions

## Pourquoi la CI ?

Sans CI, pour vérifier qu'une branche ne casse rien avant de la merger, il faut :

1. Tirer la branche en local
2. Lancer PHP CS Fixer manuellement
3. Lancer PHPStan manuellement
4. Initialiser la BDD de test si elle n'est pas à jour
5. Lancer les tests manuellement
6. Regarder les résultats
7. Répéter pour chaque PR

**Et si on oublie ?** La branche est mergée sans vérification. Le code cassé arrive sur `main`.

Avec la CI, au moment du push, GitHub exécute automatiquement tous ces contrôles sur un serveur distant. La PR affiche directement : ✅ ou ❌.

```
git push origin feature/40-ci
→ GitHub Actions démarre en fond
→ PHP CS Fixer : ✅
→ PHPStan : ✅
→ PHPUnit : ✅  43 tests passés
→ Badge vert affiché sur la PR
```

---

## Intégration Continue : le concept

**L'intégration continue** (CI = Continuous Integration) est la pratique d'automatiquement vérifier chaque modification du code. Le principe :

> Chaque fois qu'un développeur pousse du code, une machine automatique l'exécute, le teste, et rapporte si tout va bien.

Avant les outils CI, chaque développeur testait son code localement. Les problèmes n'apparaissaient qu'au moment d'assembler le travail de toute l'équipe — parfois après plusieurs semaines. L'intégration était douloureuse ("integration hell"). D'où le terme "intégration **continue**" : on intègre en permanence, au lieu d'une fois de temps en temps.

Même seul sur un projet, la CI apporte :
- Protection contre les oublis ("j'ai oublié de lancer les tests avant de merger")
- Exécution sur un environnement propre et neutre (pas de "ça marche sur ma machine")
- Historique visible des vérifications sur chaque commit

---

## GitHub Actions : vue d'ensemble

GitHub Actions est le système CI/CD intégré à GitHub. Quand du code est poussé sur le dépôt, GitHub peut automatiquement exécuter n'importe quel ensemble de commandes sur des machines virtuelles qu'il gère.

Les concepts clés :

```
Événement (push, PR)
    └── Workflow (.github/workflows/ci.yml)
            └── Job 1 : quality       ← tourne sur une machine Ubuntu
            │       └── Step 1 : checkout
            │       └── Step 2 : install PHP
            │       └── Step 3 : composer install
            │       └── Step 4 : php-cs-fixer
            │       └── ...
            └── Job 2 : functional    ← tourne sur une autre machine Ubuntu
                    └── Step 1 : checkout
                    └── Step 2 : install PHP
                    └── ...
```

**Workflow** : un fichier YAML dans `.github/workflows/`. Décrit quand et quoi exécuter.

**Job** : un groupe d'étapes qui s'exécutent sur une machine virtuelle. Chaque job reçoit une machine fraîche. Deux jobs tournent en parallèle par défaut.

**Step** : une seule étape dans un job (une commande ou une action pré-packagée).

**Runner** : la machine virtuelle où le job tourne. On utilise `ubuntu-latest` (Ubuntu Linux dernier version stable).

---

## Structure d'un fichier YAML GitHub Actions

```yaml
name: CI                    # nom affiché dans l'onglet Actions de GitHub

on:                         # "on" = "quand déclencher ce workflow"
  push:
    branches: [main]        # à chaque push sur la branche main
  pull_request:
    branches: [main]        # à chaque PR ciblant main

jobs:
  mon-job:                  # identifiant du job (snake_case, tirets, lettres)
    name: Affichage lisible # nom affiché dans GitHub (optionnel)
    runs-on: ubuntu-latest  # type de machine virtuelle

    steps:
      - name: Nom de l'étape    # affiché dans les logs GitHub
        uses: action/nom@v4     # utilise une action pré-packagée
        # OU
        run: commande bash      # exécute une commande shell directement
```

**YAML est sensible à l'indentation.** Une erreur d'indentation provoque une erreur de parsing avant même l'exécution. GitHub Actions valide la syntaxe au démarrage et affiche un message clair si elle est incorrecte.

---

## Les actions pré-packagées

Au lieu d'écrire des scripts bash complexes pour des tâches courantes, GitHub Actions propose des **actions** réutilisables. Elles s'utilisent avec `uses:`.

### `actions/checkout@v4`

```yaml
- name: Checkout
  uses: actions/checkout@v4
```

Récupère le code du dépôt sur la machine virtuelle. C'est toujours le premier step de tout job. Sans ça, la machine est vide — il n'y a pas de code à exécuter.

**Pourquoi `@v4` ?** C'est le numéro de version de l'action. Les actions sont elles-mêmes des dépôts GitHub. `@v4` épingle la version pour éviter les surprises si l'action évolue.

---

### `shivammathur/setup-php@v2`

```yaml
- name: Setup PHP 8.3
  uses: shivammathur/setup-php@v2
  with:
    php-version: '8.3'
    extensions: pdo_mysql
    coverage: none
```

Installe PHP sur la machine virtuelle avec la version et les extensions demandées. `ubuntu-latest` inclut PHP mais souvent pas la bonne version ni les bonnes extensions.

- `php-version: '8.3'` : installe PHP 8.3 (doit correspondre au `"php": ">=8.2"` de `composer.json`)
- `extensions: pdo_mysql` : active l'extension PDO MySQL (requise par Doctrine)
- `coverage: none` : désactive Xdebug (accélère l'exécution — on ne génère pas de rapport de couverture en CI)

---

### `actions/cache@v4`

```yaml
- name: Cache Composer
  uses: actions/cache@v4
  with:
    path: vendor
    key: ${{ runner.os }}-composer-${{ hashFiles('composer.lock') }}
    restore-keys: ${{ runner.os }}-composer-
```

Met en cache le dossier `vendor/` entre les runs pour éviter de re-télécharger toutes les dépendances à chaque fois.

**Sans cache :** chaque run télécharge des centaines de packages depuis Packagist → 2-3 minutes.
**Avec cache :** si `composer.lock` n'a pas changé, le dossier `vendor/` est restauré en quelques secondes.

**Comment ça marche :**
- `path: vendor` : quel dossier mettre en cache
- `key` : identifiant unique du cache. Si `composer.lock` change (nouvelle dépendance), la clé change et le cache est recrée
- `${{ ... }}` : syntaxe des expressions GitHub Actions (similaire à Twig `{{ }}`)
- `hashFiles('composer.lock')` : calcule un hash MD5 du fichier — il change à chaque modification du fichier
- `restore-keys` : clé de fallback si la clé exacte n'existe pas encore

---

## Décorticage de notre `ci.yml`

### Le déclencheur

```yaml
on:
  push:
    branches: [main]
  pull_request:
    branches: [main]
```

Le workflow se déclenche dans deux cas :
1. **Push sur `main`** : quand une PR est mergée. Confirme que `main` est toujours propre.
2. **Pull request ciblant `main`** : quand une PR est ouverte ou mise à jour. Vérifie avant le merge.

On aurait pu mettre `push: branches: ['**']` pour déclencher sur toutes les branches. On a choisi de ne surveiller que `main` pour éviter des runs inutiles sur les branches feature en cours.

---

### Job 1 : `quality`

Ce job vérifie la qualité du code statique et les tests unitaires. **Il ne nécessite pas de base de données.**

```yaml
quality:
  name: Qualité du code
  runs-on: ubuntu-latest
```

`runs-on: ubuntu-latest` = la machine virtuelle est un Ubuntu Linux (dernier LTS). C'est le runner le plus courant — Linux est moins cher que macOS sur GitHub Actions.

#### Étape : PHP CS Fixer

```yaml
- name: PHP CS Fixer
  run: vendor/bin/php-cs-fixer check --diff --no-interaction
```

`check` (et non `fix`) : mode vérification uniquement. Si un fichier n'est pas conforme au style `@Symfony`, la commande retourne un code d'erreur non-zéro → GitHub Actions marque l'étape comme échouée.

`--diff` : affiche les différences exactes (quelle ligne est mal formatée, quelle correction serait appliquée).

`--no-interaction` : désactive les questions interactives (évite que la commande attende une saisie qui ne viendra jamais en CI).

#### Étape : warmup du cache Symfony

```yaml
- name: Warmup cache Symfony (requis par PHPStan pour lire le container)
  run: php bin/console cache:warmup --env=dev
  env:
    APP_SECRET: ci_secret_placeholder
    DATABASE_URL: "mysql://app:app@127.0.0.1:3306/app?serverVersion=8.0.32&charset=utf8mb4"
```

**Pourquoi cette étape ?** Notre `phpstan.dist.neon` contient :
```neon
symfony:
    containerXmlPath: var/cache/dev/App_KernelDevDebugContainer.xml
```

PHPStan-Symfony utilise ce fichier XML pour comprendre les types des services Symfony (quel service est injecté par quel type, quelle route existe, etc.). Ce fichier est généré par `cache:warmup`.

**Pourquoi `env:`** ? La commande `php bin/console` démarre Symfony. Symfony a besoin de deux variables :
- `APP_SECRET` : clé de chiffrement des cookies. La valeur est un placeholder — on ne chiffre rien en CI.
- `DATABASE_URL` : connexion MySQL. MySQL n'est pas démarré dans ce job, mais Symfony ne se connecte pas à la BDD au démarrage — la connexion est "lazy" (paresseuse). La valeur peut pointer sur une BDD inexistante.

**Comment `env:` fonctionne-t-il ?** Ces variables sont injectées dans l'environnement du processus pour cette étape uniquement. Le fichier `.env` dit : _"Real environment variables win over .env files"_ — les variables système ont priorité sur les fichiers `.env`. Symfony les lit en premier et n'essaie pas de les écraser avec le contenu de `.env`.

#### Étape : PHPStan

```yaml
- name: PHPStan
  run: vendor/bin/phpstan analyse --no-progress
  env:
    APP_SECRET: ci_secret_placeholder
    DATABASE_URL: "mysql://app:app@127.0.0.1:3306/app?serverVersion=8.0.32&charset=utf8mb4"
```

`--no-progress` : désactive la barre de progression (inutile dans les logs CI, pollue l'affichage).

Les variables `env:` sont à nouveau nécessaires : PHPStan exécute `tests/object-manager.php` pour obtenir l'EntityManager Doctrine (et analyser correctement les types des entités). Ce script démarre le kernel Symfony — il faut donc `APP_SECRET` et `DATABASE_URL`.

**Pourquoi pas de MySQL ici ?** PHPStan analyse statiquement le code PHP. Il n'exécute pas l'application. Quand il démarre le kernel pour lire la configuration de Doctrine, PHP configure les metadata ORM en mémoire sans jamais ouvrir de connexion.

#### Étape : PHPUnit Unit

```yaml
- name: PHPUnit – tests unitaires
  run: vendor/bin/phpunit --testsuite Unit
```

Pas de `env:` ici. Pourquoi ? Les tests unitaires étendent `TestCase` (pas `WebTestCase`) — ils n'instancient pas Symfony. PHPUnit charge quand même `bootstrap.php` qui appelle `bootEnv()`, mais `bootEnv()` lira `APP_SECRET='$ecretf0rt3st'` depuis `.env.test` (déjà commité). Aucune interaction avec la BDD.

---

### Job 2 : `functional`

Ce job exécute les tests fonctionnels. Il nécessite une vraie base de données MySQL.

#### La variable d'environnement au niveau du job

```yaml
functional:
  name: Tests fonctionnels
  runs-on: ubuntu-latest

  env:
    DATABASE_URL: "mysql://root:root@127.0.0.1:3306/lereperedesprotocoles_v2?serverVersion=8.0.32&charset=utf8mb4"
```

`env:` au niveau du job s'applique à **toutes les étapes** de ce job. Toutes les commandes `php bin/console ...` et `vendor/bin/phpunit` verront cette variable.

**Pourquoi `lereperedesprotocoles_v2` et non `lereperedesprotocoles_v2_test` ?** Dans `config/packages/doctrine.yaml` :
```yaml
when@test:
    doctrine:
        dbal:
            dbname_suffix: '_test'
```
Doctrine ajoute automatiquement `_test` au nom de la BDD quand `APP_ENV=test`. La `DATABASE_URL` pointe sur la BDD de base → Doctrine construit `lereperedesprotocoles_v2_test` tout seul.

**Pourquoi `root:root` ?** Sur la machine de développement, on utilise les credentials Laragon (`root` sans mot de passe). En CI, on démarre un conteneur MySQL et on choisit les credentials librement. On a choisi `root:root` pour la simplicité. En production, les vrais credentials seraient dans les secrets GitHub.

---

### Les services Docker

```yaml
services:
  mysql:
    image: mysql:8.0
    env:
      MYSQL_ROOT_PASSWORD: root
    ports:
      - 3306:3306
    options: >-
      --health-cmd="mysqladmin ping"
      --health-interval=10s
      --health-timeout=5s
      --health-retries=3
```

GitHub Actions peut démarrer des **services** : des conteneurs Docker qui tournent en parallèle du job. Ici on démarre MySQL 8.0.

**`image: mysql:8.0`** : l'image Docker officielle MySQL version 8.0. GitHub la télécharge automatiquement depuis Docker Hub.

**`env:`** : variables d'environnement du conteneur MySQL lui-même (pas du job).
- `MYSQL_ROOT_PASSWORD: root` : le mot de passe root que MySQL configurera au démarrage. Doit correspondre à la `DATABASE_URL` du job.

**`ports: - 3306:3306`** : expose le port 3306 du conteneur sur le port 3306 de la machine virtuelle. Syntaxe : `port_machine:port_conteneur`. Nos commandes Symfony se connectent à `127.0.0.1:3306` → elles atteignent le conteneur MySQL.

**`options:`** : arguments supplémentaires passés à `docker run`. Ici, le **health check** :
- `--health-cmd="mysqladmin ping"` : commande exécutée périodiquement pour savoir si MySQL est prêt. `mysqladmin ping` répond `mysqld is alive` quand MySQL accepte les connexions.
- `--health-interval=10s` : vérifie toutes les 10 secondes.
- `--health-timeout=5s` : la commande doit répondre en moins de 5 secondes.
- `--health-retries=3` : au bout de 3 échecs consécutifs, le service est déclaré "unhealthy".

**Pourquoi le health check ?** MySQL prend quelques secondes à démarrer. Sans health check, la première étape du job (`doctrine:database:create`) s'exécuterait immédiatement et échouerait parce que MySQL n'est pas encore prêt. Avec le health check, GitHub Actions attend que MySQL soit "healthy" avant d'exécuter les steps.

**`>-`** : notation YAML pour écrire une longue valeur sur plusieurs lignes. `>` replie les sauts de ligne en espaces, `-` retire le saut de ligne final. Le résultat est une seule chaîne : `--health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3`.

---

### Création de la BDD de test en CI

```yaml
- name: Créer la base de données de test
  run: php bin/console doctrine:database:create --env=test --if-not-exists

- name: Créer le schéma
  run: php bin/console doctrine:schema:create --env=test

- name: Charger les fixtures
  run: php bin/console doctrine:fixtures:load --env=test --no-interaction
```

Ces trois commandes font la même chose qu'en développement local. En CI, elles tournent sur une BDD vierge à chaque run — l'état est toujours prévisible. C'est l'avantage d'une machine fraîche : pas d'historique, pas de données parasites.

**Pourquoi `doctrine:schema:create` et non `doctrine:migrations:migrate` ?** Les migrations construisent le schéma de façon incrémentale (migration 001, 002, 003...). `schema:create` le construit d'un coup à partir des entités actuelles. En CI, on repart de zéro à chaque run, donc `schema:create` est plus rapide et plus simple.

---

### `--no-scripts` dans composer install

```yaml
- name: Installer les dépendances Composer
  run: composer install --prefer-dist --no-interaction --no-scripts
```

Les scripts `post-install-cmd` dans `composer.json` exécutent :
1. `cache:clear` — vide le cache Symfony (nécessite des variables d'env)
2. `assets:install` — installe les assets des bundles dans `public/`
3. `importmap:install` — télécharge les packages JavaScript depuis un CDN

`--no-scripts` court-circuite ces automatismes pour éviter que `cache:clear` échoue (il tourne avec l'env `dev` par défaut, sans les variables d'env configurées). Dans le job fonctionnel, `importmap:install` est ensuite relancé **explicitement**, car les templates Twig qui utilisent `{{ importmap('app') }}` ont besoin que les fichiers vendor JS (`@hotwired/stimulus`, `@hotwired/turbo`) soient présents — sans eux, AssetMapper lève une exception PHP et les pages retournent HTTP 500.

```yaml
- name: Installer les assets JavaScript (requis par les templates Twig)
  run: php bin/console importmap:install
```

Le job `quality` n'en a pas besoin : PHPUnit Unit et PHPStan n'instancient pas Symfony et ne rendent aucune page.

---

## Variables d'environnement : comment la priorité fonctionne

Symfony charge les variables dans cet ordre, du plus faible au plus fort :

```
.env                     ← valeurs par défaut (commité)
.env.test                ← surcharges pour APP_ENV=test (commité)
.env.test.local          ← surcharges locales (gitignored, absent en CI)
variables système        ← définies par le système d'exploitation / CI  ← PRIORITÉ MAXIMALE
```

Quand GitHub Actions définit une variable dans `env:`, elle entre dans l'environnement du processus. PHP la voit dans `$_ENV` et `getenv()`. Symfony Dotenv respecte les variables déjà présentes et ne les écrase pas.

C'est pourquoi notre `DATABASE_URL` définie dans `env:` du job fonctionnel prend le pas sur le `DATABASE_URL` de `.env.test` — même si `.env.test` est chargé par `bootstrap.php`.

Le fichier `.env` lui-même le documente :
```
# Real environment variables win over .env files.
```

---

## `tests/object-manager.php` : le fichier PHPStan + Doctrine

```php
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (!isset($_SERVER['APP_ENV'])) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
```

Ce fichier est référencé dans `phpstan.dist.neon` :
```neon
doctrine:
    objectManagerLoader: tests/object-manager.php
```

PHPStan-Doctrine exécute ce script pour obtenir l'EntityManager de Doctrine. Il lit la configuration des entités (quelles colonnes sont nullable, quelles relations existent) pour analyser correctement les types dans le code PHP.

**`if (!isset($_SERVER['APP_ENV']))`** : si `APP_ENV` n'est pas déjà défini (c'est le cas quand PHPStan appelle ce script directement), on charge les fichiers `.env`. Si `APP_ENV` est déjà défini (c'est le cas quand PHPUnit l'a déjà chargé), on ne recharge pas.

**Le kernel ne se connecte pas à la BDD.** `$kernel->boot()` initialise le conteneur de services en mémoire. `getManager()` retourne l'EntityManager configuré, mais Doctrine n'ouvre une connexion MySQL que sur la première requête réelle. PHPStan lit uniquement la configuration — il n'exécute aucune requête.

---

## Lire les résultats dans GitHub

### Onglet Actions

Aller sur le dépôt GitHub → onglet **Actions**. Chaque push ou PR déclenchant un run apparaît dans la liste.

```
✅ feat: ajout CI GitHub Actions   main  2m 34s
❌ fix: correction formulaire       main  1m 12s
✅ feat: tests fonctionnels         main  3m 01s
```

Cliquer sur un run pour voir le détail. Chaque job apparaît :
```
✅ Qualité du code      1m 45s
✅ Tests fonctionnels   2m 49s
```

Cliquer sur un job pour voir le détail de chaque step :
```
✅ Checkout                    2s
✅ Setup PHP 8.3               12s
✅ Cache Composer              8s   (cache hit)
✅ Installer les dépendances   22s
✅ PHP CS Fixer                3s
✅ Warmup cache Symfony        4s
✅ PHPStan                     18s
✅ PHPUnit – tests unitaires   6s
```

---

### Ce qui se passe en cas d'échec

**PHP CS Fixer détecte un fichier mal formaté :**
```
❌ PHP CS Fixer   2s

--- Original
+++ Expected
@@ -10,7 +10,7 @@
-    public function testQuelqueChose(): void{
+    public function testQuelqueChose(): void
+    {
```

La diff indique exactement quelle ligne ne respecte pas le style. Corriger en lançant `vendor/bin/php-cs-fixer fix` en local, puis pousser à nouveau.

**PHPStan trouve une erreur de type :**
```
❌ PHPStan   18s

 ------ -----------------------------------------------
  Line   src/Controller/HomeController.php
 ------ -----------------------------------------------
  42     Cannot call method getId() on null.
 ------ -----------------------------------------------
```

Corriger l'erreur dans le code source, pousser.

**Un test PHPUnit échoue :**
```
❌ PHPUnit – tests unitaires   6s

FAILURES!
Tests: 25, Assertions: 56, Failures: 1.

1) App\Tests\Unit\Entity\UserTest::testDefaultValues
Failed asserting that true is false.
```

---

### Le badge CI dans le README

```markdown
[![CI](https://github.com/MaximeSauvageREPERE/lereperedesprotocoles-v2/actions/workflows/ci.yml/badge.svg)](https://github.com/MaximeSauvageREPERE/lereperedesprotocoles-v2/actions/workflows/ci.yml)
```

Ce badge est une image dynamique générée par GitHub. Elle affiche l'état du dernier run sur `main` :

- `passing` fond vert : le dernier run sur main est passé
- `failing` fond rouge : quelque chose est cassé sur main
- `no status` gris : aucun run n'a encore eu lieu

En cliquant sur le badge, on arrive directement sur la page des runs GitHub Actions.

---

## Vue d'ensemble : que se passe-t-il à chaque push

```
git push origin main
        │
        ▼
GitHub reçoit le push
        │
        ▼
GitHub Actions lit .github/workflows/ci.yml
        │
        ├──────────────────────────────────────────────┐
        ▼                                              ▼
  Job : quality                              Job : functional
  Machine Ubuntu fraîche                     Machine Ubuntu fraîche
        │                                              │
        │  1. checkout (récupère le code)              │  1. Démarrage MySQL 8.0 (conteneur)
        │  2. installe PHP 8.3                         │  2. Attente health check MySQL
        │  3. restaure le cache vendor/               │  3. checkout
        │  4. composer install                         │  4. installe PHP 8.3
        │  5. php-cs-fixer check                       │  5. restaure le cache vendor/
        │  6. cache:warmup (pour PHPStan)              │  6. composer install
        │  7. phpstan analyse                          │  7. doctrine:database:create
        │  8. phpunit --testsuite Unit                 │  8. doctrine:schema:create
        │                                              │  9. doctrine:fixtures:load
        │                                              │ 10. phpunit --testsuite Functional
        ▼                                              ▼
     ✅ ou ❌                                       ✅ ou ❌
        │                                              │
        └──────────────────┬───────────────────────────┘
                           ▼
              Résultat affiché sur la PR / le commit
```

Les deux jobs tournent **en parallèle** sur deux machines séparées. Chaque machine commence avec un Ubuntu vierge — aucun état n'est partagé entre les jobs (sauf le cache Composer, qui est un mécanisme explicite).

---

## Questions fréquentes

**Pourquoi deux jobs séparés et pas un seul ?**

Parce qu'ils ont des besoins différents. Le job `quality` n'a pas besoin de MySQL. Si on mettait tout dans un seul job, on démarrerait MySQL même pour les vérifications de style et les tests unitaires — lenteur inutile. La séparation permet aussi de voir d'un coup d'œil lequel des deux a échoué.

**Pourquoi un faux DATABASE_URL dans le job `quality` ?**

Symfony a besoin d'une valeur pour DATABASE_URL pour démarrer (cache:warmup, object-manager.php). La valeur n'a pas besoin de pointer sur une vraie base : Doctrine ne se connecte pas au démarrage. Si on ne définissait pas DATABASE_URL, Symfony utiliserait la valeur de `.env.test` (`mysql://app:!ChangeMe!@...`) qui est aussi un placeholder — le comportement serait identique. On le définit explicitement pour rendre l'intention claire.

**Pourquoi MySQL 8.0 et non 8.4 (comme en production) ?**

Le projet utilise MySQL 8.4 en local (Laragon), mais `mysql:8.4` n'est pas encore disponible comme image Docker officielle stable au moment de l'écriture. MySQL 8.0 est une version LTS largement compatible. Le paramètre `?serverVersion=8.0.32` dans la DATABASE_URL informe Doctrine de la version exacte utilisée (pour choisir la bonne syntaxe SQL).

**Que faire si la CI échoue sur une PR mais que ça marche en local ?**

Vérifier si la différence vient de :
- Une dépendance installée localement mais pas dans `composer.json` (rare)
- Un fichier non commité (ex : `.env.local` manquant en CI → variables manquantes)
- Une différence de version PHP (8.3.x en local vs 8.3.y en CI — en pratique négligeable)
- Un test qui dépend de l'état de la machine (heure système, fichiers temporaires)

La bonne approche : regarder les logs GitHub Actions, identifier l'étape qui a échoué, lire le message d'erreur exact.

**Le cache Composer est-il partagé entre les jobs ?**

Oui. Les deux jobs utilisent la même clé de cache (basée sur `runner.os` et `composer.lock`). GitHub Actions stocke le cache côté serveur et les deux jobs peuvent le restaurer indépendamment.

**Est-ce que la CI protège contre tous les bugs ?**

Non. La CI vérifie ce qu'on lui demande de vérifier : le style, les types statiques, et les tests qu'on a écrits. Elle ne détecte pas les bugs dans les fonctionnalités non testées, les erreurs de logique métier subtiles, ou les problèmes d'interface visuelle. C'est pour ça que les tests manuels et les revues de code restent nécessaires.
