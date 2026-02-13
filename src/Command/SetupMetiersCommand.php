<?php

namespace App\Command;

use App\Entity\Metier;
use App\Entity\MetierAvance;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:setup-metiers',
    description: 'Initialise les tables Metier et MetierAvance avec des données par défaut',
)]
class SetupMetiersCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $metiersData = [
            [
                'metier' => ['nom' => 'Gestionnaire de Scolarité', 'desc' => 'Responsable de l\'édition des bulletins et du suivi des dossiers étudiants.'],
                'avance' => ['nom' => 'Responsable Pédagogique', 'desc' => 'Supervise la validation des examens et délivre les certifications finales.']
            ],
            [
                'metier' => ['nom' => 'Chargé d\'Examen', 'desc' => 'Organise les sessions d\'examens et saisit les notes pour les bulletins.'],
                'avance' => ['nom' => 'Président de Jury', 'desc' => 'Valide les délibérations et signe officiellement les procès-verbaux de certification.']
            ],
            [
                'metier' => ['nom' => 'Assistant Qualité Formation', 'desc' => 'Vérifie la conformité des bulletins avant publication.'],
                'avance' => ['nom' => 'Auditeur de Certification', 'desc' => 'S\'assure que les processus de délivrance des diplômes respectent les normes en vigueur.']
            ]
        ];

        $metierRepo = $this->entityManager->getRepository(Metier::class);
        $metierAvanceRepo = $this->entityManager->getRepository(MetierAvance::class);

        foreach ($metiersData as $data) {
            // Metier
            $existingMetier = $metierRepo->findOneBy(['nom' => $data['metier']['nom']]);
            if (!$existingMetier) {
                $metier = new Metier();
                $metier->setNom($data['metier']['nom']);
                $metier->setDescription($data['metier']['desc']);
                $this->entityManager->persist($metier);
                $io->note('Ajout du métier : ' . $data['metier']['nom']);
            }

            // MetierAvance
            $existingAvance = $metierAvanceRepo->findOneBy(['nom' => $data['avance']['nom']]);
            if (!$existingAvance) {
                $avance = new MetierAvance();
                $avance->setNom($data['avance']['nom']);
                $avance->setDescription($data['avance']['desc']);
                $this->entityManager->persist($avance);
                $io->note('Ajout du métier avancé : ' . $data['avance']['nom']);
            }
        }

        $this->entityManager->flush();

        $io->success('Les tables Metier et MetierAvance ont été initialisées avec succès !');

        return Command::SUCCESS;
    }
}
