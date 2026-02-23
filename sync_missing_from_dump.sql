-- =============================================================================
-- Script pour ajouter les éléments du dump phpMyAdmin manquants en base.
-- Base cible : user (MariaDB/MySQL)
-- À exécuter dans phpMyAdmin ou : mysql -u root user < sync_missing_from_dump.sql
-- Les erreurs "Duplicate key" ou "already exists" peuvent être ignorées.
-- =============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. Tables : CREATE TABLE IF NOT EXISTS (structure complète avec clés)
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `performed_at` datetime NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `performed_by_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F6E1C0F52E65C292` (`performed_by_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `grade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `note` double NOT NULL,
  `coefficient` double NOT NULL,
  `session` varchar(20) NOT NULL,
  `academic_year` varchar(9) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `created_at` datetime NOT NULL,
  `student_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_595AAE34CB944F1A` (`student_id`),
  KEY `IDX_595AAE34AFC2B591` (`module_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `metier` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `metier_avance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `metier_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BFAE3046ED16FA20` (`metier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `report_card_line` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_name` varchar(100) NOT NULL,
  `note` double NOT NULL,
  `coefficient` double NOT NULL,
  `teacher_comment` longtext DEFAULT NULL,
  `bulletin_id` int(11) NOT NULL,
  `grade_id` int(11) DEFAULT NULL,
  `note_cc` double DEFAULT NULL,
  `note_ds` double DEFAULT NULL,
  `note_exam` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_1393DB4CD1AAB236` (`bulletin_id`),
  KEY `IDX_1393DB4CFE19A1A8` (`grade_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `signature_asset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `label` varchar(100) NOT NULL,
  `type` varchar(20) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `uploaded_by_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_8AF8A807A2B28FE8` (`uploaded_by_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `student_module_grade` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_name` varchar(100) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(30) NOT NULL,
  `note_cc` double DEFAULT NULL,
  `note_ds` double DEFAULT NULL,
  `note_exam` double DEFAULT NULL,
  `coefficient` double NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `student_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_student_module_semester` (`student_id`,`module_name`,`academic_year`,`semester`),
  KEY `IDX_C9886FEDCB944F1A` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- 2. Contraintes FK (ignorer si déjà existantes)
-- -----------------------------------------------------------------------------

-- audit_log
ALTER TABLE `audit_log` ADD CONSTRAINT `FK_F6E1C0F52E65C292` FOREIGN KEY (`performed_by_id`) REFERENCES `user` (`id`);

-- grade
ALTER TABLE `grade` ADD CONSTRAINT `FK_595AAE34CB944F1A` FOREIGN KEY (`student_id`) REFERENCES `user` (`id`);
ALTER TABLE `grade` ADD CONSTRAINT `FK_595AAE34AFC2B591` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`);

-- metier_avance
ALTER TABLE `metier_avance` ADD CONSTRAINT `FK_BFAE3046ED16FA20` FOREIGN KEY (`metier_id`) REFERENCES `metier` (`id`);

-- report_card_line (si la table a été créée par la migration Version20260223120000,
-- les FK s'appellent FK_rcl_bulletin et FK_rcl_grade : ignorer "Duplicate foreign key")
ALTER TABLE `report_card_line` ADD CONSTRAINT `FK_1393DB4CD1AAB236` FOREIGN KEY (`bulletin_id`) REFERENCES `bulletin` (`id`) ON DELETE CASCADE;
ALTER TABLE `report_card_line` ADD CONSTRAINT `FK_1393DB4CFE19A1A8` FOREIGN KEY (`grade_id`) REFERENCES `grade` (`id`);

-- signature_asset
ALTER TABLE `signature_asset` ADD CONSTRAINT `FK_8AF8A807A2B28FE8` FOREIGN KEY (`uploaded_by_id`) REFERENCES `user` (`id`);

-- student_module_grade
ALTER TABLE `student_module_grade` ADD CONSTRAINT `FK_C9886FEDCB944F1A` FOREIGN KEY (`student_id`) REFERENCES `user` (`id`);

-- -----------------------------------------------------------------------------
-- 3. Données : INSERT IGNORE (ne pas écraser les lignes existantes)
-- -----------------------------------------------------------------------------

INSERT IGNORE INTO `audit_log` (`id`, `entity_type`, `entity_id`, `action`, `performed_at`, `details`, `performed_by_id`) VALUES
(1, 'Certification', 2, 'CREATED', '2026-02-12 13:10:26', '{\"type\":\"REUSSITE\",\"unique_number\":\"EDU-2026-RE-000001\"}', 1),
(2, 'Bulletin', 7, 'CREATED', '2026-02-15 14:22:16', NULL, 1),
(3, 'Certification', 3, 'CREATED', '2026-02-15 14:23:16', '{\"type\":\"REUSSITE\",\"unique_number\":\"EDU-2026-RE-000002\"}', 1),
(4, 'Bulletin', 8, 'CREATED', '2026-02-16 11:17:32', NULL, 1),
(5, 'Certification', 4, 'CREATED', '2026-02-16 11:18:41', '{\"type\":\"REUSSITE\",\"unique_number\":\"EDU-2026-RE-000003\"}', 1),
(6, 'Bulletin', 7, 'DELETED', '2026-02-16 11:19:03', NULL, 1),
(7, 'Certification', 5, 'CREATED', '2026-02-16 11:50:10', '{\"type\":\"SCOLARITE\",\"unique_number\":\"EDU-2026-SC-000003\"}', 1),
(8, 'Bulletin', 9, 'CREATED', '2026-02-16 19:31:34', NULL, 5),
(9, 'Certification', 6, 'CREATED', '2026-02-16 19:38:52', '{\"type\":\"SCOLARITE\",\"unique_number\":\"EDU-2026-SC-000004\"}', 5),
(10, 'Certification', 7, 'CREATED', '2026-02-16 19:39:02', '{\"type\":\"SCOLARITE\",\"unique_number\":\"EDU-2026-SC-000005\"}', 5),
(11, 'Certification', 8, 'CREATED', '2026-02-16 19:39:13', '{\"type\":\"SCOLARITE\",\"unique_number\":\"EDU-2026-SC-000006\"}', 5),
(12, 'Certification', 9, 'CREATED', '2026-02-16 19:39:25', '{\"type\":\"SCOLARITE\",\"unique_number\":\"EDU-2026-SC-000007\"}', 5),
(13, 'Certification', 10, 'CREATED', '2026-02-16 19:39:58', '{\"type\":\"SCOLARITE\",\"unique_number\":\"EDU-2026-SC-000008\"}', 5),
(14, 'Certification', 11, 'CREATED', '2026-02-16 19:41:27', '{\"type\":\"SCOLARITE\",\"unique_number\":\"EDU-2026-SC-000009\"}', 5),
(15, 'Bulletin', 9, 'DELETED', '2026-02-16 19:49:06', NULL, 5),
(16, 'Bulletin', 10, 'CREATED', '2026-02-16 19:51:01', NULL, 5),
(17, 'Certification', 12, 'CREATED', '2026-02-18 20:20:42', '{\"type\":\"REUSSITE\",\"unique_number\":\"EDU-2026-RE-000010\"}', 1),
(18, 'Bulletin', 11, 'CREATED', '2026-02-18 20:23:53', NULL, 1),
(19, 'Bulletin', 12, 'CREATED', '2026-02-19 14:57:22', NULL, 1),
(20, 'Bulletin', 13, 'CREATED', '2026-02-19 15:02:57', NULL, 1),
(21, 'Certification', 13, 'CREATED', '2026-02-19 15:26:19', '{\"type\":\"REUSSITE\",\"unique_number\":\"EDU-2026-RE-000011\"}', 1),
(22, 'Certification', 4, 'REVOKED', '2026-02-19 15:31:16', '{\"reason\":\"fhgfghfhgfhgfg\"}', 1),
(23, 'Certification', 13, 'REVOKED', '2026-02-19 15:31:54', '{\"reason\":\"rachid\"}', 1),
(24, 'Bulletin', 14, 'CREATED', '2026-02-19 17:41:26', NULL, 1),
(25, 'Bulletin', 14, 'UPDATED', '2026-02-19 17:47:30', NULL, 1),
(26, 'Bulletin', 15, 'CREATED', '2026-02-19 17:57:52', NULL, 1),
(27, 'SignatureAsset', 1, 'CREATED', '2026-02-20 16:31:43', NULL, 1),
(28, 'Certification', 14, 'CREATED', '2026-02-20 16:45:29', '{\"type\":\"NOTES\",\"unique_number\":\"EDU-2026-RN-000012\"}', 1),
(29, 'Certification', 14, 'PDF_GENERATED', '2026-02-20 16:47:40', NULL, 1),
(30, 'Certification', 14, 'PDF_GENERATED', '2026-02-20 16:57:13', NULL, 1),
(31, 'Certification', 15, 'CREATED', '2026-02-20 17:19:25', '{\"type\":\"NOTES\",\"unique_number\":\"EDU-2026-RN-000013\"}', 1),
(32, 'Certification', 15, 'PDF_GENERATED', '2026-02-20 17:19:50', NULL, 1);

INSERT IGNORE INTO `metier` (`id`, `nom`, `description`) VALUES
(1, 'Gestionnaire de Scolarité', 'Responsable de l\'édition des bulletins et du suivi des dossiers étudiants.'),
(2, 'Chargé d\'Examen', 'Organise les sessions d\'examens et saisit les notes pour les bulletins.'),
(3, 'Assistant Qualité Formation', 'Vérifie la conformité des bulletins avant publication.');

INSERT IGNORE INTO `metier_avance` (`id`, `nom`, `description`, `metier_id`) VALUES
(1, 'Responsable Pédagogique', 'Supervise la validation des examens et délivre les certifications finales.', 1),
(2, 'Président de Jury', 'Valide les délibérations et signe officiellement les procès-verbaux de certification.', 1),
(3, 'Auditeur de Certification', 'S\'assure que les processus de délivrance des diplômes respectent les normes en vigueur.', 1);

INSERT IGNORE INTO `report_card_line` (`id`, `module_name`, `note`, `coefficient`, `teacher_comment`, `bulletin_id`, `grade_id`, `note_cc`, `note_ds`, `note_exam`) VALUES
(1, 'Mathématiques', 15.1, 3, 'Bon travail', 16, NULL, 14, 16, 15),
(2, 'Informatique', 17.2, 4, 'Bon travail', 16, NULL, 17, 18, 17),
(3, 'Physique', 12.15, 2, 'Bon travail', 16, NULL, 11.5, 13, 12),
(4, 'Anglais', 14.75, 2, 'Bon travail', 16, NULL, 14.5, 14, 15),
(5, 'Communication', 15.8, 1, 'Bon travail', 16, NULL, 16, 15, 16);

INSERT IGNORE INTO `signature_asset` (`id`, `label`, `type`, `image_path`, `created_at`, `uploaded_by_id`) VALUES
(1, 'rachid signature', 'signature', 'uploads/signatures/589818181-1464343591294299-7915639525831542378-n-69987e5f6f8f0.png', '2026-02-20 16:31:43', 1);

-- -----------------------------------------------------------------------------
-- 4. Colonnes manquantes sur des tables existantes (si besoin)
-- -----------------------------------------------------------------------------
-- Si bulletin n'a pas mention/class_rank/hmac_hash/etc. :
-- ALTER TABLE `bulletin` ADD COLUMN IF NOT EXISTS `mention` varchar(30) DEFAULT NULL AFTER `student_id`;
-- (MariaDB 10.5.2+ supporte ADD COLUMN IF NOT EXISTS)

-- Si user a déjà d'autres colonnes (is_active, reset_token, google_id, face_descriptor)
-- et que le dump ne les contient pas, ne rien faire : votre schéma actuel est plus récent.

-- -----------------------------------------------------------------------------
-- 5. Versions de migrations (optionnel : aligner avec le dump)
-- -----------------------------------------------------------------------------
INSERT IGNORE INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20260204162138', '2026-02-11 17:52:37', 65),
('DoctrineMigrations\\Version20260204164201', '2026-02-11 17:52:37', 365),
('DoctrineMigrations\\Version20260204230236', '2026-02-11 17:52:37', 0),
('DoctrineMigrations\\Version20260208193256', '2026-02-11 17:52:37', 0);

-- Fin du script
