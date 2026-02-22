<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add face_descriptor JSON column to user table for face recognition embeddings';
    }

    public function up(Schema $schema): void
    {
        // Add JSON column (nullable)
        $this->addSql('ALTER TABLE `user` ADD face_descriptor JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP face_descriptor');
    }
}
