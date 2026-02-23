<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crée la table report_card_line si manquante (colonnes note_cc, note_ds, note_exam incluses).
 */
final class Version20260223120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create report_card_line table if missing';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('report_card_line')) {
            return;
        }

        $this->addSql('CREATE TABLE report_card_line (
            id INT AUTO_INCREMENT NOT NULL,
            bulletin_id INT NOT NULL,
            grade_id INT DEFAULT NULL,
            module_name VARCHAR(100) NOT NULL,
            note_cc DOUBLE PRECISION DEFAULT NULL,
            note_ds DOUBLE PRECISION DEFAULT NULL,
            note_exam DOUBLE PRECISION DEFAULT NULL,
            note DOUBLE PRECISION NOT NULL,
            coefficient DOUBLE PRECISION NOT NULL,
            teacher_comment LONGTEXT DEFAULT NULL,
            INDEX IDX_rcl_bulletin (bulletin_id),
            INDEX IDX_rcl_grade (grade_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        $this->addSql('ALTER TABLE report_card_line ADD CONSTRAINT FK_rcl_bulletin FOREIGN KEY (bulletin_id) REFERENCES bulletin (id) ON DELETE CASCADE');
        if ($schema->hasTable('grade')) {
            $this->addSql('ALTER TABLE report_card_line ADD CONSTRAINT FK_rcl_grade FOREIGN KEY (grade_id) REFERENCES grade (id)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE report_card_line DROP FOREIGN KEY FK_rcl_bulletin');
        if ($schema->hasTable('grade')) {
            $this->addSql('ALTER TABLE report_card_line DROP FOREIGN KEY FK_rcl_grade');
        }
        $this->addSql('DROP TABLE IF EXISTS report_card_line');
    }
}
