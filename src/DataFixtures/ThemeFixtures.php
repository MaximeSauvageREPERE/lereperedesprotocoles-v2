<?php

namespace App\DataFixtures;

use App\Entity\Rubrique;
use App\Entity\Theme;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ThemeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $themes = [
            [
                'nom' => 'Prise en charge de la douleur',
                'slug' => 'prise-en-charge-douleur',
                'rubrique' => 'soins-courants',
            ],
            [
                'nom' => 'ECG et monitoring cardiaque',
                'slug' => 'ecg-monitoring-cardiaque',
                'rubrique' => 'examens-complementaires',
            ],
            [
                'nom' => 'Surveillance des constantes',
                'slug' => 'surveillance-constantes',
                'rubrique' => 'soins-courants',
            ],
            [
                'nom' => 'Arrêt cardio-respiratoire',
                'slug' => 'arret-cardio-respiratoire',
                'rubrique' => 'procedures-urgence',
            ],
            [
                'nom' => 'AVC et troubles neurologiques',
                'slug' => 'avc-troubles-neurologiques',
                'rubrique' => 'procedures-urgence',
            ],
        ];

        foreach ($themes as $data) {
            $theme = new Theme();
            $theme->setNom($data['nom']);
            $theme->setSlug($data['slug']);
            $theme->setRubrique($this->getReference('rubrique-'.$data['rubrique'], Rubrique::class));
            $manager->persist($theme);
            $this->addReference('theme-'.$data['slug'], $theme);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [RubriqueFixtures::class];
    }
}
