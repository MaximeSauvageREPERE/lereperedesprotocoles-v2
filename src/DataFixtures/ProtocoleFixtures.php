<?php

namespace App\DataFixtures;

use App\Entity\Protocole;
use App\Entity\Theme;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProtocoleFixtures extends Fixture implements DependentFixtureInterface
{
    private const PROTOCOLES = [
        ['titre' => 'Protocole ECG 12 dérivations',              'slug' => 'protocole-ecg-12-derivations',              'theme' => 'ecg-monitoring-cardiaque'],
        ['titre' => 'Prise en charge de la douleur thoracique',  'slug' => 'prise-en-charge-douleur-thoracique',        'theme' => 'prise-en-charge-douleur'],
        ['titre' => 'Monitoring continu du patient hospitalisé', 'slug' => 'monitoring-continu-patient-hospitalise',    'theme' => 'surveillance-constantes'],
        ['titre' => 'Arrêt cardio-respiratoire — RCP adulte',    'slug' => 'arret-cardio-respiratoire-rcp-adulte',      'theme' => 'arret-cardio-respiratoire'],
        ['titre' => 'Prise en charge de l\'AVC ischémique',      'slug' => 'prise-en-charge-avc-ischemique',            'theme' => 'avc-troubles-neurologiques'],
        ['titre' => 'Pose de voie veineuse périphérique',        'slug' => 'pose-voie-veineuse-peripherique',           'theme' => 'surveillance-constantes'],
        ['titre' => 'Administration d\'un traitement IV',        'slug' => 'administration-traitement-iv',              'theme' => 'surveillance-constantes'],
        ['titre' => 'Pansement simple et pansement complexe',    'slug' => 'pansement-simple-complexe',                 'theme' => 'prise-en-charge-douleur'],
        ['titre' => 'Préparation et injection d\'insuline',      'slug' => 'preparation-injection-insuline',            'theme' => 'surveillance-constantes'],
        ['titre' => 'Sondage urinaire chez l\'adulte',           'slug' => 'sondage-urinaire-adulte',                   'theme' => 'prise-en-charge-douleur'],
        ['titre' => 'Oxygénothérapie et saturation SpO2',        'slug' => 'oxygenotherapie-saturation-spo2',           'theme' => 'surveillance-constantes'],
        ['titre' => 'Surveillance de la glycémie capillaire',    'slug' => 'surveillance-glycemie-capillaire',          'theme' => 'surveillance-constantes'],
        ['titre' => 'Mesure de la pression artérielle',          'slug' => 'mesure-pression-arterielle',                'theme' => 'surveillance-constantes'],
        ['titre' => 'Score GCS — évaluation de la conscience',   'slug' => 'score-gcs-evaluation-conscience',           'theme' => 'surveillance-constantes'],
        ['titre' => 'Holter ECG — pose et dépose',               'slug' => 'holter-ecg-pose-depose',                    'theme' => 'ecg-monitoring-cardiaque'],
        ['titre' => 'Interprétation de l\'ECG de repos',         'slug' => 'interpretation-ecg-repos',                  'theme' => 'ecg-monitoring-cardiaque'],
        ['titre' => 'Épreuve d\'effort — protocole infirmier',   'slug' => 'epreuve-effort-protocole-infirmier',        'theme' => 'ecg-monitoring-cardiaque'],
        ['titre' => 'Antalgie par paliers — évaluation EVA',     'slug' => 'antalgie-paliers-evaluation-eva',           'theme' => 'prise-en-charge-douleur'],
        ['titre' => 'PCA — analgésie contrôlée par le patient',  'slug' => 'pca-analgesie-controlee-patient',           'theme' => 'prise-en-charge-douleur'],
        ['titre' => 'Bloc nerveux régional — aide à la pose',    'slug' => 'bloc-nerveux-regional-aide-pose',           'theme' => 'prise-en-charge-douleur'],
        ['titre' => 'RCP pédiatrique — nourrisson et enfant',    'slug' => 'rcp-pediatrique-nourrisson-enfant',         'theme' => 'arret-cardio-respiratoire'],
        ['titre' => 'Défibrillation externe automatisée (DEA)',  'slug' => 'defibrillation-externe-automatisee',        'theme' => 'arret-cardio-respiratoire'],
        ['titre' => 'Intubation orotrachéale en urgence',        'slug' => 'intubation-orotracheale-urgence',           'theme' => 'arret-cardio-respiratoire'],
        ['titre' => 'Thrombolyse dans l\'AVC ischémique',        'slug' => 'thrombolyse-avc-ischemique',                'theme' => 'avc-troubles-neurologiques'],
        ['titre' => 'Prise en charge d\'une crise d\'épilepsie', 'slug' => 'prise-en-charge-crise-epilepsie',           'theme' => 'avc-troubles-neurologiques'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::PROTOCOLES as $data) {
            $protocole = new Protocole();
            $protocole->setTitre($data['titre']);
            $protocole->setSlug($data['slug']);
            $protocole->setTheme($this->getReference('theme-'.$data['theme'], Theme::class));
            $manager->persist($protocole);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [ThemeFixtures::class];
    }
}
