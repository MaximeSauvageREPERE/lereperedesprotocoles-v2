<?php

namespace App\DataFixtures;

use App\Entity\Domaine;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DomaineFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $domaines = [
            [
                'nom'         => 'Cardiologie',
                'slug'        => 'cardiologie',
                'description' => 'Protocoles relatifs aux pathologies et soins cardiovasculaires.',
            ],
            [
                'nom'         => 'Neurologie',
                'slug'        => 'neurologie',
                'description' => 'Protocoles relatifs aux pathologies et soins neurologiques.',
            ],
            [
                'nom'         => 'Urgences',
                'slug'        => 'urgences',
                'description' => 'Protocoles de prise en charge des situations d\'urgence.',
            ],
        ];

        foreach ($domaines as $data) {
            $domaine = new Domaine();
            $domaine->setNom($data['nom']);
            $domaine->setSlug($data['slug']);
            $domaine->setDescription($data['description']);
            $manager->persist($domaine);
            $this->addReference('domaine-' . $data['slug'], $domaine);
        }

        $manager->flush();
    }
}
