# Comprendre les tests PHPUnit

## C'est quoi un test PHPUnit ?

Un test PHPUnit est un morceau de code PHP qui **vérifie automatiquement** qu'un autre morceau de code fonctionne correctement.

Au lieu de tester manuellement dans le navigateur ("je me connecte, je clique, ça marche"), on écrit du code qui fait les vérifications à notre place, et qu'on peut relancer en une commande à chaque fois qu'on modifie le projet.

```
vendor/bin/phpunit   →   43 tests, 72 assertions   →   tout est vert = rien de cassé
```

---

## Les deux niveaux de tests dans ce projet

| Type | Ticket | Ce qu'il teste | Besoin de la BDD |
|---|---|---|---|
| **Tests unitaires** | #13 | Une classe PHP isolée | ❌ Non |
| **Tests fonctionnels** | #35 | Une page HTTP entière | ✅ Oui |

```powershell
vendor/bin/phpunit --testsuite Unit        # tests unitaires uniquement (pas besoin de BDD)
vendor/bin/phpunit --testsuite Functional  # tests fonctionnels uniquement
vendor/bin/phpunit                         # tous les tests
```

### Tests unitaires (#13)
On instancie une classe PHP directement et on vérifie son comportement.  
Pas de serveur, pas de base de données. Très rapide.

### Tests fonctionnels (#35)
On simule un navigateur : on fait une requête HTTP (`GET /login`), on regarde la réponse (code 200 ? formulaire présent ? redirection ?).  
Nécessite une base de données de test séparée (`app_test`).

---

## Anatomie d'un test

Voici `tests/Unit/Entity/UserTest.php` :

```php
class UserTest extends TestCase          // hérite de la classe de base PHPUnit
{
    public function testDefaultValues(): void   // nom commence par "test"
    {
        // 1. ARRANGE — créer l'objet à tester
        $user = new User();

        // 2. ACT — faire quelque chose (ici rien, on teste l'état initial)

        // 3. ASSERT — vérifier que le résultat est correct
        $this->assertFalse($user->isVerified());   // un utilisateur n'est pas vérifié par défaut
        $this->assertSame('', $user->getEmail());  // email vide par défaut
    }
}
```

Le motif **Arrange / Act / Assert** est universel dans les tests :
- **Arrange** : préparer l'objet et les données
- **Act** : appeler le code à tester
- **Assert** : vérifier que le résultat est celui attendu

Si une assertion échoue, PHPUnit affiche exactement laquelle a échoué et pourquoi.

---

## Ce qu'on a testé dans le ticket #13

### `UserTest` — 7 tests

| Test | Ce qu'il vérifie |
|---|---|
| `testDefaultValues` | Un `new User()` a email='', password='', isVerified=false, etc. |
| `testGetRolesAlwaysIncludesRoleUser` | `getRoles()` retourne toujours `ROLE_USER`, même sans rôle assigné |
| `testGetRolesWithAdminRole` | Si on fait `setRoles(['ROLE_ADMIN'])`, `getRoles()` retourne les deux rôles |
| `testGetRolesNoDuplicates` | Si `ROLE_USER` est déjà dans le tableau, pas de doublon |
| `testGetUserIdentifierReturnsEmail` | `getUserIdentifier()` retourne l'email (requis par Symfony Security) |
| `testSettersReturnStatic` | Chaque setter retourne `$this` (permet le chaînage `->setEmail()->setNom()`) |
| `testEraseCredentialsIsNoop` | `eraseCredentials()` ne supprime pas le password (il est déjà haché) |

### `DemandeInscriptionTest` — 7 tests

| Test | Ce qu'il vérifie |
|---|---|
| `testDefaultValues` | Statut initial = `en_attente`, emailVerifie = false, etc. |
| `testStatutConstants` | Les constantes ont les bonnes valeurs (`'en_attente'`, `'approuvee'`, `'refusee'`) |
| `testIsTokenValideReturnsTrueWhenFutureExpiry` | Un token qui expire dans 1h est valide |
| `testIsTokenValideReturnsFalseWhenExpired` | Un token expiré depuis 1 seconde n'est plus valide |
| `testIsTokenValideReturnsFalseWhenNoExpiryDate` | Un token sans date d'expiration n'est pas valide |
| `testStatutCanBeChangedToApprouvee` | On peut changer le statut vers `approuvee` |
| `testStatutCanBeChangedToRefusee` | On peut changer le statut vers `refusee` |

Le test le plus intéressant ici : `isTokenValide()`. C'est une vraie règle métier (un token de vérification email expire), et maintenant on est sûr qu'elle fonctionne.

### `ProtocoleTest` — 7 tests

| Test | Ce qu'il vérifie |
|---|---|
| `testDefaultValues` | Titre='', slug='', description=null, theme=null, etc. |
| `testSetPdfFileRefreshesUpdatedAt` | Attacher un PDF met à jour `updatedAt` (requis par VichUploader) |
| `testSetPdfFileNullDoesNotRefreshUpdatedAt` | Retirer le PDF (`null`) ne change pas `updatedAt` |
| `testSetImageFileRefreshesUpdatedAt` | Même chose pour l'image |
| `testSetImageFileNullDoesNotRefreshUpdatedAt` | Même chose pour l'image (avec null) |
| `testOnPreUpdateRefreshesUpdatedAt` | Le hook Doctrine `#[PreUpdate]` met bien à jour `updatedAt` |
| `testToStringReturnsTitre` | `(string) $protocole` retourne le titre |

### `UserCheckerTest` — 4 tests

| Test | Ce qu'il vérifie |
|---|---|
| `testCheckPreAuthSkipsNonAppUser` | Un objet qui n'est pas `App\Entity\User` est ignoré sans erreur |
| `testCheckPreAuthThrowsWhenUserNotVerified` | Un utilisateur non vérifié déclenche une exception au login |
| `testCheckPreAuthAllowsVerifiedUser` | Un utilisateur vérifié peut se connecter |
| `testCheckPostAuthDoesNothing` | `checkPostAuth` ne fait rien (méthode vide requise par l'interface) |

---

## Comment lire la sortie de PHPUnit

```
.........................                25 / 25 (100%)
```

Chaque caractère = un test :
- `.` = test passé
- `F` = test échoué (assertion fausse)
- `E` = erreur PHP pendant le test
- `S` = test ignoré (skipped)
- `N` = notice PHPUnit (avertissement non bloquant)

---

## Structure des fichiers

```
tests/
├── bootstrap.php              Initialisation (charge .env.test, démarre Symfony)
├── object-manager.php         Accès à Doctrine (utilisé par PHPStan)
├── Unit/
│   ├── Entity/
│   │   ├── UserTest.php
│   │   ├── DemandeInscriptionTest.php
│   │   └── ProtocoleTest.php
│   └── Security/
│       └── UserCheckerTest.php
└── Functional/
    ├── AccessControlTest.php   Redirections anonymes (pas de BDD)
    ├── SecurityTest.php        Formulaire de login (BDD requise)
    └── RoleAccessTest.php      Contrôle d'accès par rôle (BDD requise)
```

La suite `Unit` fonctionne sans base de données. La suite `Functional` nécessite une BDD de test initialisée.

---

## Les problèmes rencontrés et leurs solutions

### 1. `phpunit.dist.xml` au lieu de `phpunit.xml.dist`

PHPUnit cherche automatiquement un fichier de configuration dans cet ordre : `phpunit.xml`, puis `phpunit.xml.dist`. Le fichier était mal nommé (`phpunit.dist.xml`), PHPUnit ne le trouvait pas en auto-détection.  
**Correction :** créé `phpunit.xml.dist` avec le même contenu, supprimé l'ancien.

### 2. `createMock(File::class)` sur une classe concrète

Dans les tests de `Protocole`, j'avais besoin d'un objet `File` pour tester que `setPdfFile()` met à jour `updatedAt`. J'avais utilisé `$this->createMock(File::class)`.

**Problème :** `File` hérite de `SplFileInfo` (classe PHP interne). PHPUnit 12 génère un notice quand on crée un mock d'une classe concrète (pas d'une interface).

**Correction :** `new File('dummy.pdf', false)` — le deuxième argument `false` désactive la vérification d'existence du fichier.

### 3. `createMock()` sans expectations

Dans `UserCheckerTest`, j'utilisais `createMock(UserInterface::class)` sans configurer d'attentes (on voulait juste un objet qui implémente l'interface).

**Problème :** PHPUnit 12 distingue `createMock()` (pour les mocks avec expectations) de `createStub()` (pour les stubs sans expectations). Utiliser `createMock()` sans expectations génère un notice.

**Correction :** remplacé par `$this->createStub(UserInterface::class)`.

---

## Différence mock / stub (nouveau dans PHPUnit 12)

| | `createStub()` | `createMock()` |
|---|---|---|
| **Usage** | Remplaçant passif (on s'en fout de comment il est appelé) | Remplaçant actif (on vérifie qu'il est appelé d'une certaine façon) |
| **Expectations** | Aucune | On configure `expects()`, `with()`, `willReturn()` |
| **Exemple** | Fournir un `UserInterface` quelconque | Vérifier qu'un service `sendEmail()` est bien appelé une fois |

Dans nos tests unitaires actuels, on n'a besoin que de stubs.

---

## Tests fonctionnels (#35)

### Ce qu'on a testé

#### `AccessControlTest` — 7 tests (aucune BDD)

| Test | Ce qu'il vérifie |
|---|---|
| `testHomeIsPublic` | `GET /` retourne 200 pour un visiteur anonyme |
| `testLoginPageIsPublic` | `GET /login` retourne 200 |
| `testInscriptionPageIsPublic` | `GET /inscription` retourne 200 |
| `testParcourirRedirectsToLoginWhenAnonymous` | `GET /parcourir` redirige vers `/login` |
| `testProfilRedirectsToLoginWhenAnonymous` | `GET /profil` redirige vers `/login` |
| `testModerateurRedirectsToLoginWhenAnonymous` | `GET /moderateur/domaines` redirige vers `/login` |
| `testAdminRedirectsToLoginWhenAnonymous` | `GET /admin/utilisateurs` redirige vers `/login` |

#### `SecurityTest` — 4 tests (BDD requise)

| Test | Ce qu'il vérifie |
|---|---|
| `testLoginFormContainsRequiredFields` | La page login a les champs `_username`, `_password`, `_csrf_token` |
| `testLoginWithValidCredentials` | Login correct → redirection vers la page d'accueil |
| `testLoginWithWrongPasswordStaysOnLoginPage` | Login incorrect → retour sur `/login` avec message d'erreur |
| `testAlreadyLoggedInUserIsRedirectedFromLogin` | Utilisateur déjà connecté → `/login` redirige vers l'accueil |

#### `RoleAccessTest` — 7 tests (BDD requise)

| Test | Ce qu'il vérifie |
|---|---|
| `testUserCanAccessParcourir` | ROLE_USER peut accéder à `/parcourir` |
| `testUserCannotAccessModerateurPages` | ROLE_USER → 403 sur `/moderateur/domaines` |
| `testUserCannotAccessAdminPages` | ROLE_USER → 403 sur `/admin/utilisateurs` |
| `testModerateurCanAccessModerateurPages` | ROLE_MODERATEUR → 200 sur `/moderateur/domaines` |
| `testModerateurCannotAccessAdminPages` | ROLE_MODERATEUR → 403 sur `/admin/utilisateurs` |
| `testAdminCanAccessAdminPages` | ROLE_ADMIN → 200 sur `/admin/utilisateurs` |
| `testAdminCanAccessModerateurPagesViaHierarchy` | ROLE_ADMIN → 200 sur `/moderateur/domaines` (hiérarchie des rôles) |

### Outils utilisés

**`WebTestCase`** (Symfony) — classe de base pour les tests fonctionnels. Démarre un vrai noyau Symfony, permet de faire des requêtes HTTP simulées.

**`$client->loginUser($user)`** — connecte un utilisateur directement dans la session, sans passer par le formulaire. Évite d'avoir à soumettre le formulaire à chaque test de contrôle d'accès.

**`assertResponseIsSuccessful()`** — vérifie que la réponse est 2xx.

**`assertResponseStatusCodeSame(403)`** — vérifie un code HTTP précis.

**`assertResponseRedirects('/login')`** — vérifie que la réponse est une redirection vers cette URL.

**`assertRouteSame('app_home')`** — après `followRedirect()`, vérifie la route courante.

**`assertSelectorExists('.bg-red-50')`** — vérifie la présence d'un élément CSS dans la page HTML.

### Mise en place de la BDD de test

Les tests fonctionnels ont besoin d'une BDD séparée. À faire une seule fois :

```powershell
# 1. Créer le fichier de credentials locaux (gitignored)
#    Copier .env.test.local.dist en .env.test.local et y mettre les vraies credentials

# 2. Créer la BDD de test
php bin/console doctrine:database:create --env=test --if-not-exists

# 3. Créer le schéma
php bin/console doctrine:schema:create --env=test

# 4. Charger les fixtures (crée admin@test.fr, modo@test.fr, user@test.fr)
php bin/console doctrine:fixtures:load --env=test --no-interaction

# 5. Lancer les tests
vendor/bin/phpunit --testsuite Functional
```

À re-exécuter depuis l'étape 4 si les fixtures changent (modifications de schéma → depuis l'étape 3).
