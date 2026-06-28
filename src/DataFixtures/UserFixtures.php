<?php

namespace App\DataFixtures;

use App\Entity\Profession;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

// Crée 25 utilisateurs de test : 3 comptes nommés (admin/modo/user) + 22 utilisateurs génériques.
// Dépend de ProfessionFixtures pour assigner une profession à chaque utilisateur.
class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Comptes de test avec des rôles spécifiques — identifiants documentés dans docs/installation.md.
        $named = [
            ['email' => 'admin@test.fr', 'password' => 'administrateur', 'prenom' => 'Alice',  'nom' => 'Admin', 'roles' => ['ROLE_ADMIN'],      'profession' => 'medecin-generaliste'],
            ['email' => 'modo@test.fr',  'password' => 'moderateur',     'prenom' => 'Marc',   'nom' => 'Modo',  'roles' => ['ROLE_MODERATEUR'], 'profession' => 'infirmier'],
            ['email' => 'user@test.fr',  'password' => 'utilisateur',    'prenom' => 'Lucie',  'nom' => 'User',  'roles' => [],                  'profession' => 'aide-soignant'],
        ];

        foreach ($named as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setPassword($this->hasher->hashPassword($user, $data['password']));
            $user->setPrenom($data['prenom']);
            $user->setNom($data['nom']);
            $user->setRoles($data['roles']);
            $user->setIsVerified(true);
            // getReference() récupère l'objet Profession créé par ProfessionFixtures sans requête SQL.
            $user->setProfession($this->getReference('profession-'.$data['profession'], Profession::class));
            $manager->persist($user);
        }

        // Pool de prénoms et noms pour générer des utilisateurs réalistes (pagination, recherche, etc.)
        $professionSlugs = ['medecin-generaliste', 'infirmier', 'aide-soignant', 'kinesitherapeute', 'pharmacien'];
        $prenoms = [
            'Jean', 'Marie', 'Pierre', 'Sophie', 'Luc', 'Camille', 'Antoine', 'Claire', 'Thomas', 'Emma',
            'Nicolas', 'Julie', 'David', 'Céline', 'Julien', 'Laure', 'François', 'Nathalie', 'Romain', 'Aurélie',
            'Maxime', 'Sandrine',
        ];
        $noms = [
            'Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy', 'Moreau',
            'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand', 'Roux', 'Vincent', 'Fournier',
            'Morel', 'Girard',
        ];

        // Le hash est calculé une seule fois et réutilisé pour les 22 utilisateurs génériques —
        // hashPassword() est coûteux (bcrypt), l'appeler 22 fois ralentirait inutilement le chargement.
        $hashedPassword = $this->hasher->hashPassword(new User(), 'utilisateur');

        for ($i = 0; $i < 22; ++$i) {
            $user = new User();
            $user->setEmail('testuser'.($i + 1).'@test.fr');
            $user->setPassword($hashedPassword);
            $user->setPrenom($prenoms[$i]);
            $user->setNom($noms[$i]);
            $user->setRoles([]);
            $user->setIsVerified(true);
            // Rotation sur 5 professions via modulo pour varier les données.
            $user->setProfession($this->getReference('profession-'.$professionSlugs[$i % 5], Profession::class));
            $manager->persist($user);
        }

        $manager->flush();
    }

    // Déclare que ProfessionFixtures doit être chargée avant cette fixture.
    // Doctrine respecte cet ordre automatiquement lors de doctrine:fixtures:load.
    public function getDependencies(): array
    {
        return [ProfessionFixtures::class];
    }
}
