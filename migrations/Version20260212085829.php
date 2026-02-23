<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212085829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE metier_avance ADD metier_id INT NOT NULL');
        $this->addSql('ALTER TABLE metier_avance ADD CONSTRAINT FK_BFAE3046ED16FA20 FOREIGN KEY (metier_id) REFERENCES metier (id)');
        $this->addSql('CREATE INDEX IDX_BFAE3046ED16FA20 ON metier_avance (metier_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE metier_avance DROP FOREIGN KEY FK_BFAE3046ED16FA20');
        $this->addSql('DROP INDEX IDX_BFAE3046ED16FA20 ON metier_avance');
        $this->addSql('ALTER TABLE metier_avance DROP metier_id');
    }
}
