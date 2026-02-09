<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209071817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bulletin CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE certification CHANGE pdf_path pdf_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE course CHANGE thumbnail_path thumbnail_path VARCHAR(255) DEFAULT NULL, CHANGE pdf_path pdf_path VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE exam CHANGE file_path file_path VARCHAR(255) DEFAULT NULL, CHANGE external_link external_link VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE exam_submission CHANGE file_path file_path VARCHAR(255) DEFAULT NULL, CHANGE grade grade DOUBLE PRECISION DEFAULT NULL, CHANGE candidate_identifier candidate_identifier VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE module CHANGE thumbnail thumbnail VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE product CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bulletin CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE certification CHANGE pdf_path pdf_path VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE course CHANGE thumbnail_path thumbnail_path VARCHAR(255) DEFAULT \'NULL\', CHANGE pdf_path pdf_path VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE exam CHANGE file_path file_path VARCHAR(255) DEFAULT \'NULL\', CHANGE external_link external_link VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE exam_submission CHANGE file_path file_path VARCHAR(255) DEFAULT \'NULL\', CHANGE grade grade DOUBLE PRECISION DEFAULT \'NULL\', CHANGE candidate_identifier candidate_identifier VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE module CHANGE thumbnail thumbnail VARCHAR(255) DEFAULT \'NULL\', CHANGE created_at created_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE product CHANGE image image VARCHAR(255) DEFAULT \'NULL\'');
    }
}
