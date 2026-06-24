<?php

namespace App\DataFixtures;

use App\Entity\Domaine;
use App\Entity\Rubrique;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class RubriqueFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $rubriques = [
            [
                'nom'         => 'Soins courants',
                'slug'        => 'soins-courants',
                'description' => 'Protocoles de soins quotidiens et de surveillance.',
                'domaines'    => ['cardiologie', 'urgences'],
            ],
            [
                'nom'         => 'Examens complémentaires',
                'slug'        => 'examens-complementaires',
                'description' => 'Protocoles de réalisation et d\'interprétation des examens.',
                'domaines'    => ['cardiologie', 'neurologie'],
            ],
            [
                'nom'         => 'Procédures d\'urgence',
                'slug'        => 'procedures-urgence',
                'description' => 'Protocoles de prise en charge en situation d\'urgence.',
                'domaines'    => ['urgences', 'neurologie'],
            ],
        ];

        foreach ($rubriques as $data) {
            $rubrique = new Rubrique();
            $rubrique->setNom($data['nom']);
            $rubrique->setSlug($data['slug']);
            $rubrique->setDescription($data['description']);
            foreach ($data['domaines'] as $slug) {
                $rubrique->addDomaine($this->getReference('domaine-' . $slug, Domaine::class));
            }
            $manager->persist($rubrique);
            $this->addReference('rubrique-' . $data['slug'], $rubrique);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [DomaineFixtures::class];
    }
}
