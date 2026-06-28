<?php

namespace App\DataFixtures;

use App\Entity\Profession;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

// Charge les 25 professions médicales disponibles à l'inscription.
// Aucune dépendance : cette fixture s'exécute en premier.
class ProfessionFixtures extends Fixture
{
    private const PROFESSIONS = [
        ['nom' => 'Médecin généraliste',         'slug' => 'medecin-generaliste'],
        ['nom' => 'Infirmier(ière)',              'slug' => 'infirmier'],
        ['nom' => 'Aide-soignant(e)',             'slug' => 'aide-soignant'],
        ['nom' => 'Kinésithérapeute',             'slug' => 'kinesitherapeute'],
        ['nom' => 'Pharmacien(ne)',               'slug' => 'pharmacien'],
        ['nom' => 'Chirurgien(ne)',               'slug' => 'chirurgien'],
        ['nom' => 'Cardiologue',                  'slug' => 'cardiologue'],
        ['nom' => 'Pédiatre',                     'slug' => 'pediatre'],
        ['nom' => 'Gynécologue',                  'slug' => 'gynecologue'],
        ['nom' => 'Anesthésiste-réanimateur',     'slug' => 'anesthesiste-reanimateur'],
        ['nom' => 'Radiologue',                   'slug' => 'radiologue'],
        ['nom' => 'Dermatologue',                 'slug' => 'dermatologue'],
        ['nom' => 'Ophtalmologue',                'slug' => 'ophtalmologue'],
        ['nom' => 'Psychiatre',                   'slug' => 'psychiatre'],
        ['nom' => 'Urgentiste',                   'slug' => 'urgentiste'],
        ['nom' => 'Orthopédiste',                 'slug' => 'orthopediste'],
        ['nom' => 'Pneumologue',                  'slug' => 'pneumologue'],
        ['nom' => 'Gastro-entérologue',           'slug' => 'gastro-enterologue'],
        ['nom' => 'Endocrinologue',               'slug' => 'endocrinologue'],
        ['nom' => 'Rhumatologue',                 'slug' => 'rhumatologue'],
        ['nom' => 'Néphrologue',                  'slug' => 'nephrologue'],
        ['nom' => 'Oncologue',                    'slug' => 'oncologue'],
        ['nom' => 'Hématologue',                  'slug' => 'hematologue'],
        ['nom' => 'Neurologue',                   'slug' => 'neurologue'],
        ['nom' => 'Sage-femme',                   'slug' => 'sage-femme'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::PROFESSIONS as $data) {
            $profession = new Profession();
            $profession->setNom($data['nom']);
            $profession->setSlug($data['slug']);
            $manager->persist($profession);

            // addReference() stocke l'objet sous une clé nommée pour que d'autres fixtures
            // puissent le récupérer avec getReference() sans refaire de requête SQL.
            $this->addReference('profession-'.$data['slug'], $profession);
        }

        $manager->flush();
    }
}
