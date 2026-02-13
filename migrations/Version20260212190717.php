<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212190717 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Populate existing NULL values before applying NOT NULL constraint
        $this->addSql("UPDATE bulletin SET mention = 'A définir' WHERE mention IS NULL");
        $this->addSql("UPDATE bulletin SET class_rank = 1 WHERE class_rank IS NULL");

        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bulletin CHANGE average average DOUBLE PRECISION DEFAULT NULL, CHANGE mention mention VARCHAR(30) NOT NULL, CHANGE class_rank class_rank INT NOT NULL');
        $this->addSql('ALTER TABLE metier_avance ADD CONSTRAINT FK_BFAE3046ED16FA20 FOREIGN KEY (metier_id) REFERENCES metier (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bulletin CHANGE average average DOUBLE PRECISION NOT NULL, CHANGE mention mention VARCHAR(30) DEFAULT NULL, CHANGE class_rank class_rank INT DEFAULT NULL');
        $this->addSql('ALTER TABLE metier_avance DROP FOREIGN KEY FK_BFAE3046ED16FA20');
    }
}
