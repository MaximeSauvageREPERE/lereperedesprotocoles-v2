# Entités Doctrine — lereperedesprotocoles-v2

## Vue d'ensemble

Le modèle de données est organisé autour de deux axes :

**Accès utilisateurs**
```
Profession ──(OneToMany)──► User
Profession ──(OneToMany)──► DemandeInscription
```

**Hiérarchie du contenu médical**
```
Domaine ↔(ManyToMany)↔ Rubrique ──(OneToMany)──► Thème ──(OneToMany)──► Protocole
```

---

## Les entités

### `User` — `src/Entity/User.php`

Représente un utilisateur authentifié. Généré par le MakerBundle Symfony, il implémente deux interfaces obligatoires pour le système de sécurité :

- `UserInterface` — fournit `getUserIdentifier()` (retourne l'email), `getRoles()`, `eraseCredentials()`
- `PasswordAuthenticatedUserInterface` — fournit `getPassword()`

| Champ | Type SQL | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT | Clé primaire |
| `email` | VARCHAR(180) UNIQUE | Identifiant de connexion |
| `roles` | JSON | Tableau de rôles, `ROLE_USER` toujours ajouté automatiquement |
| `password` | VARCHAR | Mot de passe haché (bcrypt) |
| `prenom` | VARCHAR(100) | — |
| `nom` | VARCHAR(100) | — |
| `profession_id` | INT NOT NULL | FK → `profession` |
| `is_verified` | BOOLEAN | L'email a été vérifié |
| `created_at` | DATETIME | Initialisé dans `__construct()` |

**Annotation spéciale :**
```php
#[UniqueEntity(fields: ['email'], message: 'Un compte avec cet email existe déjà.')]
```
Cette contrainte Symfony vérifie l'unicité de l'email au niveau du formulaire (avant même d'envoyer en base). Elle s'ajoute à la contrainte `UNIQUE` en base de données.

---

### `DemandeInscription` — `src/Entity/DemandeInscription.php`

Représente une demande d'accès soumise par un futur utilisateur. Elle suit un workflow en trois états :

```
en_attente → approuvee  (l'admin crée un User et le lie via OneToOne)
           → refusee    (l'admin renseigne un motifRejet)
```

| Champ | Type SQL | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT | — |
| `email` | VARCHAR(180) | — |
| `prenom` | VARCHAR(100) | — |
| `nom` | VARCHAR(100) | — |
| `profession_id` | INT NOT NULL | FK → `profession` |
| `password` | VARCHAR | Haché dès la soumission, copié vers `User` à l'approbation |
| `statut` | VARCHAR(20) | `en_attente` / `approuvee` / `refusee` |
| `token` | VARCHAR(100) nullable | Token de vérification d'email, à usage unique |
| `token_expires_at` | DATETIME nullable | Expiration du token (ex : +24h) |
| `motif_rejet` | TEXT nullable | Renseigné par l'admin si refus |
| `created_at` | DATETIME | — |
| `traitee_at` | DATETIME nullable | Date de décision admin |
| `utilisateur_id` | INT nullable | FK OneToOne → `user` (créé après approbation) |

**Méthode utilitaire :**
```php
public function isTokenValide(): bool
{
    return $this->tokenExpiresAt !== null && $this->tokenExpiresAt > new \DateTimeImmutable();
}
```

---

### `Profession` — `src/Entity/Profession.php`

Liste des professions médicales, gérée par l'admin (ticket #19). Remplace le champ texte libre présent dans la v1.

| Champ | Type SQL | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT | — |
| `nom` | VARCHAR(150) | Ex : "Médecin généraliste" |
| `slug` | VARCHAR(150) UNIQUE | Ex : "medecin-generaliste" (pour les URLs) |

**Pourquoi une entité dédiée ?**  
Dans la v1, `User.profession` était un `VARCHAR` libre. Le problème : les utilisateurs tapaient "cardiologue", "Cardiologue", "cardiologiste"... rendant toute statistique ou filtre impossible. Avec une entité dédiée, la liste est contrôlée par l'admin et les formulaires affichent une liste déroulante.

---

### `Domaine` — `src/Entity/Domaine.php`

Domaine médical de premier niveau (ex : Cardiologie, Pneumologie).

| Champ | Type SQL | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT | — |
| `nom` | VARCHAR(255) | — |
| `slug` | VARCHAR(255) UNIQUE | Pour les URLs `/domaine/{slug}` |
| `description` | TEXT nullable | — |

Relation vers `Rubrique` : **ManyToMany** (un domaine contient plusieurs rubriques, une rubrique peut appartenir à plusieurs domaines).

---

### `Rubrique` — `src/Entity/Rubrique.php`

Regroupement thématique rattaché à un ou plusieurs domaines.

| Champ | Type SQL | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT | — |
| `nom` | VARCHAR(255) | — |
| `slug` | VARCHAR(255) UNIQUE | — |
| `description` | TEXT nullable | — |

**Table de liaison** : la relation ManyToMany avec `Domaine` génère une table intermédiaire `rubrique_domaine(rubrique_id, domaine_id)`.

---

### `Theme` — `src/Entity/Theme.php`

Sous-catégorie d'une rubrique. Contient des protocoles.

| Champ | Type SQL | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT | — |
| `nom` | VARCHAR(255) | — |
| `slug` | VARCHAR(255) UNIQUE | — |
| `rubrique_id` | INT NOT NULL | FK → `rubrique` |

---

### `Protocole` — `src/Entity/Protocole.php`

L'entité centrale : un protocole médical avec son PDF.

| Champ | Type SQL | Notes |
|---|---|---|
| `id` | INT AUTO_INCREMENT | — |
| `titre` | VARCHAR(255) | Nom du protocole |
| `slug` | VARCHAR(255) UNIQUE | — |
| `description` | TEXT nullable | — |
| `pdf_filename` | VARCHAR(255) nullable | Nom du fichier PDF stocké |
| `image_filename` | VARCHAR(255) nullable | Image de couverture |
| `theme_id` | INT NOT NULL | FK → `theme` |
| `created_at` | DATETIME | — |
| `updated_at` | DATETIME | Mis à jour automatiquement |

**Lifecycle callback :**
```php
#[ORM\HasLifecycleCallbacks]
class Protocole
{
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
```
`#[ORM\HasLifecycleCallbacks]` sur la classe + `#[ORM\PreUpdate]` sur la méthode : Doctrine appelle automatiquement `onPreUpdate()` avant chaque `UPDATE` en base, sans avoir à y penser dans les controllers.

---

## Les relations Doctrine

### ManyToOne / OneToMany

La relation la plus courante. Exemple : un `Protocole` appartient à un `Theme`, un `Theme` a plusieurs `Protocoles`.

```
Protocole                          Theme
─────────                          ─────
theme_id  ──────────────────────►  id
(colonne FK)
```

**Côté "Many" (Protocole) :**
```php
#[ORM\ManyToOne(inversedBy: 'protocoles')]
#[ORM\JoinColumn(nullable: false)]
private ?Theme $theme = null;
```
- `inversedBy: 'protocoles'` : le nom de la propriété Collection dans `Theme`
- `nullable: false` : la FK en base ne peut pas être NULL (un protocole doit avoir un thème)

**Côté "One" (Theme) :**
```php
#[ORM\OneToMany(targetEntity: Protocole::class, mappedBy: 'theme', cascade: ['persist', 'remove'])]
private Collection $protocoles;
```
- `mappedBy: 'theme'` : le nom de la propriété dans `Protocole` qui porte la FK
- `cascade: ['persist', 'remove']` : si on supprime un `Theme`, ses `Protocoles` sont supprimés en cascade

**Règle :** le côté `ManyToOne` est le **propriétaire** de la relation (c'est lui qui détient la FK en base). Le côté `OneToMany` est l'**inverse** (lecture seule pour Doctrine).

---

### ManyToMany

Une `Rubrique` peut appartenir à plusieurs `Domaines`, et un `Domaine` contient plusieurs `Rubriques`.

```
rubrique_domaine
────────────────
rubrique_id  ──► rubrique.id
domaine_id   ──► domaine.id
```

**Côté propriétaire (Rubrique) :**
```php
#[ORM\ManyToMany(targetEntity: Domaine::class, inversedBy: 'rubriques')]
#[ORM\JoinTable(name: 'rubrique_domaine')]
private Collection $domaines;
```

**Côté inverse (Domaine) :**
```php
#[ORM\ManyToMany(targetEntity: Rubrique::class, mappedBy: 'domaines')]
private Collection $rubriques;
```

**Règle :** le côté propriétaire utilise `inversedBy`, l'inverse utilise `mappedBy`. C'est le propriétaire qui décide du nom de la table de liaison (`#[ORM\JoinTable]`).

---

### OneToOne

`DemandeInscription` est liée à un `User` après approbation.

```php
#[ORM\OneToOne(targetEntity: User::class)]
#[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
private ?User $utilisateur = null;
```

`onDelete: 'SET NULL'` : si le `User` est supprimé, `DemandeInscription.utilisateur_id` passe à NULL au lieu de lever une erreur de FK.

---

## Le `__construct()` obligatoire

Toute entité qui possède des collections doit les initialiser dans le constructeur :

```php
public function __construct()
{
    $this->protocoles = new ArrayCollection();
}
```

Sans ça, `$protocole->getProtocoles()->count()` lèverait une erreur car on appellerait `count()` sur `null`.

---

## Le `__toString()`

Présent sur toutes les entités "référentiel" (Profession, Domaine, Rubrique, Theme, Protocole). Il permet à Symfony Forms d'afficher automatiquement le bon libellé dans une liste déroulante (`EntityType`) sans configuration supplémentaire.

```php
public function __toString(): string
{
    return $this->nom ?? '';
}
```

---

## Workflow de migration

Après toute modification d'une entité (ajout de champ, nouvelle relation, etc.) :

```bash
# 1. Vérifier que le mapping PHP est cohérent avec la base
php bin/console doctrine:schema:validate

# 2. Générer le fichier de migration
php bin/console make:migration

# 3. Ouvrir le fichier généré dans migrations/ et vérifier le SQL
# Ajouter une description dans getDescription()

# 4. Exécuter la migration
php bin/console doctrine:migrations:migrate
```

**Ne jamais modifier la base manuellement** (ALTER TABLE à la main). Passer toujours par les migrations pour que l'historique reste cohérent entre développeurs et en production.

---

## Slug

Un slug est la version URL-friendly d'un titre :  
`"Cardiologie Pédiatrique"` → `"cardiologie-pediatrique"`

Toutes les entités naviguables (`Domaine`, `Rubrique`, `Theme`, `Protocole`, `Profession`) ont un champ `slug` marqué `unique: true`. La stratégie de génération automatique sera définie au ticket #6 (options : `cocur/slugify` appelé manuellement dans le controller, ou `stof/doctrine-extensions-bundle` pour une génération automatique).
