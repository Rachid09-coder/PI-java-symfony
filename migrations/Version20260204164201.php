<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260204164201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bulletin ADD academic_year VARCHAR(9) NOT NULL, ADD semester VARCHAR(10) NOT NULL, ADD average DOUBLE PRECISION NOT NULL, ADD status VARCHAR(20) NOT NULL, ADD created_at DATETIME NOT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD student_id INT NOT NULL');
        $this->addSql('ALTER TABLE bulletin ADD CONSTRAINT FK_2B7D8942CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_2B7D8942CB944F1A ON bulletin (student_id)');
        $this->addSql('ALTER TABLE certification ADD type VARCHAR(50) NOT NULL, ADD issued_at DATETIME NOT NULL, ADD verification_code VARCHAR(30) NOT NULL, ADD pdf_path VARCHAR(255) DEFAULT NULL, ADD status VARCHAR(20) NOT NULL, ADD student_id INT NOT NULL, ADD bulletin_id INT NOT NULL');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT FK_6C3C6D75CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT FK_6C3C6D75D1AAB236 FOREIGN KEY (bulletin_id) REFERENCES bulletin (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6C3C6D75E821C39F ON certification (verification_code)');
        $this->addSql('CREATE INDEX IDX_6C3C6D75CB944F1A ON certification (student_id)');
        $this->addSql('CREATE INDEX IDX_6C3C6D75D1AAB236 ON certification (bulletin_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE bulletin DROP FOREIGN KEY FK_2B7D8942CB944F1A');
        $this->addSql('DROP INDEX IDX_2B7D8942CB944F1A ON bulletin');
        $this->addSql('ALTER TABLE bulletin DROP academic_year, DROP semester, DROP average, DROP status, DROP created_at, DROP updated_at, DROP student_id');
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY FK_6C3C6D75CB944F1A');
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY FK_6C3C6D75D1AAB236');
        $this->addSql('DROP INDEX UNIQ_6C3C6D75E821C39F ON certification');
        $this->addSql('DROP INDEX IDX_6C3C6D75CB944F1A ON certification');
        $this->addSql('DROP INDEX IDX_6C3C6D75D1AAB236 ON certification');
        $this->addSql('ALTER TABLE certification DROP type, DROP issued_at, DROP verification_code, DROP pdf_path, DROP status, DROP student_id, DROP bulletin_id');
    }
}
