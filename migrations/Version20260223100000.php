<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute la colonne coefficient à la table course (merge rachid-branche).
 */
final class Version20260223100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add coefficient column to course table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE course ADD coefficient DOUBLE PRECISION DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE course DROP coefficient');
    }
}
