# Comprendre les tests PHPUnit

## C'est quoi un test PHPUnit ?

Un test PHPUnit est un morceau de code PHP qui **vérifie automatiquement** qu'un autre morceau de code fonctionne correctement.

Au lieu de tester manuellement dans le navigateur ("je me connecte, je clique, ça marche"), on écrit du code qui fait les vérifications à notre place, et qu'on peut relancer en une commande à chaque fois qu'on modifie le projet.

```
vendor/bin/phpunit   →   25 tests, 56 assertions   →   tout est vert = rien de cassé
```

---

## Les deux niveaux de tests dans ce projet

| Type | Ticket | Ce qu'il teste | Besoin de la BDD |
|---|---|---|---|
| **Tests unitaires** | #13 (fait) | Une classe PHP isolée | ❌ Non |
| **Tests fonctionnels** | #35 (à venir) | Une page HTTP entière | ✅ Oui |

### Tests unitaires (#13)
On instancie une classe PHP directement et on vérifie son comportement.  
Pas de serveur, pas de base de données. Très rapide.

### Tests fonctionnels (#35)
On simule un navigateur : on fait une requête HTTP (`GET /login`), on regarde la réponse (code 200 ? formulaire présent ? redirection ?).  
Nécessite une base de données de test.

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
├── object-manager.php         Accès à Doctrine (utilisé par les tests fonctionnels)
└── Unit/
    ├── Entity/
    │   ├── UserTest.php
    │   ├── DemandeInscriptionTest.php
    │   └── ProtocoleTest.php
    └── Security/
        └── UserCheckerTest.php
```

La convention `Unit/` vs `Functional/` (à venir) sépare les deux types de tests.

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
