# Diagramme de classes — lereperedesprotocoles-v2

Diagramme des 7 entités Doctrine (`src/Entity/`) et de leurs relations. Généré à partir du code source le 2026-07-01.

Les getters/setters triviaux ne sont pas listés (un par attribut, aucune logique) — seules les méthodes métier apparaissent.

```mermaid
classDiagram
    class User {
        <<Entity>>
        -int id
        -string email
        -string[] roles
        -string password
        -string prenom
        -string nom
        -bool isVerified
        -DateTimeImmutable createdAt
        +getUserIdentifier() string
        +getRoles() string[]
        +eraseCredentials() void
    }

    class DemandeInscription {
        <<Entity>>
        -int id
        -string email
        -string prenom
        -string nom
        -string password
        -string statut
        -string token
        -DateTimeImmutable tokenExpiresAt
        -string motifRejet
        -DateTimeImmutable createdAt
        -DateTimeImmutable traiteeAt
        -bool emailVerifie
        +isTokenValide() bool
    }

    class Profession {
        <<Entity>>
        -int id
        -string nom
        -string slug
    }

    class Domaine {
        <<Entity>>
        -int id
        -string nom
        -string slug
        -string description
        +addRubrique(Rubrique) static
        +removeRubrique(Rubrique) static
    }

    class Rubrique {
        <<Entity>>
        -int id
        -string nom
        -string slug
        -string description
        +addDomaine(Domaine) static
        +removeDomaine(Domaine) static
        +addTheme(Theme) static
        +removeTheme(Theme) static
    }

    class Theme {
        <<Entity>>
        -int id
        -string nom
        -string slug
        +addProtocole(Protocole) static
        +removeProtocole(Protocole) static
    }

    class Protocole {
        <<Entity>>
        -int id
        -string titre
        -string slug
        -string description
        -File pdfFile
        -string pdfFilename
        -File imageFile
        -string imageFilename
        -DateTimeImmutable createdAt
        -DateTimeImmutable updatedAt
        +onPreUpdate() void
    }

    User "*" --> "1" Profession : profession
    DemandeInscription "*" --> "1" Profession : profession
    DemandeInscription "0..1" --> "0..1" User : utilisateur
    Rubrique "*" --> "*" Domaine : domaines
    Theme "*" --> "1" Rubrique : rubrique
    Protocole "*" --> "1" Theme : theme
```

## Notes de lecture

- **User → Profession** et **DemandeInscription → Profession** : `ManyToOne` non nullable, un référentiel partagé (voir [[project_lerepere_v2]]).
- **DemandeInscription → User** : `OneToOne` nullable, renseigné uniquement à l'approbation d'une demande (`onDelete: SET NULL`).
- **Rubrique ↔ Domaine** : `ManyToMany`, table de liaison `rubrique_domaine`. `Rubrique` est le côté propriétaire (porte `inversedBy`/`JoinTable`), `Domaine` est le côté inverse (`mappedBy`).
- **Theme → Rubrique** et **Protocole → Theme** : `ManyToOne` non nullable avec `cascade: ['persist', 'remove']` côté `OneToMany` — supprimer une `Rubrique` supprime en cascade ses `Theme` puis leurs `Protocole`.
- Hiérarchie de navigation complète : `Domaine ↔ Rubrique → Theme → Protocole`.

Pour le détail champ par champ (types SQL, index, contraintes), voir `docs/entites.md`.
