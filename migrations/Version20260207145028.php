<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207145028 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE exam_submission (id INT AUTO_INCREMENT NOT NULL, file_path VARCHAR(255) DEFAULT NULL, grade DOUBLE PRECISION DEFAULT NULL, is_passed TINYINT DEFAULT NULL, submitted_at VARCHAR(255) NOT NULL, student_id INT NOT NULL, exam_id INT NOT NULL, INDEX IDX_C90D20A6CB944F1A (student_id), INDEX IDX_C90D20A6578D5E91 (exam_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE exam_submission ADD CONSTRAINT FK_C90D20A6CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE exam_submission ADD CONSTRAINT FK_C90D20A6578D5E91 FOREIGN KEY (exam_id) REFERENCES exam (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exam_submission DROP FOREIGN KEY FK_C90D20A6CB944F1A');
        $this->addSql('ALTER TABLE exam_submission DROP FOREIGN KEY FK_C90D20A6578D5E91');
        $this->addSql('DROP TABLE exam_submission');
    }
}
