<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211205921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE metier (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE metier_avance (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bulletin CHANGE semester semester VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY `FK_6C3C6D75D1AAB236`');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT FK_6C3C6D75D1AAB236 FOREIGN KEY (bulletin_id) REFERENCES bulletin (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE metier');
        $this->addSql('DROP TABLE metier_avance');
        $this->addSql('ALTER TABLE bulletin CHANGE semester semester VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY FK_6C3C6D75D1AAB236');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT `FK_6C3C6D75D1AAB236` FOREIGN KEY (bulletin_id) REFERENCES bulletin (id)');
    }
}
