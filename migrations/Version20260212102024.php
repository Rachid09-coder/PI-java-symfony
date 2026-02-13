<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260212102024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, entity_type VARCHAR(50) NOT NULL, entity_id INT NOT NULL, action VARCHAR(50) NOT NULL, performed_at DATETIME NOT NULL, details JSON DEFAULT NULL, performed_by_id INT DEFAULT NULL, INDEX IDX_F6E1C0F52E65C292 (performed_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE grade (id INT AUTO_INCREMENT NOT NULL, note DOUBLE PRECISION NOT NULL, coefficient DOUBLE PRECISION NOT NULL, session VARCHAR(20) NOT NULL, academic_year VARCHAR(9) NOT NULL, semester VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, student_id INT NOT NULL, module_id INT NOT NULL, INDEX IDX_595AAE34CB944F1A (student_id), INDEX IDX_595AAE34AFC2B591 (module_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE report_card_line (id INT AUTO_INCREMENT NOT NULL, module_name VARCHAR(100) NOT NULL, note DOUBLE PRECISION NOT NULL, coefficient DOUBLE PRECISION NOT NULL, teacher_comment LONGTEXT DEFAULT NULL, bulletin_id INT NOT NULL, grade_id INT DEFAULT NULL, INDEX IDX_1393DB4CD1AAB236 (bulletin_id), INDEX IDX_1393DB4CFE19A1A8 (grade_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE signature_asset (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, image_path VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, uploaded_by_id INT DEFAULT NULL, INDEX IDX_8AF8A807A2B28FE8 (uploaded_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F52E65C292 FOREIGN KEY (performed_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE grade ADD CONSTRAINT FK_595AAE34CB944F1A FOREIGN KEY (student_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE grade ADD CONSTRAINT FK_595AAE34AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');
        $this->addSql('ALTER TABLE report_card_line ADD CONSTRAINT FK_1393DB4CD1AAB236 FOREIGN KEY (bulletin_id) REFERENCES bulletin (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE report_card_line ADD CONSTRAINT FK_1393DB4CFE19A1A8 FOREIGN KEY (grade_id) REFERENCES grade (id)');
        $this->addSql('ALTER TABLE signature_asset ADD CONSTRAINT FK_8AF8A807A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE bulletin ADD mention VARCHAR(30) DEFAULT NULL, ADD class_rank INT DEFAULT NULL, ADD hmac_hash VARCHAR(255) DEFAULT NULL, ADD pdf_path VARCHAR(255) DEFAULT NULL, ADD verification_code VARCHAR(30) DEFAULT NULL, ADD validated_at DATETIME DEFAULT NULL, ADD published_at DATETIME DEFAULT NULL, ADD revoked_at DATETIME DEFAULT NULL, ADD revocation_reason LONGTEXT DEFAULT NULL, ADD validated_by_id INT DEFAULT NULL, ADD published_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE bulletin ADD CONSTRAINT FK_2B7D8942C69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE bulletin ADD CONSTRAINT FK_2B7D89425B075477 FOREIGN KEY (published_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2B7D8942E821C39F ON bulletin (verification_code)');
        $this->addSql('CREATE INDEX IDX_2B7D8942C69DE5E5 ON bulletin (validated_by_id)');
        $this->addSql('CREATE INDEX IDX_2B7D89425B075477 ON bulletin (published_by_id)');
        $this->addSql('ALTER TABLE certification ADD unique_number VARCHAR(50) DEFAULT NULL, ADD valid_until DATETIME DEFAULT NULL, ADD hmac_hash VARCHAR(255) DEFAULT NULL, ADD revoked_at DATETIME DEFAULT NULL, ADD revocation_reason LONGTEXT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6C3C6D758B2E7FF4 ON certification (unique_number)');
        $this->addSql('ALTER TABLE metier_avance ADD metier_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE metier_avance ADD CONSTRAINT FK_BFAE3046ED16FA20 FOREIGN KEY (metier_id) REFERENCES metier (id)');
        $this->addSql('CREATE INDEX IDX_BFAE3046ED16FA20 ON metier_avance (metier_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F52E65C292');
        $this->addSql('ALTER TABLE grade DROP FOREIGN KEY FK_595AAE34CB944F1A');
        $this->addSql('ALTER TABLE grade DROP FOREIGN KEY FK_595AAE34AFC2B591');
        $this->addSql('ALTER TABLE report_card_line DROP FOREIGN KEY FK_1393DB4CD1AAB236');
        $this->addSql('ALTER TABLE report_card_line DROP FOREIGN KEY FK_1393DB4CFE19A1A8');
        $this->addSql('ALTER TABLE signature_asset DROP FOREIGN KEY FK_8AF8A807A2B28FE8');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE grade');
        $this->addSql('DROP TABLE report_card_line');
        $this->addSql('DROP TABLE signature_asset');
        $this->addSql('ALTER TABLE bulletin DROP FOREIGN KEY FK_2B7D8942C69DE5E5');
        $this->addSql('ALTER TABLE bulletin DROP FOREIGN KEY FK_2B7D89425B075477');
        $this->addSql('DROP INDEX UNIQ_2B7D8942E821C39F ON bulletin');
        $this->addSql('DROP INDEX IDX_2B7D8942C69DE5E5 ON bulletin');
        $this->addSql('DROP INDEX IDX_2B7D89425B075477 ON bulletin');
        $this->addSql('ALTER TABLE bulletin DROP mention, DROP class_rank, DROP hmac_hash, DROP pdf_path, DROP verification_code, DROP validated_at, DROP published_at, DROP revoked_at, DROP revocation_reason, DROP validated_by_id, DROP published_by_id');
        $this->addSql('DROP INDEX UNIQ_6C3C6D758B2E7FF4 ON certification');
        $this->addSql('ALTER TABLE certification DROP unique_number, DROP valid_until, DROP hmac_hash, DROP revoked_at, DROP revocation_reason');
        $this->addSql('ALTER TABLE metier_avance DROP FOREIGN KEY FK_BFAE3046ED16FA20');
        $this->addSql('DROP INDEX IDX_BFAE3046ED16FA20 ON metier_avance');
        $this->addSql('ALTER TABLE metier_avance DROP metier_id');
    }
}
