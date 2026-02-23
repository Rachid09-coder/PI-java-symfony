<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209035248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE choice (id INT AUTO_INCREMENT NOT NULL, text VARCHAR(255) NOT NULL, is_correct TINYINT NOT NULL, question_id INT NOT NULL, INDEX IDX_C1AB5A921E27F6BF (question_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, text LONGTEXT NOT NULL, points DOUBLE PRECISION NOT NULL, exam_id INT NOT NULL, INDEX IDX_B6F7494E578D5E91 (exam_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE choice ADD CONSTRAINT FK_C1AB5A921E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E578D5E91 FOREIGN KEY (exam_id) REFERENCES exam (id)');
        $this->addSql('ALTER TABLE bulletin ADD student_id INT NOT NULL, DROP student_name');
        $this->addSql('ALTER TABLE bulletin ADD CONSTRAINT FK_2B7D8942CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_2B7D8942CB944F1A ON bulletin (student_id)');
        $this->addSql('ALTER TABLE certification ADD student_id INT NOT NULL, DROP student_name');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT FK_6C3C6D75CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6C3C6D75CB944F1A ON certification (student_id)');
        $this->addSql('ALTER TABLE exam_submission DROP FOREIGN KEY `FK_C90D20A6578D5E91`');
        $this->addSql('ALTER TABLE exam_submission ADD student_id INT NOT NULL, DROP student_name');
        $this->addSql('ALTER TABLE exam_submission ADD CONSTRAINT FK_C90D20A6578D5E91 FOREIGN KEY (exam_id) REFERENCES exam (id)');
        $this->addSql('ALTER TABLE exam_submission ADD CONSTRAINT FK_C90D20A6CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_C90D20A6CB944F1A ON exam_submission (student_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE choice DROP FOREIGN KEY FK_C1AB5A921E27F6BF');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E578D5E91');
        $this->addSql('DROP TABLE choice');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE bulletin DROP FOREIGN KEY FK_2B7D8942CB944F1A');
        $this->addSql('DROP INDEX IDX_2B7D8942CB944F1A ON bulletin');
        $this->addSql('ALTER TABLE bulletin ADD student_name VARCHAR(255) NOT NULL, DROP student_id');
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY FK_6C3C6D75CB944F1A');
        $this->addSql('DROP INDEX IDX_6C3C6D75CB944F1A ON certification');
        $this->addSql('ALTER TABLE certification ADD student_name VARCHAR(255) NOT NULL, DROP student_id');
        $this->addSql('ALTER TABLE exam_submission DROP FOREIGN KEY FK_C90D20A6578D5E91');
        $this->addSql('ALTER TABLE exam_submission DROP FOREIGN KEY FK_C90D20A6CB944F1A');
        $this->addSql('DROP INDEX IDX_C90D20A6CB944F1A ON exam_submission');
        $this->addSql('ALTER TABLE exam_submission ADD student_name VARCHAR(255) NOT NULL, DROP student_id');
        $this->addSql('ALTER TABLE exam_submission ADD CONSTRAINT `FK_C90D20A6578D5E91` FOREIGN KEY (exam_id) REFERENCES exam (id) ON DELETE CASCADE');
    }
}
