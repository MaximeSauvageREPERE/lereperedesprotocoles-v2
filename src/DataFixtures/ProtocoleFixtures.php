<?php

namespace App\DataFixtures;

use App\Entity\Protocole;
use App\Entity\Theme;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProtocoleFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $protocoles = [
            [
                'titre'       => 'Protocole ECG 12 dérivations',
                'slug'        => 'protocole-ecg-12-derivations',
                'description' => 'Procédure standardisée pour la réalisation d\'un ECG 12 dérivations.',
                'theme'       => 'ecg-monitoring-cardiaque',
            ],
            [
                'titre'       => 'Prise en charge de la douleur thoracique',
                'slug'        => 'prise-en-charge-douleur-thoracique',
                'description' => 'Protocole d\'évaluation et de traitement de la douleur thoracique aiguë.',
                'theme'       => 'prise-en-charge-douleur',
            ],
            [
                'titre'       => 'Monitoring continu du patient hospitalisé',
                'slug'        => 'monitoring-continu-patient-hospitalise',
                'description' => 'Protocole de surveillance continue des constantes vitales.',
                'theme'       => 'surveillance-constantes',
            ],
            [
                'titre'       => 'Arrêt cardio-respiratoire — RCP adulte',
                'slug'        => 'arret-cardio-respiratoire-rcp-adulte',
                'description' => 'Protocole de réanimation cardio-pulmonaire chez l\'adulte.',
                'theme'       => 'arret-cardio-respiratoire',
            ],
            [
                'titre'       => 'Prise en charge de l\'AVC ischémique',
                'slug'        => 'prise-en-charge-avc-ischemique',
                'description' => 'Protocole de prise en charge en urgence de l\'AVC ischémique.',
                'theme'       => 'avc-troubles-neurologiques',
            ],
        ];

        foreach ($protocoles as $data) {
            $protocole = new Protocole();
            $protocole->setTitre($data['titre']);
            $protocole->setSlug($data['slug']);
            $protocole->setDescription($data['description']);
            $protocole->setTheme($this->getReference('theme-' . $data['theme'], Theme::class));
            $manager->persist($protocole);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ThemeFixtures::class];
    }
}
