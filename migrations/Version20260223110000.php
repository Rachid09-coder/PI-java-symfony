<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute les colonnes manquantes à la table exam (module_name, grade_category, academic_year, semester, coefficient, course_id).
 */
final class Version20260223110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add module_name, grade_category, academic_year, semester, coefficient, course_id to exam table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE exam ADD module_name VARCHAR(100) DEFAULT NULL, ADD grade_category VARCHAR(20) DEFAULT NULL, ADD academic_year VARCHAR(20) DEFAULT NULL, ADD semester INT DEFAULT NULL, ADD coefficient DOUBLE PRECISION DEFAULT NULL, ADD course_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE exam ADD CONSTRAINT FK_2182C12F591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_2182C12F591CC992 ON exam (course_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE exam DROP FOREIGN KEY FK_2182C12F591CC992');
        $this->addSql('DROP INDEX IDX_2182C12F591CC992 ON exam');
        $this->addSql('ALTER TABLE exam DROP module_name, DROP grade_category, DROP academic_year, DROP semester, DROP coefficient, DROP course_id');
    }
}
