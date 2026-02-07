<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260207135312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bulletin (id INT AUTO_INCREMENT NOT NULL, academic_year VARCHAR(9) NOT NULL, semester VARCHAR(10) NOT NULL, average DOUBLE PRECISION NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, student_id INT NOT NULL, INDEX IDX_2B7D8942CB944F1A (student_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE certification (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, issued_at DATETIME NOT NULL, verification_code VARCHAR(30) NOT NULL, pdf_path VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, student_id INT NOT NULL, bulletin_id INT NOT NULL, UNIQUE INDEX UNIQ_6C3C6D75E821C39F (verification_code), INDEX IDX_6C3C6D75CB944F1A (student_id), INDEX IDX_6C3C6D75D1AAB236 (bulletin_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE exam (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(20) NOT NULL, file_path VARCHAR(255) DEFAULT NULL, external_link VARCHAR(255) DEFAULT NULL, duration INT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bulletin ADD CONSTRAINT FK_2B7D8942CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT FK_6C3C6D75CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT FK_6C3C6D75D1AAB236 FOREIGN KEY (bulletin_id) REFERENCES bulletin (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bulletin DROP FOREIGN KEY FK_2B7D8942CB944F1A');
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY FK_6C3C6D75CB944F1A');
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY FK_6C3C6D75D1AAB236');
        $this->addSql('DROP TABLE bulletin');
        $this->addSql('DROP TABLE certification');
        $this->addSql('DROP TABLE exam');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
