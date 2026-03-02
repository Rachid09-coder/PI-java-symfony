<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260223120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add course_progress table for student course progress tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE course_progress (id INT AUTO_INCREMENT NOT NULL, student_id INT NOT NULL, course_id INT NOT NULL, progress_percent SMALLINT DEFAULT 0 NOT NULL, completed_at DATETIME DEFAULT NULL, last_accessed_at DATETIME NOT NULL, started_at DATETIME NOT NULL, UNIQUE INDEX student_course_unique (student_id, course_id), INDEX IDX_A1F3C44BCB944F1A (student_id), INDEX IDX_A1F3C44B591CC992 (course_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE course_progress ADD CONSTRAINT FK_A1F3C44BCB944F1A FOREIGN KEY (student_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE course_progress ADD CONSTRAINT FK_A1F3C44B591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE course_progress DROP FOREIGN KEY FK_A1F3C44BCB944F1A');
        $this->addSql('ALTER TABLE course_progress DROP FOREIGN KEY FK_A1F3C44B591CC992');
        $this->addSql('DROP TABLE course_progress');
    }
}
