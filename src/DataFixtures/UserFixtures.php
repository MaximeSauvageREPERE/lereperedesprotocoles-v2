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
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $users = [
            [
                'email'      => 'admin@test.fr',
                'password'   => 'administrateur',
                'prenom'     => 'Alice',
                'nom'        => 'Admin',
                'roles'      => ['ROLE_ADMIN'],
                'profession' => 'medecin-generaliste',
            ],
            [
                'email'      => 'modo@test.fr',
                'password'   => 'moderateur',
                'prenom'     => 'Marc',
                'nom'        => 'Modo',
                'roles'      => ['ROLE_MODERATEUR'],
                'profession' => 'infirmier',
            ],
            [
                'email'      => 'user@test.fr',
                'password'   => 'utilisateur',
                'prenom'     => 'Lucie',
                'nom'        => 'User',
                'roles'      => [],
                'profession' => 'aide-soignant',
            ],
        ];

        foreach ($users as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setPassword($this->hasher->hashPassword($user, $data['password']));
            $user->setPrenom($data['prenom']);
            $user->setNom($data['nom']);
            $user->setRoles($data['roles']);
            $user->setIsVerified(true);
            $user->setProfession($this->getReference('profession-' . $data['profession'], Profession::class));
            $manager->persist($user);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ProfessionFixtures::class];
    }
}
