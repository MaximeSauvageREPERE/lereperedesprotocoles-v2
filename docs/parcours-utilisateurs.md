# Parcours utilisateurs

Ce document décrit les actions disponibles et le parcours typique pour chaque rôle de l'application.

La hiérarchie des rôles est cumulative : `ROLE_ADMIN` hérite de `ROLE_MODERATEUR`, qui hérite de `ROLE_USER`.

---

## Visiteur (non connecté)

**Pages accessibles :** `/` · `/inscription` · `/login`

```
/ (accueil)
└── "Je m'inscris" ──► /inscription
                            └── soumet le formulaire
                                └── reçoit un email de vérification
                                    └── clique le lien ──► en attente de validation admin
```

Le visiteur ne peut pas consulter les protocoles. Toute URL protégée redirige vers `/login`.

---

## ROLE_USER — Professionnel de santé

**Accès après approbation de son inscription par un admin.**

### Parcours type : consulter un protocole

```
/login ──► connexion réussie ──► /profil
                                      │
                                      └── "Parcourir les protocoles" ──► /domaines
                                                                              │
                                                                    choisit un domaine
                                                                              │
                                                                         /domaine/{slug}
                                                                              │
                                                                    choisit une rubrique
                                                                              │
                                                                         /rubrique/{slug}
                                                                              │
                                                                    choisit un thème
                                                                              │
                                                                         /theme/{slug}
                                                                              │
                                                                    choisit un protocole
                                                                              │
                                                                         /protocole/{slug}
                                                                              │
                                                                    télécharge le PDF
```

### Pages disponibles

| URL | Description |
|---|---|
| `/profil` | Affiche prénom, nom, profession, rôle, date d'inscription |
| `/domaines` | Liste de tous les domaines |
| `/domaine/{slug}` | Rubriques d'un domaine |
| `/rubrique/{slug}` | Thèmes d'une rubrique |
| `/theme/{slug}` | Protocoles d'un thème |
| `/protocole/{slug}` | Détail d'un protocole + lien PDF + image |

---

## ROLE_MODERATEUR — Modérateur de contenu

**Hérite de toutes les pages ROLE_USER, plus la gestion du contenu.**

### Parcours type : publier un nouveau protocole

```
/moderateur/protocoles ──► "+ Nouveau protocole"
                                    │
                            remplit le formulaire :
                            - titre
                            - thème (liste déroulante)
                            - description (optionnel)
                            - fichier PDF (upload)
                            - image de couverture (optionnel)
                                    │
                            soumet ──► protocole visible dans /theme/{slug}
```

### Parcours type : organiser le contenu

```
/moderateur/domaines  ──► créer / modifier / supprimer un domaine
/moderateur/rubriques ──► créer / modifier / supprimer une rubrique
                               └── associer à un ou plusieurs domaines
/moderateur/themes    ──► créer / modifier / supprimer un thème
                               └── associer à une rubrique
/moderateur/protocoles──► créer / modifier / supprimer un protocole
                               └── associer à un thème
                               └── uploader PDF et image
```

### Pages disponibles

| URL | Description |
|---|---|
| `/moderateur/domaines` | Liste + CRUD domaines |
| `/moderateur/rubriques` | Liste + CRUD rubriques |
| `/moderateur/themes` | Liste + CRUD thèmes |
| `/moderateur/protocoles` | Liste + CRUD protocoles (upload PDF/image) |

---

## ROLE_ADMIN — Administrateur

**Hérite de toutes les pages ROLE_MODERATEUR, plus la gestion des utilisateurs et des demandes.**

### Parcours type : traiter une demande d'inscription

```
/admin/demandes ──► tableau "À traiter"
                        │
                    clique sur une demande
                        │
                ┌───────┴───────┐
            Approuver         Refuser
                │                 └── saisit un motif
                │                         │
         User créé               demande refusée
         email envoyé            email envoyé
```

Voir [inscription.md](inscription.md) pour le détail complet du workflow.

### Parcours type : gérer les utilisateurs

```
/admin/utilisateurs ──► liste de tous les comptes
                             │
                    ┌────────┴────────┐
                modifier            supprimer
                    │
            peut changer :
            - prénom / nom
            - email
            - profession
            - rôle (Utilisateur / Modérateur / Admin)
```

> Un admin ne peut pas supprimer son propre compte.

### Parcours type : gérer les professions

```
/admin/professions ──► liste des professions
                            │
                   ajouter / modifier / supprimer
```

Les professions sont proposées dans le formulaire d'inscription et associées aux comptes utilisateurs.

### Pages disponibles

| URL | Description |
|---|---|
| `/admin/demandes` | Demandes d'inscription à traiter |
| `/admin/demandes/{id}/approuver` | Approuver une demande |
| `/admin/demandes/{id}/refuser` | Refuser une demande (motif obligatoire) |
| `/admin/utilisateurs` | Liste + CRUD utilisateurs |
| `/admin/professions` | Liste + CRUD professions |

---

## Récapitulatif des accès par rôle

| Zone | Visiteur | ROLE_USER | ROLE_MODERATEUR | ROLE_ADMIN |
|---|:---:|:---:|:---:|:---:|
| Accueil `/` | ✅ | ✅ | ✅ | ✅ |
| Inscription `/inscription` | ✅ | — | — | — |
| Profil `/profil` | — | ✅ | ✅ | ✅ |
| Navigation publique `/domaines` | — | ✅ | ✅ | ✅ |
| CRUD contenu `/moderateur/*` | — | — | ✅ | ✅ |
| Gestion utilisateurs `/admin/utilisateurs` | — | — | — | ✅ |
| Gestion professions `/admin/professions` | — | — | — | ✅ |
| Demandes d'inscription `/admin/demandes` | — | — | — | ✅ |
