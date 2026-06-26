<?php

namespace App\DataFixtures;

use App\Entity\Profession;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
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
            $user->setProfession($this->getReference('profession-'.$data['profession'], Profession::class));
            $manager->persist($user);
        }

        $professionSlugs = ['medecin-generaliste', 'infirmier', 'aide-soignant', 'kinesitherapeute', 'pharmacien'];
        $prenoms = ['Jean', 'Marie', 'Pierre', 'Sophie', 'Luc', 'Camille', 'Antoine', 'Claire', 'Thomas', 'Emma',
                    'Nicolas', 'Julie', 'David', 'Céline', 'Julien', 'Laure', 'François', 'Nathalie', 'Romain', 'Aurélie',
                    'Maxime', 'Sandrine'];
        $noms = ['Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy', 'Moreau',
                 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand', 'Roux', 'Vincent', 'Fournier',
                 'Morel', 'Girard'];

        $hashedPassword = $this->hasher->hashPassword(new User(), 'utilisateur');

        for ($i = 0; $i < 22; ++$i) {
            $user = new User();
            $user->setEmail('testuser'.($i + 1).'@test.fr');
            $user->setPassword($hashedPassword);
            $user->setPrenom($prenoms[$i]);
            $user->setNom($noms[$i]);
            $user->setRoles([]);
            $user->setIsVerified(true);
            $user->setProfession($this->getReference('profession-'.$professionSlugs[$i % 5], Profession::class));
            $manager->persist($user);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ProfessionFixtures::class];
    }
}
