<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260208015007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exam_submission DROP FOREIGN KEY `FK_C90D20A6578D5E91`');
        $this->addSql('ALTER TABLE exam_submission ADD CONSTRAINT FK_C90D20A6578D5E91 FOREIGN KEY (exam_id) REFERENCES exam (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE exam_submission DROP FOREIGN KEY FK_C90D20A6578D5E91');
        $this->addSql('ALTER TABLE exam_submission ADD CONSTRAINT `FK_C90D20A6578D5E91` FOREIGN KEY (exam_id) REFERENCES exam (id)');
    }
}
