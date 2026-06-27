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
