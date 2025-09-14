-- ==================================================================
-- إعداد قاعدة البيانات الجديدة للنقل
-- New Database Setup for Migration
-- ==================================================================

-- إنشاء قاعدة البيانات الجديدة
CREATE DATABASE IF NOT EXISTS `platinum_migration`
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `platinum_migration`;

-- ==================================================================
-- جداول إدارة عملية النقل | Migration Management Tables
-- ==================================================================

-- جدول تتبع عمليات النقل
CREATE TABLE `migration_batches` (
    `id` int AUTO_INCREMENT PRIMARY KEY,
    `batch_name` varchar(100) NOT NULL,
    `source_table` varchar(100) NOT NULL,
    `target_table` varchar(100) NOT NULL,
    `file_name` varchar(255) DEFAULT NULL,
    `file_path` varchar(500) DEFAULT NULL,
    `total_records` int DEFAULT 0,
    `processed_records` int DEFAULT 0,
    `successful_records` int DEFAULT 0,
    `failed_records` int DEFAULT 0,
    `status` enum('pending', 'processing', 'completed', 'failed', 'paused') DEFAULT 'pending',
    `started_at` timestamp NULL DEFAULT NULL,
    `completed_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` int DEFAULT NULL,
    `notes` text,
    INDEX idx_status (`status`),
    INDEX idx_created_at (`created_at`)
) ENGINE=InnoDB;

-- جدول تفاصيل الأخطاء
CREATE TABLE `migration_errors` (
    `id` int AUTO_INCREMENT PRIMARY KEY,
    `batch_id` int NOT NULL,
    `row_number` int NOT NULL,
    `error_type` varchar(50) NOT NULL,
    `error_message` text NOT NULL,
    `raw_data` json DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`batch_id`) REFERENCES `migration_batches`(`id`) ON DELETE CASCADE,
    INDEX idx_batch_id (`batch_id`),
    INDEX idx_error_type (`error_type`)
) ENGINE=InnoDB;

-- جدول ربط المعرفات بين القواعد
CREATE TABLE `id_mapping` (
    `id` int AUTO_INCREMENT PRIMARY KEY,
    `source_table` varchar(100) NOT NULL,
    `target_table` varchar(100) NOT NULL,
    `source_id` int NOT NULL,
    `target_id` int NOT NULL,
    `additional_data` json DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_mapping (`source_table`, `target_table`, `source_id`),
    INDEX idx_target (`target_table`, `target_id`)
) ENGINE=InnoDB;

-- جدول حفظ البيانات المؤقتة قبل المعالجة
CREATE TABLE `temp_import_data` (
    `id` int AUTO_INCREMENT PRIMARY KEY,
    `batch_id` int NOT NULL,
    `row_number` int NOT NULL,
    `raw_data` json NOT NULL,
    `processed_data` json DEFAULT NULL,
    `validation_status` enum('pending', 'valid', 'invalid') DEFAULT 'pending',
    `validation_errors` json DEFAULT NULL,
    `is_processed` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`batch_id`) REFERENCES `migration_batches`(`id`) ON DELETE CASCADE,
    INDEX idx_batch_validation (`batch_id`, `validation_status`),
    INDEX idx_processed (`is_processed`)
) ENGINE=InnoDB;

-- جدول إعدادات النقل
CREATE TABLE `migration_settings` (
    `id` int AUTO_INCREMENT PRIMARY KEY,
    `setting_key` varchar(100) NOT NULL UNIQUE,
    `setting_value` text,
    `setting_type` enum('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    `description` text,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- إدراج الإعدادات الافتراضية
INSERT INTO `migration_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('batch_size', '1000', 'integer', 'عدد السجلات المعالجة في المرة الواحدة'),
('max_file_size', '10485760', 'integer', 'الحد الأقصى لحجم ملف CSV بالبايت (10MB)'),
('allowed_extensions', '["csv","xlsx"]', 'json', 'أنواع الملفات المسموحة'),
('backup_before_import', 'true', 'boolean', 'إنشاء نسخة احتياطية قبل الاستيراد'),
('validate_before_import', 'true', 'boolean', 'التحقق من البيانات قبل الاستيراد'),
('auto_rollback_on_error', 'true', 'boolean', 'التراجع التلقائي في حالة الخطأ');

-- جدول قواعد التحقق
CREATE TABLE `validation_rules` (
    `id` int AUTO_INCREMENT PRIMARY KEY,
    `table_name` varchar(100) NOT NULL,
    `field_name` varchar(100) NOT NULL,
    `rule_type` enum('required', 'unique', 'email', 'phone', 'numeric', 'date', 'regex', 'custom') NOT NULL,
    `rule_value` text DEFAULT NULL,
    `error_message` varchar(255) NOT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `priority` int DEFAULT 1,
    INDEX idx_table_field (`table_name`, `field_name`)
) ENGINE=InnoDB;

-- إدراج قواعد التحقق الأساسية للمستخدمين
INSERT INTO `validation_rules` (`table_name`, `field_name`, `rule_type`, `rule_value`, `error_message`) VALUES
('users', 'name', 'required', NULL, 'اسم المستخدم مطلوب'),
('users', 'email', 'email', NULL, 'البريد الإلكتروني غير صحيح'),
('users', 'email', 'unique', 'users.email', 'البريد الإلكتروني موجود مسبقاً'),
('users', 'phone', 'required', NULL, 'رقم الهاتف مطلوب'),
('users', 'phone', 'regex', '^[0-9]{9,15}$', 'رقم الهاتف غير صحيح'),
('cvs', 'passport_id', 'required', NULL, 'رقم جواز السفر مطلوب'),
('cvs', 'passport_id', 'unique', 'cvs.passport_id', 'رقم جواز السفر موجود مسبقاً'),
('cvs', 'salary', 'numeric', NULL, 'الراتب يجب أن يكون رقماً');

-- ==================================================================
-- Views للإحصائيات والتقارير
-- ==================================================================

-- عرض إحصائيات النقل
CREATE VIEW `migration_statistics` AS
SELECT
    mb.source_table,
    mb.target_table,
    COUNT(*) as total_batches,
    SUM(mb.total_records) as total_records,
    SUM(mb.successful_records) as successful_records,
    SUM(mb.failed_records) as failed_records,
    ROUND((SUM(mb.successful_records) / SUM(mb.total_records)) * 100, 2) as success_rate,
    COUNT(CASE WHEN mb.status = 'completed' THEN 1 END) as completed_batches,
    COUNT(CASE WHEN mb.status = 'failed' THEN 1 END) as failed_batches,
    MAX(mb.created_at) as last_migration,
    MIN(mb.created_at) as first_migration
FROM `migration_batches` mb
GROUP BY mb.source_table, mb.target_table;

-- عرض الأخطاء الشائعة
CREATE VIEW `common_errors` AS
SELECT
    me.error_type,
    mb.source_table,
    COUNT(*) as error_count,
    me.error_message,
    COUNT(DISTINCT me.batch_id) as affected_batches
FROM `migration_errors` me
JOIN `migration_batches` mb ON mb.id = me.batch_id
GROUP BY me.error_type, mb.source_table, me.error_message
ORDER BY error_count DESC;

-- ==================================================================
-- Stored Procedures للعمليات الشائعة
-- ==================================================================

DELIMITER //

-- إجراء مخزن لإنشاء batch جديد
CREATE PROCEDURE `CreateMigrationBatch`(
    IN p_batch_name VARCHAR(100),
    IN p_source_table VARCHAR(100),
    IN p_target_table VARCHAR(100),
    IN p_file_name VARCHAR(255),
    IN p_created_by INT,
    OUT p_batch_id INT
)
BEGIN
    INSERT INTO `migration_batches`
    (`batch_name`, `source_table`, `target_table`, `file_name`, `created_by`, `status`)
    VALUES (p_batch_name, p_source_table, p_target_table, p_file_name, p_created_by, 'pending');

    SET p_batch_id = LAST_INSERT_ID();
END //

-- إجراء مخزن لتحديث حالة النقل
CREATE PROCEDURE `UpdateBatchStatus`(
    IN p_batch_id INT,
    IN p_status VARCHAR(20),
    IN p_processed_records INT DEFAULT NULL,
    IN p_successful_records INT DEFAULT NULL,
    IN p_failed_records INT DEFAULT NULL
)
BEGIN
    UPDATE `migration_batches`
    SET
        `status` = p_status,
        `processed_records` = COALESCE(p_processed_records, `processed_records`),
        `successful_records` = COALESCE(p_successful_records, `successful_records`),
        `failed_records` = COALESCE(p_failed_records, `failed_records`),
        `updated_at` = CURRENT_TIMESTAMP,
        `completed_at` = CASE WHEN p_status IN ('completed', 'failed') THEN CURRENT_TIMESTAMP ELSE `completed_at` END,
        `started_at` = CASE WHEN p_status = 'processing' AND `started_at` IS NULL THEN CURRENT_TIMESTAMP ELSE `started_at` END
    WHERE `id` = p_batch_id;
END //

-- إجراء مخزن للتحقق من البيانات
CREATE PROCEDURE `ValidateImportData`(
    IN p_batch_id INT
)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_id INT;
    DECLARE v_raw_data JSON;
    DECLARE v_errors JSON;
    DECLARE v_is_valid BOOLEAN DEFAULT TRUE;

    DECLARE cur CURSOR FOR
        SELECT id, raw_data FROM temp_import_data
        WHERE batch_id = p_batch_id AND validation_status = 'pending';

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    validation_loop: LOOP
        FETCH cur INTO v_id, v_raw_data;
        IF done THEN
            LEAVE validation_loop;
        END IF;

        -- هنا يمكن إضافة منطق التحقق المخصص
        -- مؤقتاً نضع جميع البيانات كصحيحة
        SET v_is_valid = TRUE;
        SET v_errors = JSON_ARRAY();

        UPDATE temp_import_data
        SET
            validation_status = IF(v_is_valid, 'valid', 'invalid'),
            validation_errors = v_errors
        WHERE id = v_id;

    END LOOP;

    CLOSE cur;
END //

DELIMITER ;

-- ==================================================================
-- إنشاء مستخدم خاص بعملية النقل
-- ==================================================================

-- إنشاء مستخدم للنقل (اختياري)
-- CREATE USER 'migration_user'@'localhost' IDENTIFIED BY 'secure_password_here';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON platinum_migration.* TO 'migration_user'@'localhost';
-- GRANT SELECT ON platinum.* TO 'migration_user'@'localhost';
-- GRANT INSERT, UPDATE ON platinum.* TO 'migration_user'@'localhost';

-- ==================================================================
-- فهارس إضافية للأداء
-- ==================================================================

-- فهارس للبحث السريع
CREATE INDEX idx_batch_name ON migration_batches(batch_name);
CREATE INDEX idx_mapping_source ON id_mapping(source_table, source_id);
CREATE INDEX idx_mapping_target ON id_mapping(target_table, target_id);

-- ==================================================================
-- تسجيل إكمال الإعداد
-- ==================================================================

INSERT INTO migration_batches (batch_name, source_table, target_table, status, notes)
VALUES ('DATABASE_SETUP', 'system', 'system', 'completed', 'تم إعداد قاعدة البيانات بنجاح');

SELECT 'تم إنشاء قاعدة بيانات النقل بنجاح! يمكنك الآن البدء في استخدام النظام.' as setup_status;
