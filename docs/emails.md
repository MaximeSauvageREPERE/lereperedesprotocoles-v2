# Emails transactionnels — lereperedesprotocoles-v2

Ce document explique comment le projet envoie des emails, pourquoi c'est fait ainsi, et comment tester que les emails partent correctement.

---

## Pourquoi des emails transactionnels ?

Un **email transactionnel** est déclenché par une action utilisateur précise (inscription, validation de compte...), par opposition aux emails marketing envoyés en masse.

Dans ce projet, 3 événements déclenchent un email :

| Déclencheur | Destinataire | Email envoyé |
|---|---|---|
| Soumission du formulaire d'inscription | Le candidat | Lien de confirmation d'email |
| Admin approuve la demande | Le candidat | Accès accordé + lien de connexion |
| Admin refuse la demande | Le candidat | Refus + motif |

---

## Symfony Mailer : le composant

Symfony Mailer est le composant officiel pour envoyer des emails. Il est installé via :

```bash
composer require symfony/mailer
```

Il est déjà présent dans ce projet. Il remplace l'ancien `SwiftMailer` (abandonné depuis Symfony 6).

**Les 3 concepts clés :**

```
Transport  →  comment l'email est acheminé (SMTP, API Mailgun, SES...)
Mailer     →  le service PHP qui orchestre l'envoi
Email      →  l'objet qui représente le message (destinataires, sujet, corps)
```

---

## Configuration : MAILER_DSN

Toute la configuration tient dans une variable d'environnement `MAILER_DSN`, définie dans `.env` :

```env
MAILER_DSN=null://null
```

Le format est `protocole://identifiants@serveur:port`.

**Exemples de valeurs :**

| Valeur | Comportement |
|---|---|
| `null://null` | Aucun email envoyé (dev / tests) |
| `smtp://user:pass@smtp.example.com:587` | Envoi SMTP classique |
| `smtp://localhost:1025` | Mailpit en local (capture les emails) |
| `sendgrid://KEY@default` | API SendGrid |
| `ses+smtp://KEY:SECRET@default` | Amazon SES |

En développement, `null://null` est parfait : aucun risque d'envoyer par erreur à de vrais utilisateurs. Les emails peuvent tout de même être consultés via le Symfony Profiler (voir ci-dessous).

---

## TemplatedEmail : email avec template Twig

Le projet n'utilise pas la classe `Email` basique mais `TemplatedEmail`, qui permet d'écrire le corps de l'email dans un fichier Twig :

```php
// src/Controller/InscriptionController.php
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

$email = (new TemplatedEmail())
    ->from('noreply@lereperedesprotocoles.fr')
    ->to($demande->getEmail())
    ->subject('Confirmez votre adresse email')
    ->htmlTemplate('emails/inscription_confirmation.html.twig')
    ->context(['demande' => $demande, 'token' => $token]);

$mailer->send($email);
```

**Déconstruction :**

- `->from(...)` : expéditeur affiché dans le client mail
- `->to(...)` : destinataire
- `->subject(...)` : objet de l'email
- `->htmlTemplate(...)` : chemin vers un fichier Twig dans `templates/`
- `->context([...])` : variables passées au template Twig (comme `$this->render()` pour les pages web)
- `$mailer->send($email)` : déclenche l'envoi via le Transport configuré dans `MAILER_DSN`

---

## Les templates Twig d'email

Les templates sont dans `templates/emails/`. Contrairement aux pages web qui héritent de `base.html.twig`, les emails sont des documents HTML **autonomes et inline** :

```html
<!-- templates/emails/inscription_confirmation.html.twig -->
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"></head>
<body style="font-family: sans-serif; color: #374151; max-width: 600px;">

    <h2 style="color: #1d4ed8;">Confirmez votre adresse email</h2>

    <p>Bonjour {{ demande.prenom }},</p>

    <p style="text-align: center;">
        <a href="{{ url('app_inscription_confirmer', {token: token}) }}"
           style="background-color: #2563eb; color: white; padding: 12px 24px;">
            Confirmer mon adresse email
        </a>
    </p>

</body>
</html>
```

**Pourquoi les styles sont-ils en `style="..."` inline ?**

La plupart des clients mail (Outlook, Gmail, Apple Mail) ignorent les balises `<style>` et les classes CSS. Les styles inline sont la seule manière fiable de mettre en forme un email HTML.

**Pourquoi `url()` et non `path()` ?**

`path()` génère une URL relative (`/inscription/confirmer/abc`). Un email est ouvert dans un client mail, pas dans le navigateur du projet : le lien relatif ne fonctionnerait pas. `url()` génère une URL absolue avec le nom de domaine (`https://lereperedesprotocoles.fr/inscription/confirmer/abc`).

---

## Flow complet de l'inscription

```
[Candidat]  remplit le formulaire /inscription
     ↓
[Serveur]   crée DemandeInscription en BDD (statut: en_attente, emailVerifie: false)
            génère un token aléatoire (ByteString::fromRandom(32))
            stocke le token + expiration dans DemandeInscription
     ↓
[Mailer]    envoie inscription_confirmation.html.twig
            contient url('app_inscription_confirmer', {token: token})
     ↓
[Candidat]  clique sur le lien dans l'email
     ↓
[Serveur]   vérifie le token (valide et non expiré)
            passe emailVerifie à true
            supprime le token
     ↓
[Admin]     voit la demande dans /admin/demandes
            clique "Approuver" ou "Refuser"
     ↓
[Mailer]    envoie inscription_approuvee.html.twig
            ou inscription_refusee.html.twig (avec le motif)
     ↓
[Candidat]  peut se connecter (si approuvé)
```

---

## Voir les emails en développement

Avec `MAILER_DSN=null://null`, les emails ne partent pas. Mais Symfony les capture quand même dans le **Profiler** :

1. Déclencher un email (ex : soumettre le formulaire d'inscription)
2. Cliquer sur l'icône email dans la barre Symfony Profiler (en bas de la page)
3. Ou aller directement sur `http://127.0.0.1:8000/_profiler/latest?panel=mailer`

Cela montre l'email tel qu'il aurait été envoyé, avec son contenu HTML, les en-têtes, les destinataires.

**Alternative : Mailpit**

Pour tester avec un vrai client mail en local, [Mailpit](https://mailpit.axllent.org/) simule un serveur SMTP et affiche les emails reçus dans une interface web :

```env
# .env.local
MAILER_DSN=smtp://localhost:1025
```

Mailpit se lance avec `docker run -p 8025:8025 -p 1025:1025 axllent/mailpit` puis s'ouvre sur http://localhost:8025.

---

## Tests PHPUnit : vérifier que les emails sont envoyés

Symfony fournit des assertions spécifiques pour les emails dans les tests fonctionnels. Elles sont disponibles dans toute classe qui hérite de `WebTestCase`.

**Le fichier de test :** `tests/Functional/EmailTest.php`

### Test 1 — email de confirmation d'inscription

```php
public function testInscriptionEnvoieEmailConfirmation(): void
{
    $client = static::createClient();
    $profession = static::getContainer()->get(ProfessionRepository::class)->findOneBy([]);

    $crawler = $client->request('GET', '/inscription');
    $form = $crawler->selectButton('Envoyer ma demande')->form([
        'inscription[prenom]' => 'Nouveau',
        'inscription[email]' => 'email-test-'.uniqid().'@test.fr',
        // ...
    ]);
    $client->submit($form);

    $this->assertEmailCount(1);                                                  // 1 email envoyé
    $email = $this->getMailerMessage();                                          // récupère le 1er
    $this->assertEmailHtmlBodyContains($email, 'Confirmez votre adresse email'); // vérifie le contenu
}
```

### Test 2 — email d'approbation

```php
public function testApprobationDemandeEnvoieEmail(): void
{
    $client = static::createClient();
    $client->loginUser(/* admin */);

    $demande = $this->creerDemandePendante(); // crée un objet en BDD via EntityManager

    $crawler = $client->request('GET', '/admin/demandes');
    $form = $crawler->filter('form[action$="/'.$demande->getId().'/approuver"]')->form();
    $client->submit($form);

    $this->assertEmailCount(1);
    $this->assertEmailAddressContains($this->getMailerMessage(), 'To', $demande->getEmail());
    $this->assertEmailSubjectContains($this->getMailerMessage(), 'approuvé');
}
```

**Pourquoi GET la page de liste avant de soumettre ?**

Le bouton "Approuver" est un formulaire avec un token CSRF généré dans le template : `{{ csrf_token('approuver_' ~ demande.id) }}`. En naviguant d'abord sur la page, le token est généré dans la session du client de test — il est alors valide quand le formulaire est soumis.

### Les assertions disponibles

| Assertion | Vérifie |
|---|---|
| `assertEmailCount(1)` | Exactement 1 email envoyé pendant la requête |
| `getMailerMessage()` | Retourne le 1er email (`RawMessage`) |
| `assertEmailAddressContains($email, 'To', 'x@y.fr')` | Le destinataire contient `x@y.fr` |
| `assertEmailSubjectContains($email, 'texte')` | L'objet contient `texte` |
| `assertEmailHtmlBodyContains($email, 'texte')` | Le corps HTML contient `texte` |
| `assertEmailTextBodyContains($email, 'texte')` | La version texte contient `texte` |

**Pourquoi le transport `async` est-il remplacé par `sync://` en test ?**

Ce projet utilise Symfony Messenger pour envoyer les emails de façon asynchrone (voir `config/packages/messenger.yaml`). En production, les emails passent par une file Doctrine et sont traités par un worker en arrière-plan. C'est efficace, mais ça pose un problème pour les tests :

- En mode `async`, l'email est **queué** (mis en file d'attente), pas envoyé immédiatement.
- `MessageLoggerListener` le capture avec `queued = true`.
- `assertEmailCount(1)` ne compte que les emails `queued = false` (envoyés synchroniquement).
- Résultat : `assertEmailCount(1)` retourne **0** même si l'email a bien été queué.

La solution est de remplacer le transport `async` par `sync://` dans l'environnement de test (`when@test:` dans `messenger.yaml`). Avec `sync://`, Messenger traite les messages immédiatement et en synchrone. L'email passe par `AbstractTransport::send()` qui déclenche un `MessageEvent` avec `queued = false`, capté par `MessageLoggerListener` — et `assertEmailCount(1)` fonctionne.

```yaml
# config/packages/messenger.yaml
when@test:
    framework:
        messenger:
            transports:
                async: 'sync://'
```

---

## En production

En production, remplacer `MAILER_DSN=null://null` par un vrai transport dans le fichier de secrets ou `.env.local` du serveur :

```env
MAILER_DSN=smtp://login:password@smtp.example.com:587
```

Il faut aussi configurer l'expéditeur et s'assurer que le domaine `noreply@lereperedesprotocoles.fr` est autorisé à envoyer (enregistrements SPF/DKIM/DMARC).

---

## Fichiers concernés

| Fichier | Rôle |
|---|---|
| `.env` | `MAILER_DSN=null://null` (dev/test) |
| `config/packages/mailer.yaml` | Lit `%env(MAILER_DSN)%` |
| `src/Controller/InscriptionController.php` | Envoie l'email de confirmation |
| `src/Controller/Admin/DemandeController.php` | Envoie les emails d'approbation/refus |
| `templates/emails/inscription_confirmation.html.twig` | Template email confirmation |
| `templates/emails/inscription_approuvee.html.twig` | Template email approbation |
| `templates/emails/inscription_refusee.html.twig` | Template email refus |
| `tests/Functional/EmailTest.php` | Tests vérifiant que les emails partent |
