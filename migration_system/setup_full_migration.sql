-- إعداد جداول النقل الشامل
-- Full Migration Setup Tables

USE platinum_migration;

-- جدول جلسات النقل الشامل
CREATE TABLE IF NOT EXISTS `full_migration_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_name` VARCHAR(255) DEFAULT NULL,
    `options` JSON NOT NULL,
    `status` ENUM('pending', 'in_progress', 'completed', 'failed', 'paused') DEFAULT 'pending',
    `total_tables` INT DEFAULT 0,
    `completed_tables` INT DEFAULT 0,
    `total_records` INT DEFAULT 0,
    `migrated_records` INT DEFAULT 0,
    `failed_records` INT DEFAULT 0,
    `current_phase` VARCHAR(100) DEFAULT NULL,
    `current_table` VARCHAR(100) DEFAULT NULL,
    `progress_percent` DECIMAL(5,2) DEFAULT 0,
    `error_message` TEXT NULL,
    `backup_file` VARCHAR(255) DEFAULT NULL,
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT DEFAULT 1
) ENGINE=InnoDB;

-- جدول تفاصيل النقل
CREATE TABLE IF NOT EXISTS `full_migration_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `migration_session_id` INT DEFAULT NULL,
    `phase_name` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(100) NOT NULL,
    `target_table` VARCHAR(100) DEFAULT NULL,
    `source_records` INT DEFAULT 0,
    `migrated_records` INT DEFAULT 0,
    `failed_records` INT DEFAULT 0,
    `status` ENUM('pending', 'in_progress', 'completed', 'failed', 'skipped') DEFAULT 'pending',
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `duration_seconds` INT DEFAULT NULL,
    `error_message` TEXT NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`migration_session_id`) REFERENCES `full_migration_sessions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- جدول تطابق المعرفات الشامل (محسن)
DROP TABLE IF EXISTS `id_mapping_full`;
CREATE TABLE `id_mapping_full` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `migration_session_id` INT DEFAULT NULL,
    `source_table` VARCHAR(100) NOT NULL,
    `target_table` VARCHAR(100) NOT NULL,
    `source_id` INT NOT NULL,
    `target_id` INT NOT NULL,
    `additional_data` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_mapping (`source_table`, `target_table`, `source_id`),
    INDEX idx_session (`migration_session_id`),
    INDEX idx_source (`source_table`, `source_id`),
    INDEX idx_target (`target_table`, `target_id`),
    FOREIGN KEY (`migration_session_id`) REFERENCES `full_migration_sessions`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- جدول قواعد التحويل
CREATE TABLE IF NOT EXISTS `migration_transformation_rules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `source_table` VARCHAR(100) NOT NULL,
    `target_table` VARCHAR(100) NOT NULL,
    `source_field` VARCHAR(100) NOT NULL,
    `target_field` VARCHAR(100) NOT NULL,
    `transformation_type` ENUM('direct', 'function', 'lookup', 'calculated') DEFAULT 'direct',
    `transformation_rule` TEXT NULL,
    `is_required` TINYINT(1) DEFAULT 0,
    `default_value` TEXT NULL,
    `validation_rule` TEXT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `priority` INT DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- إدراج قواعد التحويل الأساسية
INSERT IGNORE INTO `migration_transformation_rules`
(`source_table`, `target_table`, `source_field`, `target_field`, `transformation_type`, `transformation_rule`) VALUES
-- تحويل المستخدمين
('users', 'users', 'name', 'name', 'direct', NULL),
('users', 'users', 'email', 'email', 'direct', NULL),
('users', 'users', 'phone', 'phone', 'function', 'CASE WHEN phone LIKE "+966%" THEN SUBSTRING(phone, 5) WHEN phone LIKE "966%" THEN SUBSTRING(phone, 4) ELSE phone END'),
('users', 'users', 'password', 'password', 'direct', NULL),
('users', 'users', 'created_at', 'created_at', 'direct', NULL),

-- تحويل العاملات إلى CVs
('maids', 'cvs', 'passport_num', 'passport_id', 'direct', NULL),
('maids', 'cvs', 'name', 'name', 'direct', NULL),
('maids', 'cvs', 'salary', 'salary', 'direct', NULL),
('maids', 'cvs', 'price', 'service_price', 'direct', NULL),
('maids', 'cvs', 'nationality_id', 'national_id', 'direct', NULL),
('maids', 'cvs', 'age', 'age_id', 'function', 'CASE WHEN age <= 25 THEN 1 WHEN age <= 35 THEN 2 WHEN age <= 45 THEN 3 ELSE 4 END'),

-- تحويل الطلبات
('orders', 'orders', 'order_code', 'order_code', 'direct', NULL),
('orders', 'orders', 'status', 'status', 'function', 'CASE WHEN status = "completed" THEN "finished" WHEN status = "cancelled" THEN "canceled" ELSE status END'),
('orders', 'orders', 'user_id', 'user_id', 'lookup', 'id_mapping_full.users'),
('orders', 'orders', 'maid_id', 'biography_id', 'lookup', 'id_mapping_full.maids');

-- View لإحصائيات النقل
CREATE OR REPLACE VIEW `migration_statistics_view` AS
SELECT
    fms.id as session_id,
    fms.session_name,
    fms.status as session_status,
    fms.progress_percent,
    fms.total_tables,
    fms.completed_tables,
    fms.total_records,
    fms.migrated_records,
    fms.failed_records,
    TIMESTAMPDIFF(MINUTE, fms.started_at, COALESCE(fms.completed_at, NOW())) as duration_minutes,
    COUNT(fml.id) as log_entries,
    COUNT(CASE WHEN fml.status = 'completed' THEN 1 END) as successful_tables,
    COUNT(CASE WHEN fml.status = 'failed' THEN 1 END) as failed_tables,
    fms.created_at
FROM full_migration_sessions fms
LEFT JOIN full_migration_log fml ON fml.migration_session_id = fms.id
GROUP BY fms.id;

-- Stored Procedure لبدء النقل
DELIMITER //

CREATE OR REPLACE PROCEDURE `StartFullMigration`(
    IN p_session_name VARCHAR(255),
    IN p_options JSON,
    IN p_created_by INT,
    OUT p_session_id INT
)
BEGIN
    DECLARE v_error_msg TEXT DEFAULT '';
    DECLARE exit handler for sqlexception
    BEGIN
        GET DIAGNOSTICS CONDITION 1 v_error_msg = MESSAGE_TEXT;
        ROLLBACK;
        RESIGNAL SET MESSAGE_TEXT = v_error_msg;
    END;

    START TRANSACTION;

    -- إنشاء جلسة النقل
    INSERT INTO full_migration_sessions (
        session_name, options, status, created_by, started_at
    ) VALUES (
        p_session_name, p_options, 'pending', p_created_by, NOW()
    );

    SET p_session_id = LAST_INSERT_ID();

    -- تحديث الحالة إلى قيد التنفيذ
    UPDATE full_migration_sessions
    SET status = 'in_progress'
    WHERE id = p_session_id;

    COMMIT;
END //

-- Stored Procedure لتحديث التقدم
CREATE OR REPLACE PROCEDURE `UpdateMigrationProgress`(
    IN p_session_id INT,
    IN p_current_phase VARCHAR(100),
    IN p_current_table VARCHAR(100),
    IN p_completed_tables INT,
    IN p_migrated_records INT,
    IN p_failed_records INT
)
BEGIN
    DECLARE v_total_tables INT DEFAULT 0;
    DECLARE v_progress_percent DECIMAL(5,2) DEFAULT 0;

    -- الحصول على إجمالي الجداول
    SELECT total_tables INTO v_total_tables
    FROM full_migration_sessions
    WHERE id = p_session_id;

    -- حساب نسبة التقدم
    IF v_total_tables > 0 THEN
        SET v_progress_percent = (p_completed_tables / v_total_tables) * 100;
    END IF;

    -- تحديث التقدم
    UPDATE full_migration_sessions
    SET
        current_phase = p_current_phase,
        current_table = p_current_table,
        completed_tables = p_completed_tables,
        migrated_records = p_migrated_records,
        failed_records = p_failed_records,
        progress_percent = v_progress_percent,
        updated_at = NOW()
    WHERE id = p_session_id;
END //

-- Stored Procedure لإنهاء النقل
CREATE OR REPLACE PROCEDURE `CompleteMigration`(
    IN p_session_id INT,
    IN p_status VARCHAR(20),
    IN p_error_message TEXT
)
BEGIN
    UPDATE full_migration_sessions
    SET
        status = p_status,
        completed_at = NOW(),
        error_message = p_error_message,
        updated_at = NOW()
    WHERE id = p_session_id;
END //

DELIMITER ;

-- إنشاء فهارس إضافية للأداء
CREATE INDEX idx_session_status ON full_migration_sessions(status, created_at);
CREATE INDEX idx_log_session_table ON full_migration_log(migration_session_id, table_name);
CREATE INDEX idx_log_status ON full_migration_log(status, completed_at);

-- إدراج بيانات اختبارية
INSERT IGNORE INTO full_migration_sessions (session_name, options, status)
VALUES ('DATABASE_SETUP', '{"type": "setup"}', 'completed');

SELECT 'تم إعداد جداول النقل الشامل بنجاح!' as setup_message;
