# Workflow d'inscription

## Vue d'ensemble

L'accès à la plateforme est restreint. Un visiteur ne peut pas créer un compte lui-même : il soumet une **demande d'inscription**, qui passe par deux étapes de validation avant qu'un compte soit créé.

```
Visiteur          Système                  Admin
   │                  │                      │
   ├─ /inscription ──►│ DemandeInscription   │
   │                  │ statut=en_attente    │
   │                  │ emailVerifie=true    │
   │◄── redirect ─────┤ → /login (flash)     │
   │                  │                      ├─ /admin/demandes
   │                  │                      │  voit la demande
   │                  │        approuver ────┤
   │                  │ User créé            │
   │◄── email ────────┤ statut=approuvee     │
   │                  │                      │
   │  ou              │        refuser ──────┤
   │◄── email ────────┤ statut=refusee       │
```

> **Note :** la vérification email à l'inscription est temporairement désactivée pour faciliter les tests. `emailVerifie` est positionné à `true` directement à la soumission. La route `/inscription/confirmer/{token}` reste en place pour la réactivation future (voir [project_email_verification.md](../memory/project_email_verification.md)).

---

## Étape 1 — Soumission du formulaire

**Route :** `GET/POST /inscription` (`app_inscription`)

Le visiteur remplit :
- Prénom, nom
- Adresse email professionnelle
- Profession (liste déroulante, extensible par l'admin)
- Mot de passe (saisi deux fois, minimum 8 caractères)

À la soumission valide, le système :
1. Vérifie qu'aucun `User` ni aucune `DemandeInscription en_attente` n'existe avec cet email
2. Hache le mot de passe et le stocke dans `DemandeInscription.password`
3. Persiste la demande avec `statut=en_attente`, `emailVerifie=true`
4. Redirige vers `/login` avec un flash de confirmation (pattern PRG)

La demande est immédiatement visible dans l'interface admin sans étape de vérification email.

---

## Étape 2 — Décision de l'admin

**Route :** `GET /admin/demandes` (`admin_demande_index`)  
**Accès :** `ROLE_ADMIN` uniquement

La page présente deux tableaux :
- **À traiter** — demandes en attente de décision (`emailVerifie=true`)
- **En attente d'email** — demandes dont l'email n'est pas encore vérifié (inutilisé tant que la vérification est désactivée)

### Approuver

**Route :** `POST /admin/demandes/{id}/approuver` (protégée par CSRF)

Le système :
1. Crée un `User` en recopiant email, prénom, nom, profession et le mot de passe déjà haché
2. Positionne `User.isVerified=true` et `User.roles=['ROLE_USER']`
3. Met à jour la demande : `statut=approuvee`, `traiteeAt=now`, `utilisateur=User`
4. Envoie un email de bienvenue avec un lien vers `/login`

### Refuser

**Route :** `GET/POST /admin/demandes/{id}/refuser`

L'admin saisit un **motif de refus** (obligatoire). Le système :
1. Met à jour la demande : `statut=refusee`, `traiteeAt=now`, `motifRejet=texte saisi`
2. Envoie un email au demandeur incluant le motif

---

## Entité `DemandeInscription`

| Champ | Type | Rôle |
|---|---|---|
| `email` | string | Identifiant de la demande |
| `prenom`, `nom` | string | Copiés vers `User` si approuvée |
| `profession` | ManyToOne → Profession | Copiée vers `User` si approuvée |
| `password` | string | Mot de passe haché, copié vers `User` |
| `statut` | string | `en_attente` / `approuvee` / `refusee` |
| `emailVerifie` | bool | `true` une fois le lien cliqué |
| `token` | string\|null | Token de vérification email (effacé après usage) |
| `tokenExpiresAt` | DateTimeImmutable\|null | Expiration du token |
| `motifRejet` | text\|null | Renseigné par l'admin en cas de refus |
| `createdAt` | DateTimeImmutable | Date de soumission |
| `traiteeAt` | DateTimeImmutable\|null | Date de décision admin |
| `utilisateur` | OneToOne → User\|null | Lien vers le compte créé |

---

## Emails envoyés

| Déclencheur | Destinataire | Template |
|---|---|---|
| ~~Soumission du formulaire~~ | ~~Demandeur~~ | ~~`emails/inscription_confirmation.html.twig`~~ (désactivé) |
| Approbation admin | Demandeur | `emails/inscription_approuvee.html.twig` |
| Refus admin | Demandeur | `emails/inscription_refusee.html.twig` |

En développement, `MAILER_DSN=null://null` intercepte les emails sans les envoyer. Ils sont consultables dans le **Symfony Profiler** (barre de débogage → onglet "Emails").

---

## Fichiers concernés

| Fichier | Rôle |
|---|---|
| [src/Entity/DemandeInscription.php](../src/Entity/DemandeInscription.php) | Entité |
| [src/Form/InscriptionType.php](../src/Form/InscriptionType.php) | Formulaire public |
| [src/Form/RefuserDemandeType.php](../src/Form/RefuserDemandeType.php) | Formulaire de refus (admin) |
| [src/Controller/InscriptionController.php](../src/Controller/InscriptionController.php) | Routes publiques |
| [src/Controller/Admin/DemandeController.php](../src/Controller/Admin/DemandeController.php) | Routes admin |
| [src/Repository/DemandeInscriptionRepository.php](../src/Repository/DemandeInscriptionRepository.php) | Requêtes filtrées |
