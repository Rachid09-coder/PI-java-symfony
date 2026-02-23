<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223054504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit_log (id INT AUTO_INCREMENT NOT NULL, entity_type VARCHAR(50) NOT NULL, entity_id INT NOT NULL, action VARCHAR(50) NOT NULL, performed_at DATETIME NOT NULL, details JSON DEFAULT NULL, performed_by_id INT DEFAULT NULL, INDEX IDX_F6E1C0F52E65C292 (performed_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE bulletin (id INT AUTO_INCREMENT NOT NULL, academic_year VARCHAR(9) NOT NULL, semester VARCHAR(20) NOT NULL, average DOUBLE PRECISION DEFAULT NULL, status VARCHAR(20) NOT NULL, mention VARCHAR(30) NOT NULL, class_rank INT DEFAULT NULL, hmac_hash VARCHAR(255) DEFAULT NULL, pdf_path VARCHAR(255) DEFAULT NULL, verification_code VARCHAR(30) DEFAULT NULL, validated_at DATETIME DEFAULT NULL, published_at DATETIME DEFAULT NULL, revoked_at DATETIME DEFAULT NULL, revocation_reason LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, student_id INT NOT NULL, validated_by_id INT DEFAULT NULL, published_by_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_2B7D8942E821C39F (verification_code), INDEX IDX_2B7D8942CB944F1A (student_id), INDEX IDX_2B7D8942C69DE5E5 (validated_by_id), INDEX IDX_2B7D89425B075477 (published_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, description LONGTEXT NOT NULL, icon VARCHAR(50) NOT NULL, color VARCHAR(20) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE certification (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, issued_at DATETIME NOT NULL, verification_code VARCHAR(30) NOT NULL, pdf_path VARCHAR(255) DEFAULT NULL, status VARCHAR(20) NOT NULL, unique_number VARCHAR(50) DEFAULT NULL, valid_until DATETIME DEFAULT NULL, hmac_hash VARCHAR(255) DEFAULT NULL, revoked_at DATETIME DEFAULT NULL, revocation_reason LONGTEXT DEFAULT NULL, student_id INT NOT NULL, bulletin_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_6C3C6D75E821C39F (verification_code), UNIQUE INDEX UNIQ_6C3C6D758B2E7FF4 (unique_number), INDEX IDX_6C3C6D75CB944F1A (student_id), INDEX IDX_6C3C6D75D1AAB236 (bulletin_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE choice (id INT AUTO_INCREMENT NOT NULL, text VARCHAR(255) NOT NULL, is_correct TINYINT NOT NULL, question_id INT NOT NULL, INDEX IDX_C1AB5A921E27F6BF (question_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE course (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, thumbnail_path VARCHAR(255) DEFAULT NULL, pdf_path VARCHAR(255) DEFAULT NULL, generated_content LONGTEXT DEFAULT NULL, coefficient DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE course_module (course_id INT NOT NULL, module_id INT NOT NULL, INDEX IDX_A21CE765591CC992 (course_id), INDEX IDX_A21CE765AFC2B591 (module_id), PRIMARY KEY (course_id, module_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE exam (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(20) NOT NULL, file_path VARCHAR(255) DEFAULT NULL, external_link VARCHAR(255) DEFAULT NULL, duration INT DEFAULT NULL, module_name VARCHAR(100) DEFAULT NULL, grade_category VARCHAR(20) DEFAULT NULL, academic_year VARCHAR(20) DEFAULT NULL, semester INT DEFAULT NULL, coefficient DOUBLE PRECISION DEFAULT NULL, course_id INT DEFAULT NULL, INDEX IDX_38BBA6C6591CC992 (course_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE exam_submission (id INT AUTO_INCREMENT NOT NULL, file_path VARCHAR(255) DEFAULT NULL, grade DOUBLE PRECISION DEFAULT NULL, is_passed TINYINT DEFAULT NULL, submitted_at DATETIME NOT NULL, started_at DATETIME DEFAULT NULL, closed_at DATETIME DEFAULT NULL, candidate_identifier VARCHAR(255) DEFAULT NULL, student_id INT DEFAULT NULL, exam_id INT NOT NULL, INDEX IDX_C90D20A6CB944F1A (student_id), INDEX IDX_C90D20A6578D5E91 (exam_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE forum_post (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, thread_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_996BCC5AE2904019 (thread_id), INDEX IDX_996BCC5AF675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE forum_thread (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, course_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_298F7F52591CC992 (course_id), INDEX IDX_298F7F52F675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE grade (id INT AUTO_INCREMENT NOT NULL, note DOUBLE PRECISION NOT NULL, coefficient DOUBLE PRECISION NOT NULL, session VARCHAR(20) NOT NULL, academic_year VARCHAR(9) NOT NULL, semester VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, student_id INT NOT NULL, module_id INT NOT NULL, INDEX IDX_595AAE34CB944F1A (student_id), INDEX IDX_595AAE34AFC2B591 (module_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE metier (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE metier_avance (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, metier_id INT DEFAULT NULL, INDEX IDX_BFAE3046ED16FA20 (metier_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE module (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, thumbnail VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, items JSON NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, status VARCHAR(20) NOT NULL, tracker_id VARCHAR(255) DEFAULT NULL, tracking_number VARCHAR(100) DEFAULT NULL, carrier VARCHAR(100) DEFAULT NULL, shipping_status VARCHAR(50) DEFAULT NULL, status_detail VARCHAR(255) DEFAULT NULL, public_url VARCHAR(500) DEFAULT NULL, shipping_updated_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_F5299398A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, price NUMERIC(10, 2) NOT NULL, stock INT NOT NULL, image VARCHAR(255) DEFAULT NULL, category_id INT NOT NULL, INDEX IDX_D34A04AD12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, text LONGTEXT NOT NULL, points DOUBLE PRECISION NOT NULL, exam_id INT NOT NULL, INDEX IDX_B6F7494E578D5E91 (exam_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE report_card_line (id INT AUTO_INCREMENT NOT NULL, module_name VARCHAR(100) NOT NULL, note_cc DOUBLE PRECISION DEFAULT NULL, note_ds DOUBLE PRECISION DEFAULT NULL, note_exam DOUBLE PRECISION DEFAULT NULL, note DOUBLE PRECISION NOT NULL, coefficient DOUBLE PRECISION NOT NULL, teacher_comment LONGTEXT DEFAULT NULL, bulletin_id INT NOT NULL, grade_id INT DEFAULT NULL, INDEX IDX_1393DB4CD1AAB236 (bulletin_id), INDEX IDX_1393DB4CFE19A1A8 (grade_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE signature_asset (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(100) NOT NULL, type VARCHAR(20) NOT NULL, image_path VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, uploaded_by_id INT DEFAULT NULL, INDEX IDX_8AF8A807A2B28FE8 (uploaded_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE student_module_grade (id INT AUTO_INCREMENT NOT NULL, module_name VARCHAR(100) NOT NULL, academic_year VARCHAR(20) NOT NULL, semester VARCHAR(30) NOT NULL, note_cc DOUBLE PRECISION DEFAULT NULL, note_ds DOUBLE PRECISION DEFAULT NULL, note_exam DOUBLE PRECISION DEFAULT NULL, coefficient DOUBLE PRECISION NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, student_id INT NOT NULL, INDEX IDX_C9886FEDCB944F1A (student_id), UNIQUE INDEX unique_student_module_semester (student_id, module_name, academic_year, semester), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(255) NOT NULL, role VARCHAR(20) NOT NULL, password VARCHAR(200) NOT NULL, numtel VARCHAR(255) NOT NULL, is_active TINYINT NOT NULL, reset_token VARCHAR(255) DEFAULT NULL, reset_token_expires_at DATETIME DEFAULT NULL, google_id VARCHAR(255) DEFAULT NULL, face_descriptor JSON DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE audit_log ADD CONSTRAINT FK_F6E1C0F52E65C292 FOREIGN KEY (performed_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE bulletin ADD CONSTRAINT FK_2B7D8942CB944F1A FOREIGN KEY (student_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE bulletin ADD CONSTRAINT FK_2B7D8942C69DE5E5 FOREIGN KEY (validated_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE bulletin ADD CONSTRAINT FK_2B7D89425B075477 FOREIGN KEY (published_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT FK_6C3C6D75CB944F1A FOREIGN KEY (student_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE certification ADD CONSTRAINT FK_6C3C6D75D1AAB236 FOREIGN KEY (bulletin_id) REFERENCES bulletin (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE choice ADD CONSTRAINT FK_C1AB5A921E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)');
        $this->addSql('ALTER TABLE course_module ADD CONSTRAINT FK_A21CE765591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course_module ADD CONSTRAINT FK_A21CE765AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE exam ADD CONSTRAINT FK_38BBA6C6591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE exam_submission ADD CONSTRAINT FK_C90D20A6CB944F1A FOREIGN KEY (student_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE exam_submission ADD CONSTRAINT FK_C90D20A6578D5E91 FOREIGN KEY (exam_id) REFERENCES exam (id)');
        $this->addSql('ALTER TABLE forum_post ADD CONSTRAINT FK_996BCC5AE2904019 FOREIGN KEY (thread_id) REFERENCES forum_thread (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_post ADD CONSTRAINT FK_996BCC5AF675F31B FOREIGN KEY (author_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_thread ADD CONSTRAINT FK_298F7F52591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE forum_thread ADD CONSTRAINT FK_298F7F52F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE grade ADD CONSTRAINT FK_595AAE34CB944F1A FOREIGN KEY (student_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE grade ADD CONSTRAINT FK_595AAE34AFC2B591 FOREIGN KEY (module_id) REFERENCES module (id)');
        $this->addSql('ALTER TABLE metier_avance ADD CONSTRAINT FK_BFAE3046ED16FA20 FOREIGN KEY (metier_id) REFERENCES metier (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E578D5E91 FOREIGN KEY (exam_id) REFERENCES exam (id)');
        $this->addSql('ALTER TABLE report_card_line ADD CONSTRAINT FK_1393DB4CD1AAB236 FOREIGN KEY (bulletin_id) REFERENCES bulletin (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE report_card_line ADD CONSTRAINT FK_1393DB4CFE19A1A8 FOREIGN KEY (grade_id) REFERENCES grade (id)');
        $this->addSql('ALTER TABLE signature_asset ADD CONSTRAINT FK_8AF8A807A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE student_module_grade ADD CONSTRAINT FK_C9886FEDCB944F1A FOREIGN KEY (student_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE audit_log DROP FOREIGN KEY FK_F6E1C0F52E65C292');
        $this->addSql('ALTER TABLE bulletin DROP FOREIGN KEY FK_2B7D8942CB944F1A');
        $this->addSql('ALTER TABLE bulletin DROP FOREIGN KEY FK_2B7D8942C69DE5E5');
        $this->addSql('ALTER TABLE bulletin DROP FOREIGN KEY FK_2B7D89425B075477');
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY FK_6C3C6D75CB944F1A');
        $this->addSql('ALTER TABLE certification DROP FOREIGN KEY FK_6C3C6D75D1AAB236');
        $this->addSql('ALTER TABLE choice DROP FOREIGN KEY FK_C1AB5A921E27F6BF');
        $this->addSql('ALTER TABLE course_module DROP FOREIGN KEY FK_A21CE765591CC992');
        $this->addSql('ALTER TABLE course_module DROP FOREIGN KEY FK_A21CE765AFC2B591');
        $this->addSql('ALTER TABLE exam DROP FOREIGN KEY FK_38BBA6C6591CC992');
        $this->addSql('ALTER TABLE exam_submission DROP FOREIGN KEY FK_C90D20A6CB944F1A');
        $this->addSql('ALTER TABLE exam_submission DROP FOREIGN KEY FK_C90D20A6578D5E91');
        $this->addSql('ALTER TABLE forum_post DROP FOREIGN KEY FK_996BCC5AE2904019');
        $this->addSql('ALTER TABLE forum_post DROP FOREIGN KEY FK_996BCC5AF675F31B');
        $this->addSql('ALTER TABLE forum_thread DROP FOREIGN KEY FK_298F7F52591CC992');
        $this->addSql('ALTER TABLE forum_thread DROP FOREIGN KEY FK_298F7F52F675F31B');
        $this->addSql('ALTER TABLE grade DROP FOREIGN KEY FK_595AAE34CB944F1A');
        $this->addSql('ALTER TABLE grade DROP FOREIGN KEY FK_595AAE34AFC2B591');
        $this->addSql('ALTER TABLE metier_avance DROP FOREIGN KEY FK_BFAE3046ED16FA20');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E578D5E91');
        $this->addSql('ALTER TABLE report_card_line DROP FOREIGN KEY FK_1393DB4CD1AAB236');
        $this->addSql('ALTER TABLE report_card_line DROP FOREIGN KEY FK_1393DB4CFE19A1A8');
        $this->addSql('ALTER TABLE signature_asset DROP FOREIGN KEY FK_8AF8A807A2B28FE8');
        $this->addSql('ALTER TABLE student_module_grade DROP FOREIGN KEY FK_C9886FEDCB944F1A');
        $this->addSql('DROP TABLE audit_log');
        $this->addSql('DROP TABLE bulletin');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE certification');
        $this->addSql('DROP TABLE choice');
        $this->addSql('DROP TABLE course');
        $this->addSql('DROP TABLE course_module');
        $this->addSql('DROP TABLE exam');
        $this->addSql('DROP TABLE exam_submission');
        $this->addSql('DROP TABLE forum_post');
        $this->addSql('DROP TABLE forum_thread');
        $this->addSql('DROP TABLE grade');
        $this->addSql('DROP TABLE metier');
        $this->addSql('DROP TABLE metier_avance');
        $this->addSql('DROP TABLE module');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE report_card_line');
        $this->addSql('DROP TABLE signature_asset');
        $this->addSql('DROP TABLE student_module_grade');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
