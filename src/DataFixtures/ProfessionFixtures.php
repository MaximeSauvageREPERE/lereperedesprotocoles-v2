<?php

namespace App\DataFixtures;

use App\Entity\Profession;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProfessionFixtures extends Fixture
{
    private const PROFESSIONS = [
        ['nom' => 'Médecin généraliste',  'slug' => 'medecin-generaliste'],
        ['nom' => 'Infirmier(ière)',       'slug' => 'infirmier'],
        ['nom' => 'Aide-soignant(e)',      'slug' => 'aide-soignant'],
        ['nom' => 'Kinésithérapeute',      'slug' => 'kinesitherapeute'],
        ['nom' => 'Pharmacien(ne)',        'slug' => 'pharmacien'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::PROFESSIONS as $data) {
            $profession = new Profession();
            $profession->setNom($data['nom']);
            $profession->setSlug($data['slug']);
            $manager->persist($profession);
            $this->addReference('profession-'.$data['slug'], $profession);
        }

        $manager->flush();
    }
}
