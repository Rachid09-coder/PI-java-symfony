<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211194901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bulletin CHANGE semester semester VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY `FK_6C3C6D75D1AAB236`');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT FK_6C3C6D75D1AAB236 FOREIGN KEY (bulletin_id) REFERENCES bulletin (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bulletin CHANGE semester semester VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY FK_6C3C6D75D1AAB236');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT `FK_6C3C6D75D1AAB236` FOREIGN KEY (bulletin_id) REFERENCES bulletin (id)');
    }
}
