<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

// Fixture de base générée par Symfony — non utilisée dans ce projet.
// Toutes les données de test sont dans des fixtures dédiées par entité
// (ProfessionFixtures, UserFixtures, DomaineFixtures, etc.).
class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $manager->flush();
    }
}
