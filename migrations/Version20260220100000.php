<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Exam anti-cheat: started_at and closed_at on exam_submission.
 */
final class Version20260220100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add started_at and closed_at to exam_submission for anti-cheat (timer + leave detection).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE exam_submission ADD started_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD closed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('UPDATE exam_submission SET started_at = submitted_at WHERE started_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE exam_submission DROP started_at, DROP closed_at');
    }
}
