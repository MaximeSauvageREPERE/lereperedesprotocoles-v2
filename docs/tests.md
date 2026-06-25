# Comprendre les tests PHPUnit

## Pourquoi écrire des tests ?

Sans tests, pour vérifier que le code fonctionne encore après une modification, il faut :

1. Démarrer le serveur
2. Ouvrir le navigateur
3. Se connecter
4. Naviguer vers la fonctionnalité
5. Vérifier manuellement
6. Répéter pour chaque fonctionnalité potentiellement impactée

Avec des tests, une seule commande fait tout ça en quelques secondes :

```
vendor/bin/phpunit   →   43 tests, 72 assertions   →   tout est vert = rien de cassé
```

Si quelque chose casse suite à une modification, PHPUnit indique exactement quel test a échoué, quelle valeur était attendue, et quelle valeur a été obtenue.

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

---

## Tests unitaires (#13)

---

### Qu'est-ce qu'un test unitaire ?

Un test unitaire instancie une classe PHP directement — sans serveur, sans base de données, sans HTTP — et vérifie que ses méthodes retournent les bonnes valeurs.

```php
$user = new User();
echo $user->isVerified();   // false — on peut vérifier ça manuellement dans la console

// Avec PHPUnit, on écrit ça une fois et on le vérifie automatiquement à chaque fois :
$this->assertFalse($user->isVerified());
```

L'idée clé : **on teste une unité de code en isolation**. Si `UserTest` passe, on sait que `User` fonctionne correctement — indépendamment du reste de l'application.

---

### Anatomie d'un test

```php
use PHPUnit\Framework\TestCase;   // classe de base fournie par PHPUnit

class UserTest extends TestCase   // notre classe de test hérite de TestCase
{
    //  ↑ convention : nom de la classe testée + "Test"

    public function testDefaultValues(): void
    //             ↑ doit commencer par "test" — c'est comme ça que PHPUnit détecte les tests
    {
        // ── ARRANGE ─────────────────────────────────────────────────────────
        // Préparer l'objet à tester. C'est toujours le point de départ.
        $user = new User();

        // ── ACT ─────────────────────────────────────────────────────────────
        // Appeler le code à tester. Parfois cette étape est absente
        // (quand on teste l'état initial d'un objet, comme ici).

        // ── ASSERT ──────────────────────────────────────────────────────────
        // Vérifier que le résultat est correct. Si l'assertion échoue,
        // PHPUnit arrête le test et affiche exactement ce qui ne va pas.
        $this->assertFalse($user->isVerified());
        $this->assertSame('', $user->getEmail());
        $this->assertNull($user->getId());
    }
}
```

Le motif **Arrange / Act / Assert** est universel dans les tests. Le garder en tête aide à structurer chaque test de façon lisible.

---

### Ce que font les méthodes `assert*`

PHPUnit fournit une cinquantaine de méthodes `assert*`. En voici celles utilisées dans ce projet :

#### `assertNull($valeur)`
Vérifie que `$valeur === null`.
```php
$this->assertNull($user->getId());
// Un User non persisté en BDD n'a pas d'id — il est null.
```

#### `assertSame($attendu, $obtenu)`
Vérifie que les deux valeurs sont **strictement identiques** (même valeur ET même type). C'est l'équivalent de `===`.
```php
$this->assertSame('', $user->getEmail());
// assertSame('', 0) échouerait car '' !== 0
// assertSame('', null) échouerait car '' !== null
```

#### `assertEquals($attendu, $obtenu)`
Vérifie l'égalité **avec conversion de type** (équivalent de `==`). Utilisé quand on compare deux objets `DateTimeImmutable` avec les mêmes valeurs.
```php
$this->assertEquals($past, $protocole->getUpdatedAt());
// Deux objets DateTimeImmutable distincts mais représentant le même instant.
```

#### `assertFalse($valeur)` / `assertTrue($valeur)`
Vérifie que la valeur est exactement `false` ou exactement `true`.
```php
$this->assertFalse($user->isVerified());
$this->assertTrue($demande->isTokenValide());
```

#### `assertInstanceOf($classe, $objet)`
Vérifie que `$objet` est bien une instance de `$classe`.
```php
$this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
// Vérifie que createdAt est bien un DateTimeImmutable, pas null, pas une string.
```

#### `assertContains($valeur, $tableau)`
Vérifie qu'un tableau contient un élément.
```php
$this->assertContains('ROLE_USER', $user->getRoles());
// getRoles() doit toujours inclure ROLE_USER.
```

#### `assertCount($nombre, $tableau)`
Vérifie qu'un tableau a exactement `$nombre` éléments.
```php
$this->assertCount(1, $user->getRoles());
// Vérifie l'absence de doublons : ['ROLE_USER', 'ROLE_USER'] échouerait.
```

#### `assertGreaterThan($valeur, $obtenu)`
Vérifie que `$obtenu > $valeur`.
```php
$this->assertGreaterThan($past, $protocole->getUpdatedAt());
// L'updatedAt doit être postérieur à $past (il a été mis à jour).
```

#### `expectException($classe)`
Déclare qu'on s'attend à ce que le code suivant lève une exception.
```php
$this->expectException(CustomUserMessageAuthenticationException::class);
$this->checker->checkPreAuth($user);  // doit lancer l'exception
```
Si l'exception n'est pas lancée, le test échoue.

---

### Ce qui se passe quand un test échoue

Exemple : on modifie `User::isVerified()` pour qu'il retourne `true` par défaut. Le test `testDefaultValues` échoue :

```
FAILURES!
Tests: 25, Assertions: 56, Failures: 1.

UserTest::testDefaultValues
Failed asserting that true is false.

tests/Unit/Entity/UserTest.php:19
```

PHPUnit indique : le nom du test, l'assertion exacte qui a échoué, et le numéro de ligne. En quelques secondes on sait exactement où chercher.

---

### Décorticage de `UserTest`

#### `testGetRolesAlwaysIncludesRoleUser`

```php
public function testGetRolesAlwaysIncludesRoleUser(): void
{
    $user = new User();
    // On n'assigne aucun rôle explicite — le tableau interne est vide.

    $this->assertContains('ROLE_USER', $user->getRoles());
    // Pourtant, getRoles() doit toujours retourner ROLE_USER.
}
```

**Pourquoi ce test ?** Dans `User::getRoles()`, le code est :
```php
public function getRoles(): array
{
    $roles = $this->roles;
    $roles[] = 'ROLE_USER';   // ← ajouté systématiquement
    return array_unique($roles);
}
```

Si quelqu'un supprime la ligne `$roles[] = 'ROLE_USER'` par erreur, tous les utilisateurs perdraient leur accès de base sans aucun message d'erreur visible. Ce test le détecterait immédiatement.

#### `testGetRolesNoDuplicates`

```php
public function testGetRolesNoDuplicates(): void
{
    $user = new User();
    $user->setRoles(['ROLE_USER']);   // ROLE_USER déjà dans le tableau
    // getRoles() va ajouter ROLE_USER une deuxième fois, puis array_unique() doit le dédupliquer

    $roles = $user->getRoles();

    $this->assertCount(1, $roles);   // doit rester un seul ROLE_USER
}
```

#### `testSettersReturnStatic`

```php
public function testSettersReturnStatic(): void
{
    $user = new User();

    $this->assertSame($user, $user->setEmail('a@b.fr'));
    // setEmail() doit retourner $this (le même objet)
    // Ça permet le chaînage : $user->setEmail('...')->setNom('...')->setPrenom('...')
}
```

**Pourquoi tester ça ?** Si un setter est modifié et qu'on oublie le `return $this;`, le chaînage casse silencieusement (PHP retournerait `null`). Ce type de régression est difficile à détecter sans test.

#### `testEraseCredentialsIsNoop`

```php
public function testEraseCredentialsIsNoop(): void
{
    $user = new User();
    $user->setPassword('secret_hash');
    $user->eraseCredentials();   // méthode requise par l'interface UserInterface

    $this->assertSame('secret_hash', $user->getPassword());
    // Le mot de passe ne doit PAS être effacé
}
```

**Pourquoi ne pas effacer le mot de passe ?** Parce qu'il est déjà haché (bcrypt). `eraseCredentials()` sert à effacer les données sensibles en clair (un champ `plainPassword` temporaire). Notre entité n'en a pas, donc la méthode ne fait rien — et c'est bien ce qu'on vérifie.

---

### Décorticage de `DemandeInscriptionTest`

Ce fichier teste la logique métier de `DemandeInscription`. Le test le plus important est celui du token.

#### `testIsTokenValideReturnsTrueWhenFutureExpiry`

```php
public function testIsTokenValideReturnsTrueWhenFutureExpiry(): void
{
    $demande = new DemandeInscription();
    $demande->setToken('abc123');
    $demande->setTokenExpiresAt(new \DateTimeImmutable('+1 hour'));  // expire dans 1h

    $this->assertTrue($demande->isTokenValide());
}
```

#### `testIsTokenValideReturnsFalseWhenExpired`

```php
public function testIsTokenValideReturnsFalseWhenExpired(): void
{
    $demande = new DemandeInscription();
    $demande->setTokenExpiresAt(new \DateTimeImmutable('-1 second'));  // expiré il y a 1 seconde

    $this->assertFalse($demande->isTokenValide());
}
```

#### `testIsTokenValideReturnsFalseWhenNoExpiryDate`

```php
public function testIsTokenValideReturnsFalseWhenNoExpiryDate(): void
{
    $demande = new DemandeInscription();
    // tokenExpiresAt = null — pas de date d'expiration

    $this->assertFalse($demande->isTokenValide());
}
```

**Pourquoi ces trois cas ?** La méthode `isTokenValide()` a trois chemins possibles :
1. La date est dans le futur → valide
2. La date est dans le passé → invalide
3. Pas de date → invalide

Chaque chemin est une règle métier distincte. Si on oubliait de tester le cas `null` et qu'on écrivait `$this->tokenExpiresAt > new \DateTimeImmutable()` sans vérifier le null, PHP lancerait une erreur en production. Ce test protège contre ça.

---

### Décorticage de `ProtocoleTest`

#### `testSetPdfFileRefreshesUpdatedAt`

```php
public function testSetPdfFileRefreshesUpdatedAt(): void
{
    $protocole = new Protocole();
    $past = new \DateTimeImmutable('-1 day');
    $protocole->setUpdatedAt($past);             // on force updatedAt dans le passé

    $protocole->setPdfFile(new File('dummy.pdf', false));
    // ↑ le deuxième argument "false" dit à Symfony de ne PAS vérifier
    //   que le fichier existe sur le disque — on n'a pas besoin d'un vrai fichier

    $this->assertGreaterThan($past, $protocole->getUpdatedAt());
    // updatedAt doit maintenant être postérieur à $past
}
```

**Contexte :** VichUploaderBundle a besoin que `updatedAt` change quand un fichier est attaché — sinon il ne sait pas qu'il doit re-traiter le fichier. Dans `Protocole::setPdfFile()`, il y a donc :
```php
public function setPdfFile(?File $file): void
{
    $this->pdfFile = $file;
    if ($file !== null) {
        $this->updatedAt = new \DateTimeImmutable();  // ← comportement critique
    }
}
```

Si quelqu'un supprime cette ligne en pensant que c'est du code inutile, VichUploader ne fonctionnera plus. Ce test le détecterait.

#### `testSetPdfFileNullDoesNotRefreshUpdatedAt`

```php
public function testSetPdfFileNullDoesNotRefreshUpdatedAt(): void
{
    $protocole = new Protocole();
    $past = new \DateTimeImmutable('-1 day');
    $protocole->setUpdatedAt($past);

    $protocole->setPdfFile(null);   // on retire le fichier

    $this->assertEquals($past, $protocole->getUpdatedAt());
    // updatedAt NE DOIT PAS changer quand on retire le fichier
}
```

**Pourquoi tester le cas null séparément ?** Parce que c'est un comportement intentionnellement différent — retirer le fichier ne doit pas déclencher de mise à jour. Ce test exprime et protège cette intention.

---

### Décorticage de `UserCheckerTest`

`UserChecker` est la classe Symfony qui vérifie si un utilisateur a le droit de se connecter. Dans ce projet, elle bloque les utilisateurs dont l'adresse email n'a pas été vérifiée.

#### `testCheckPreAuthSkipsNonAppUser`

```php
public function testCheckPreAuthSkipsNonAppUser(): void
{
    $user = $this->createStub(UserInterface::class);
    // createStub() crée un objet qui implémente UserInterface
    // mais qui n'est PAS une instance de App\Entity\User

    $this->checker->checkPreAuth($user);
    // Ne doit pas lancer d'exception — UserChecker ignore les utilisateurs
    // qui ne sont pas de type App\Entity\User

    $this->addToAssertionCount(1);
    // PHPUnit exige au moins une assertion par test.
    // Ce test vérifie l'absence d'exception (pas de plantage = succès).
    // addToAssertionCount(1) dit "j'ai bien fait une vérification".
}
```

**Pourquoi `createStub()` et pas `new User()` ?** Parce qu'on veut un objet qui n'est PAS un `User`. `UserInterface` est l'interface Symfony pour tous les types d'utilisateurs — d'autres bundles peuvent avoir leurs propres classes. `UserChecker` doit les ignorer proprement.

#### `testCheckPreAuthThrowsWhenUserNotVerified`

```php
public function testCheckPreAuthThrowsWhenUserNotVerified(): void
{
    $user = new User();
    $user->setIsVerified(false);   // email non vérifié

    $this->expectException(CustomUserMessageAuthenticationException::class);
    // ↑ doit être placé AVANT l'appel qui lance l'exception

    $this->checker->checkPreAuth($user);
    // ↑ doit lancer l'exception — si elle n'est pas lancée, le test échoue
}
```

**Pourquoi ce test est-il critique ?** Si `UserChecker` est modifié et cesse de bloquer les non-vérifiés, des utilisateurs pourraient se connecter avant que leur email soit validé. Ce test détecterait cette régression immédiatement.

---

### `setUp()` : factoriser la préparation

Dans `UserCheckerTest`, on a besoin d'une instance de `UserChecker` dans chaque test. Au lieu de la recréer dans chaque méthode, on utilise `setUp()` :

```php
class UserCheckerTest extends TestCase
{
    private UserChecker $checker;

    protected function setUp(): void
    //         ↑ méthode spéciale de PHPUnit : s'exécute AVANT chaque test
    {
        $this->checker = new UserChecker();
    }

    public function testCheckPreAuthThrowsWhenUserNotVerified(): void
    {
        // $this->checker est disponible — setUp() a déjà tourné
        $this->checker->checkPreAuth($user);
    }
}
```

`setUp()` s'exécute une fois par test (pas une fois par classe). Chaque test repart avec une instance fraîche de `UserChecker`. C'est l'équivalent d'un `@Before` en JUnit (Java) ou d'un `beforeEach` en Jest (JavaScript).

---

### Stubs et mocks : remplacer des dépendances

Quand on teste `UserChecker`, on doit lui passer un objet `UserInterface`. Mais `UserInterface` est une interface — on ne peut pas faire `new UserInterface()`.

**Le stub** est un objet généré par PHPUnit qui implémente l'interface, sans aucun comportement réel :

```php
$user = $this->createStub(UserInterface::class);
// $user est maintenant un objet qui implémente UserInterface
// Toutes ses méthodes retournent null par défaut
// On peut l'utiliser partout où UserInterface est attendu
```

**La différence stub / mock :**

| | `createStub()` | `createMock()` |
|---|---|---|
| **Usage** | Remplaçant passif (on veut juste un objet du bon type) | Remplaçant actif (on veut vérifier qu'il est appelé d'une certaine façon) |
| **Exemple** | Fournir un `UserInterface` quelconque | Vérifier qu'un `MailerInterface` a bien été appelé une fois avec tel email |

Dans nos tests unitaires, on utilise uniquement des stubs : on n'a pas besoin de vérifier que des services externes sont appelés.

**Règle PHPUnit 12 :** utiliser `createMock()` sans configurer d'expectations génère un notice. PHPUnit 12 est strict là-dessus — il faut `createStub()` pour un remplaçant passif.

---

### La configuration `phpunit.xml.dist`

```xml
<phpunit failOnDeprecation="true"
         failOnNotice="true"
         failOnWarning="true" ...>
```

Ces trois options rendent les tests stricts :
- **`failOnDeprecation`** : si le code utilise une API dépréciée (ex : `new Length(['max' => 100])` au lieu de `new Length(max: 100)`), le test échoue. Force à garder le code à jour.
- **`failOnNotice`** : si PHPUnit génère un notice (ex : `createMock()` sur une classe concrète), le test échoue.
- **`failOnWarning`** : même chose pour les warnings PHP.

Sans ces options, les problèmes s'accumulent silencieusement. Avec elles, chaque problème est détecté au plus tôt.

---

### Comment lire la sortie de PHPUnit

```
.........................                25 / 25 (100%)

Time: 00:00.426, Memory: 16.00 MB

OK (25 tests, 56 assertions)
```

Chaque caractère = un test :
- `.` = test passé
- `F` = test échoué (assertion fausse)
- `E` = erreur PHP pendant le test (exception non attendue, etc.)
- `S` = test ignoré (`$this->markTestSkipped()`)
- `N` = notice PHPUnit (avec `failOnNotice="true"`, traité comme un échec)

En cas d'échec, PHPUnit affiche le détail après la barre de progression :

```
FAILURES!
Tests: 25, Assertions: 56, Failures: 1.

1) UserTest::testDefaultValues
Failed asserting that true is false.

tests/Unit/Entity/UserTest.php:19
```

---

### Structure des fichiers

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

## Problèmes rencontrés pendant le ticket #13

### 1. `phpunit.dist.xml` au lieu de `phpunit.xml.dist`

PHPUnit cherche automatiquement dans cet ordre : `phpunit.xml`, puis `phpunit.xml.dist`. Le fichier était mal nommé (`phpunit.dist.xml`), PHPUnit ne le trouvait pas.
**Correction :** créé `phpunit.xml.dist` avec le même contenu, supprimé l'ancien.

### 2. `createMock(File::class)` sur une classe concrète

`File` hérite de `SplFileInfo` (classe PHP interne). PHPUnit 12 génère un notice quand on crée un mock d'une classe concrète.
**Correction :** `new File('dummy.pdf', false)` — le deuxième argument `false` désactive la vérification d'existence du fichier sur le disque.

### 3. `createMock()` sans expectations

PHPUnit 12 distingue `createMock()` (pour vérifier des appels) de `createStub()` (pour un remplaçant passif). Utiliser `createMock()` sans expectations génère un notice.
**Correction :** remplacé par `$this->createStub(UserInterface::class)`.

---

## Tests fonctionnels (#35)

---

### Qu'est-ce qu'un test fonctionnel ?

Un test unitaire instancie une classe PHP directement et vérifie son comportement en isolation. Il n'y a pas de HTTP, pas de serveur, pas de navigateur.

Un test fonctionnel va plus loin : il **simule un navigateur** qui fait une vraie requête HTTP à l'application. Il vérifie ce qu'un utilisateur obtiendrait réellement en tapant une URL.

```
Test unitaire  →  new User() → user->getEmail() → assert "test@test.fr"
Test fonctionnel → GET /login → réponse HTTP → assert code 200, assert formulaire présent
```

Ce que le test fonctionnel vérifie concrètement : "si j'ouvre cette URL dans mon navigateur, est-ce que j'obtiens bien ce que j'attends ?"

---

### Comment ça marche techniquement

Un test fonctionnel Symfony utilise `WebTestCase` (au lieu de `TestCase` pour les tests unitaires). Cette classe fournit un **client HTTP de test** qui :

1. Reçoit une requête (`GET /login`)
2. Démarre le noyau Symfony complet (comme si le serveur web tournait)
3. Exécute le controller correspondant
4. Retourne la réponse HTTP
5. Permet de faire des assertions sur cette réponse

```php
class MonTest extends WebTestCase   // ← hérite de WebTestCase, pas TestCase
{
    public function testQuelqueChose(): void
    {
        $client = static::createClient();      // crée le "navigateur de test"
        $client->request('GET', '/login');     // fait une requête HTTP
        $this->assertResponseIsSuccessful();   // vérifie la réponse
    }
}
```

**Rien ne tourne dans le navigateur.** Tout se passe en PHP, en mémoire. C'est très rapide et ne nécessite pas de serveur web démarré.

---

### Les codes HTTP : ce que signifient 200, 302, 403

Le protocole HTTP définit des codes de statut qui indiquent ce qui s'est passé côté serveur.

| Code | Nom | Signification |
|---|---|---|
| **200** | OK | La page existe et l'utilisateur a le droit de la voir |
| **302** | Found (redirect) | L'URL existe mais renvoie ailleurs (ex : login requis → `/login`) |
| **403** | Forbidden | L'URL existe, l'utilisateur est connecté, mais il n'a pas les droits |
| **404** | Not Found | L'URL n'existe pas |
| **500** | Internal Server Error | Le code PHP a planté |

Dans notre application, le schéma est le suivant :

```
Visiteur anonyme → page protégée      →  302 vers /login
Utilisateur connecté → page interdite →  403 Access Denied
Utilisateur connecté → page autorisée →  200 OK
```

C'est exactement ce que nos tests vérifient.

---

### Décorticage de `AccessControlTest`

Ce fichier teste les redirections d'un visiteur anonyme (non connecté). Aucune base de données n'est nécessaire — on teste juste que les URLs protégées redirigent bien.

```php
class AccessControlTest extends WebTestCase
{
    public function testHomeIsPublic(): void
    {
        $client = static::createClient();   // crée le client (navigateur de test)
        $client->request('GET', '/');       // visite la page d'accueil
        $this->assertResponseIsSuccessful(); // vérifie que la réponse est 200
    }
```

`assertResponseIsSuccessful()` = "la réponse a un code 2xx" (200, 201, 204...). Pour une page normale, c'est 200.

```php
    public function testParcourirRedirectsToLoginWhenAnonymous(): void
    {
        $client = static::createClient();
        $client->request('GET', '/parcourir');
        $this->assertResponseRedirects('/login');  // vérifie que c'est une redirection vers /login
    }
```

`assertResponseRedirects('/login')` vérifie deux choses : que le code est 302, ET que l'en-tête `Location` contient `/login`.

Pourquoi `/parcourir` redirige-t-il ? Parce que dans `NavigationController`, le contrôleur porte l'attribut `#[IsGranted('ROLE_USER')]`. Symfony intercepte la requête avant même d'entrer dans le contrôleur et redirige vers la page de login.

---

### Décorticage de `SecurityTest`

Ce fichier teste le formulaire de login. Il a besoin de la base de données pour vérifier les credentials.

#### Test 1 : vérifier que le formulaire contient les bons champs

```php
public function testLoginFormContainsRequiredFields(): void
{
    $client = static::createClient();
    $crawler = $client->request('GET', '/login');   // $crawler = l'objet qui représente la page HTML

    $this->assertResponseIsSuccessful();
    $this->assertSelectorExists('input[name="_username"]');   // cherche un <input name="_username">
    $this->assertSelectorExists('input[name="_password"]');
    $this->assertSelectorExists('input[name="_csrf_token"]'); // token anti-falsification
}
```

`$crawler` est un objet qui permet de naviguer dans le HTML de la page (comme jQuery en PHP). `assertSelectorExists()` prend un sélecteur CSS et vérifie qu'il trouve au moins un élément.

**Pourquoi tester ça ?** Si quelqu'un renomme le champ `_username` en `email` dans le template, le formulaire de login cessera de fonctionner silencieusement. Ce test le détectera immédiatement.

#### Test 2 : connexion avec les bons identifiants

```php
public function testLoginWithValidCredentials(): void
{
    $client = static::createClient();
    $crawler = $client->request('GET', '/login');   // visite d'abord la page pour récupérer le token CSRF

    // Récupère la valeur du token CSRF généré par Symfony dans le formulaire
    $csrfToken = $crawler->filter('input[name="_csrf_token"]')->attr('value');

    // Soumet le formulaire de login
    $client->request('POST', '/login', [
        '_username'   => 'user@test.fr',
        '_password'   => 'utilisateur',
        '_csrf_token' => $csrfToken,        // le token doit correspondre à celui de la page
    ]);

    // Après un login réussi, Symfony redirige vers la page d'accueil
    $this->assertResponseStatusCodeSame(302);
    $client->followRedirect();          // suit la redirection (comme un navigateur)
    $this->assertRouteSame('app_home'); // vérifie qu'on est bien sur la route app_home
}
```

**Le token CSRF :** Symfony génère un jeton unique par session pour protéger le formulaire de login contre les attaques CSRF (Cross-Site Request Forgery). Sans ce token valide, la soumission est rejetée. Dans le test, on visite d'abord `/login` pour que Symfony génère le token, puis on le récupère dans le HTML et on le renvoie avec le POST.

**`followRedirect()`** : après un login réussi, Symfony répond avec un 302. Si on ne suit pas la redirection, on reste sur cette réponse 302. `followRedirect()` dit au client de faire automatiquement la requête suivante vers l'URL de redirection.

#### Test 3 : mauvais mot de passe

```php
public function testLoginWithWrongPasswordStaysOnLoginPage(): void
{
    $client = static::createClient();
    $crawler = $client->request('GET', '/login');
    $csrfToken = $crawler->filter('input[name="_csrf_token"]')->attr('value');

    $client->request('POST', '/login', [
        '_username'   => 'user@test.fr',
        '_password'   => 'mauvais_mot_de_passe',
        '_csrf_token' => $csrfToken,
    ]);

    // Symfony redirige vers /login avec un message d'erreur en session
    $this->assertResponseRedirects('/login');
    $client->followRedirect();
    // L'élément d'erreur doit être présent dans la page
    $this->assertSelectorExists('.bg-red-50');
}
```

`assertSelectorExists('.bg-red-50')` : dans le template `login.html.twig`, le bloc d'erreur a la classe Tailwind `bg-red-50` (fond rouge clair). Ce sélecteur CSS vérifie que cet élément est bien dans la page.

---

### Décorticage de `RoleAccessTest`

Ce fichier teste que chaque rôle accède uniquement à ce qu'il a le droit de voir.

#### La méthode helper `loginAs()`

```php
private function loginAs(string $email): KernelBrowser
{
    $client = static::createClient();

    // Récupère l'utilisateur depuis la BDD de test
    $user = static::getContainer()
        ->get(UserRepository::class)
        ->findOneBy(['email' => $email]);

    // Connecte l'utilisateur directement dans la session
    $client->loginUser($user);

    return $client;
}
```

`static::getContainer()` donne accès au conteneur de services Symfony — c'est-à-dire tous les services injectables (repositories, mailers, etc.). On l'utilise ici pour récupérer un utilisateur depuis la base de données de test.

`$client->loginUser($user)` est une méthode Symfony spécifique aux tests. Elle **court-circuite le processus de login** : au lieu de soumettre le formulaire avec email + mot de passe + token CSRF, elle injecte directement l'utilisateur dans la session. C'est l'équivalent de "faire croire au système que cet utilisateur est déjà connecté".

**Pourquoi ne pas utiliser le formulaire à chaque fois ?** Les tests de contrôle d'accès ne testent pas le login — ils testent les permissions. Passer par le formulaire à chaque test serait lent et redondant. `loginUser()` sépare la préoccupation du login (testé dans `SecurityTest`) de celle des permissions (testée ici).

#### Les tests de permissions

```php
public function testUserCannotAccessModerateurPages(): void
{
    $client = $this->loginAs('user@test.fr');          // connecté comme ROLE_USER
    $client->request('GET', '/moderateur/domaines');
    $this->assertResponseStatusCodeSame(403);           // accès refusé
}

public function testAdminCanAccessModerateurPagesViaHierarchy(): void
{
    $client = $this->loginAs('admin@test.fr');          // connecté comme ROLE_ADMIN
    $client->request('GET', '/moderateur/domaines');
    $this->assertResponseIsSuccessful();                // autorisé via la hiérarchie des rôles
}
```

Ce dernier test vérifie la **hiérarchie des rôles** définie dans `security.yaml` :

```yaml
role_hierarchy:
    ROLE_MODERATEUR: ROLE_USER
    ROLE_ADMIN: ROLE_MODERATEUR
```

`ROLE_ADMIN` hérite de `ROLE_MODERATEUR`, qui hérite de `ROLE_USER`. Un admin peut donc accéder à toutes les pages moderateur. Si cette hiérarchie était cassée, `testAdminCanAccessModerateurPagesViaHierarchy` le détecterait.

---

### Pourquoi une base de données séparée ?

Les tests fonctionnels qui utilisent `loginUser()` ont besoin de récupérer un vrai objet `User` depuis la BDD. On ne peut pas utiliser la BDD de dev parce que :

- Les données de dev changent (on crée, modifie, supprime des choses manuellement)
- Les tests doivent tourner dans un état prévisible et reproductible
- Un test qui crée ou modifie des données ne doit pas affecter le travail en cours

La BDD de test (`lereperedesprotocoles_v2_test`) est chargée avec des fixtures connues et stables. On peut la réinitialiser à tout moment.

**Comment Symfony sait quelle BDD utiliser ?** Dans `doctrine.yaml` :
```yaml
when@test:
    doctrine:
        dbal:
            dbname_suffix: '_test'
```

Doctrine ajoute automatiquement `_test` au nom de la BDD quand `APP_ENV=test`. Si `DATABASE_URL` pointe sur `lereperedesprotocoles_v2`, Doctrine utilise `lereperedesprotocoles_v2_test`.

---

### Mise en place de la BDD de test

```powershell
# Copier le template de credentials et l'adapter
copy .env.test.local.dist .env.test.local
# Dans .env.test.local : mettre tes vraies credentials MySQL (même que .env.local mais sans _test)

# Créer la BDD, le schéma, et charger les fixtures
php bin/console doctrine:database:create --env=test --if-not-exists
php bin/console doctrine:schema:create --env=test
php bin/console doctrine:fixtures:load --env=test --no-interaction

# Lancer les tests
vendor/bin/phpunit --testsuite Functional
```

À re-exécuter depuis `fixtures:load` si les données changent, depuis `schema:create` si le schéma change.

---

### Résumé de tous les tests

| Fichier | Tests | BDD | Ce qu'il protège |
|---|---|---|---|
| `AccessControlTest` | 7 | ❌ | Les redirections anonymes restent en place |
| `SecurityTest` | 4 | ✅ | Le formulaire de login fonctionne |
| `RoleAccessTest` | 7 | ✅ | La hiérarchie des rôles est respectée |
| `UserTest` | 7 | ❌ | L'entité User se comporte correctement |
| `DemandeInscriptionTest` | 7 | ❌ | La logique de token de vérification email |
| `ProtocoleTest` | 7 | ❌ | Les hooks VichUploader (`updatedAt`) |
| `UserCheckerTest` | 4 | ❌ | Le UserChecker bloque les non-vérifiés |

**Total : 43 tests, 72 assertions.**
