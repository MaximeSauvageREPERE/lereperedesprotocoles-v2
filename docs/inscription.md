# Workflow d'inscription

## Vue d'ensemble

L'accès à la plateforme est restreint. Un visiteur ne peut pas créer un compte lui-même : il soumet une **demande d'inscription**, qui passe par deux étapes de validation avant qu'un compte soit créé.

```
Visiteur          Système                  Admin
   │                  │                      │
   ├─ /inscription ──►│ DemandeInscription   │
   │                  │ statut=en_attente    │
   │                  │ emailVerifie=false   │
   │◄── email ────────┤ token (24h)          │
   │                  │                      │
   ├─ clic lien ─────►│ emailVerifie=true    │
   │                  │ token=null           │
   │                  │                      ├─ /admin/demandes
   │                  │                      │  voit la demande
   │                  │        approuver ────┤
   │                  │ User créé            │
   │◄── email ────────┤ statut=approuvee     │
   │                  │                      │
   │  ou              │        refuser ──────┤
   │◄── email ────────┤ statut=refusee       │
```

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
3. Génère un token aléatoire de 32 caractères (valable **24 heures**)
4. Persiste la demande avec `statut=en_attente`, `emailVerifie=false`
5. Envoie un email de confirmation contenant le lien de vérification

Le visiteur est redirigé vers une page de confirmation lui indiquant de vérifier sa boîte mail.

---

## Étape 2 — Vérification de l'adresse email

**Route :** `GET /inscription/confirmer/{token}` (`app_inscription_confirmer`)

Lorsque le visiteur clique sur le lien reçu par email :

| Cas | Résultat |
|---|---|
| Token valide et email non encore vérifié | `emailVerifie=true`, token effacé, page de confirmation affichée |
| Token expiré (> 24h) ou introuvable | Page d'erreur avec lien vers `/inscription` |
| Email déjà vérifié | Redirection vers `/login` |

Après cette étape, la demande est visible dans l'interface admin.

---

## Étape 3 — Décision de l'admin

**Route :** `GET /admin/demandes` (`admin_demande_index`)  
**Accès :** `ROLE_ADMIN` uniquement

La page présente deux tableaux :
- **À traiter** — demandes dont l'email est vérifié, en attente de décision
- **En attente d'email** — demandes soumises mais dont le lien n'a pas encore été cliqué

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
| Soumission du formulaire | Demandeur | `emails/inscription_confirmation.html.twig` |
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
