<?php

namespace App\DataFixtures;

use App\Entity\Metier;
use App\Entity\MetierAvance;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create Metiers
        $metiers = [];
        $metierNames = ['Informatique', 'Management', 'Design'];

        foreach ($metierNames as $name) {
            $metier = new Metier();
            $metier->setNom($name);
            $metier->setDescription('Description pour ' . $name);
            $manager->persist($metier);
            $metiers[$name] = $metier;
        }

        // Create MetierAvances linked to Metiers
        $metierAvanceData = [
            'Développeur Web' => 'Informatique',
            'Chef de Projet' => 'Management',
            'Graphiste' => 'Design',
        ];

        foreach ($metierAvanceData as $avanceName => $metierName) {
            $metierAvance = new MetierAvance();
            $metierAvance->setNom($avanceName);
            $metierAvance->setDescription('Description avancée pour ' . $avanceName);
            $metierAvance->setMetier($metiers[$metierName]);
            $manager->persist($metierAvance);
        }

        $manager->flush();
    }
}
