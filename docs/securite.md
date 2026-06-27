# Sécurité — lereperedesprotocoles-v2

## Rate limiting (protection brute force login)

### Fonctionnement

`login_throttling` est configuré sur le firewall `main` (`config/packages/security.yaml`).
Il bloque les tentatives de connexion après **5 échecs consécutifs sur 1 minute**, par combinaison IP + identifiant saisi.

- Les compteurs sont stockés dans le pool `cache.app` (fichiers dans `var/cache/`).
- Le blocage est levé automatiquement après 1 minute (fenêtre glissante).
- Un login réussi ne réinitialise pas le compteur — les compteurs expirent naturellement.
- Le composant utilisé : `symfony/rate-limiter` v7.x.

### Configuration

```yaml
# config/packages/security.yaml
firewalls:
    main:
        login_throttling:
            max_attempts: 5
            interval: '1 minute'
```

### Message d'erreur

Quand le seuil est atteint, l'utilisateur voit un message du type :
> Trop de tentatives de connexion échouées, veuillez réessayer dans 1 minute.

### Test fonctionnel

`tests/Functional/SecurityTest.php` — `testLoginThrottlingBlocksAfterFiveFailedAttempts` :
- Soumet 5 fois un mauvais mot de passe avec un email unique (isolé du cache des autres tests)
- Vérifie que la 6e tentative affiche le message de blocage ("minute")

### Pourquoi IP + identifiant et pas seulement IP ?

Bloquer uniquement sur l'IP pénaliserait des utilisateurs légitimes derrière un NAT ou un proxy partagé.
La clé composite `IP + email saisi` cible l'attaquant sur un compte précis sans affecter les autres.

---

## Validation des uploads (type MIME réel)

### Contexte

VichUploaderBundle stocke les fichiers sans valider leur contenu. La validation est portée par les contraintes Symfony sur les champs du formulaire (`src/Form/ProtocoleType.php`).

### Deux niveaux de validation

| Niveau | Option | Ce qu'elle vérifie |
|---|---|---|
| Contenu réel | `mimeTypes` | Lit les magic bytes du fichier via PHP `finfo` — indépendant du nom ou du type déclaré par le navigateur |
| Extension | `allowedExtensions` | Vérifie l'extension du nom de fichier original (défense en profondeur) |

La validation `mimeTypes` est la protection principale : un fichier PHP renommé en `.pdf` sera rejeté car `finfo` identifie son contenu réel comme `text/x-php`.

### Fichiers acceptés

| Champ | MIME types autorisés | Extensions | Taille max |
|---|---|---|---|
| PDF | `application/pdf` | `.pdf` | 10 Mo |
| Image | `image/jpeg`, `image/png`, `image/webp` | `.jpg`, `.jpeg`, `.png`, `.webp` | 20 Mo |

SVG délibérément exclu (peut contenir du JavaScript).

### Tests unitaires

`tests/Unit/Security/UploadValidationTest.php` :
- `testPhpFileDisguisedAsPdfIsRejected` — fichier PHP renommé `.pdf` → violation `mimeTypes`
- `testPhpFileDisguisedAsImageIsRejected` — fichier PHP renommé `.jpg` → violation `mimeTypes`
- `testFileWithWrongExtensionIsRejected` — extension `.exe` → violation `allowedExtensions`

---

## Protection CSRF et cache navigateur (bfcache)

### Contexte

L'application utilise Turbo 8 (`@hotwired/turbo` v8.0.23). Turbo Drive intercepte les navigations et maintient un cache interne de snapshots de pages. Le navigateur dispose également de son propre **bfcache** (Back/Forward Cache) qui gèle une copie complète de la page en mémoire pour les navigations arrière/avant.

### Problème

Après un logout, quand l'utilisateur retourne sur `/login`, le navigateur peut restaurer une version gelée de la page depuis son bfcache — une version qui contient le token CSRF de l'ancienne session (désormais invalide). La soumission du formulaire échoue alors avec **"Invalid CSRF token"**, même si l'utilisateur n'a rien fait de suspect.

Deux caches distincts peuvent provoquer ce comportement :

| Cache | Contrôle |
|---|---|
| Cache Turbo (snapshots JS) | `<meta name="turbo-cache-control" content="no-cache">` dans `base.html.twig` |
| **bfcache navigateur** | `Cache-Control: no-store` en header HTTP |

La meta Turbo seule est insuffisante : elle ne communique pas avec le bfcache du navigateur.

### Fix appliqué

`src/Controller/SecurityController.php` — la route `app_login` ajoute des headers HTTP explicites :

```php
$response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate');
$response->headers->set('Pragma', 'no-cache');
```

`no-store` est l'instruction qui bloque le bfcache dans Chrome, Firefox et Safari. `no-cache` et `must-revalidate` ajoutent une protection supplémentaire contre les proxys et caches intermédiaires.

### Formulaires concernés

- **Login** : token `authenticate` validé par `FormLoginAuthenticator` (`enable_csrf: true` dans `security.yaml`)
- **Logout** : token `logout` validé par le firewall. Protégé différemment : `data-turbo="false"` sur le formulaire force une soumission HTTP native, et la page entière a le meta `no-cache` Turbo via `base.html.twig`.
