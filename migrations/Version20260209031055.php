<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209031055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE exam_submission DROP FOREIGN KEY `FK_C90D20A6578D5E91`');
        $this->addSql('DROP TABLE exam_submission');
        $this->addSql('ALTER TABLE bulletin ADD student_id INT NOT NULL, DROP student_name');
        $this->addSql('ALTER TABLE bulletin ADD CONSTRAINT FK_2B7D8942CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_2B7D8942CB944F1A ON bulletin (student_id)');
        $this->addSql('ALTER TABLE certification ADD student_id INT NOT NULL, DROP student_name');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT FK_6C3C6D75CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6C3C6D75CB944F1A ON certification (student_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE exam_submission (id INT AUTO_INCREMENT NOT NULL, student_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, file_path VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, grade DOUBLE PRECISION DEFAULT NULL, is_passed TINYINT DEFAULT NULL, submitted_at DATETIME NOT NULL, exam_id INT NOT NULL, INDEX IDX_C90D20A6578D5E91 (exam_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE exam_submission ADD CONSTRAINT `FK_C90D20A6578D5E91` FOREIGN KEY (exam_id) REFERENCES exam (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE bulletin DROP FOREIGN KEY FK_2B7D8942CB944F1A');
        $this->addSql('DROP INDEX IDX_2B7D8942CB944F1A ON bulletin');
        $this->addSql('ALTER TABLE bulletin ADD student_name VARCHAR(255) NOT NULL, DROP student_id');
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY FK_6C3C6D75CB944F1A');
        $this->addSql('DROP INDEX IDX_6C3C6D75CB944F1A ON certification');
        $this->addSql('ALTER TABLE certification ADD student_name VARCHAR(255) NOT NULL, DROP student_id');
    }
}
