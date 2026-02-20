<?php

namespace App\DataFixtures;

use App\Entity\Bulletin;
use App\Entity\ReportCardLine;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixture pour créer un bulletin complet avec notes
 * Exécuter avec: php bin/console doctrine:fixtures:load --group=bulletin --append
 */
class BulletinTestFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['bulletin'];
    }

    public function load(ObjectManager $manager): void
    {
        // Trouver l'étudiant de test
        $student = $manager->getRepository(User::class)->findOneBy(['email' => 'rachid.gharbi@esprit.tn']);
        
        if (!$student) {
            echo "Étudiant non trouvé. Créez d'abord les données de test avec --group=grades\n";
            return;
        }

        // Créer le bulletin
        $bulletin = new Bulletin();
        $bulletin->setStudent($student);
        $bulletin->setAcademicYear('2025/2026');
        $bulletin->setSemester('Semestre 1');
        $bulletin->setStatus('Brouillon');
        $bulletin->setAverage(15.31);
        $bulletin->setMention('Bien');
        $bulletin->setClassRank(1);
        $bulletin->setCreatedAt(new \DateTimeImmutable());
        
        $manager->persist($bulletin);

        // Ajouter les lignes de notes
        $notes = [
            ['moduleName' => 'Mathématiques', 'noteCC' => 14, 'noteDS' => 16, 'noteExam' => 15, 'note' => 15.1, 'coefficient' => 3],
            ['moduleName' => 'Informatique', 'noteCC' => 17, 'noteDS' => 18, 'noteExam' => 17, 'note' => 17.2, 'coefficient' => 4],
            ['moduleName' => 'Physique', 'noteCC' => 11.5, 'noteDS' => 13, 'noteExam' => 12, 'note' => 12.15, 'coefficient' => 2],
            ['moduleName' => 'Anglais', 'noteCC' => 14.5, 'noteDS' => 14, 'noteExam' => 15, 'note' => 14.75, 'coefficient' => 2],
            ['moduleName' => 'Communication', 'noteCC' => 16, 'noteDS' => 15, 'noteExam' => 16, 'note' => 15.8, 'coefficient' => 1],
        ];

        foreach ($notes as $noteData) {
            $line = new ReportCardLine();
            $line->setBulletin($bulletin);
            $line->setModuleName($noteData['moduleName']);
            $line->setNoteCC($noteData['noteCC']);
            $line->setNoteDS($noteData['noteDS']);
            $line->setNoteExam($noteData['noteExam']);
            $line->setNote($noteData['note']);
            $line->setCoefficient($noteData['coefficient']);
            $line->setTeacherComment('Bon travail');
            
            $manager->persist($line);
            $bulletin->addReportCardLine($line);
        }

        $manager->flush();

        echo "\n=== BULLETIN DE TEST CRÉÉ ===\n";
        echo "ID: {$bulletin->getId()}\n";
        echo "Étudiant: {$student->getPrenom()} {$student->getName()}\n";
        echo "Email: {$student->getEmail()}\n";
        echo "Téléphone: {$student->getNumtel()}\n";
        echo "Année: {$bulletin->getAcademicYear()}\n";
        echo "Semestre: {$bulletin->getSemester()}\n";
        echo "Moyenne: {$bulletin->getAverage()}\n";
        echo "Mention: {$bulletin->getMention()}\n";
        echo "Nombre de lignes: " . count($notes) . "\n\n";
        echo "Pour tester: allez sur /admin/bulletin/{$bulletin->getId()}\n";
        echo "Puis suivez le workflow: Vérifier → Valider → Publier\n";
        echo "\n";
    }
}
