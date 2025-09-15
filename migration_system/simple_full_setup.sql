-- إعداد مبسط لجداول النقل الشامل
USE platinum_migration;

-- جدول جلسات النقل الشامل
CREATE TABLE IF NOT EXISTS `full_migration_sessions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `session_name` VARCHAR(255) DEFAULT NULL,
    `options` TEXT NOT NULL,
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
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- جدول تطابق المعرفات الشامل (محسن)
CREATE TABLE IF NOT EXISTS `id_mapping_full` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `migration_session_id` INT DEFAULT NULL,
    `source_table` VARCHAR(100) NOT NULL,
    `target_table` VARCHAR(100) NOT NULL,
    `source_id` INT NOT NULL,
    `target_id` INT NOT NULL,
    `additional_data` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_mapping (`source_table`, `target_table`, `source_id`)
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

-- إنشاء فهارس للأداء
CREATE INDEX IF NOT EXISTS idx_session_status ON full_migration_sessions(status, created_at);
CREATE INDEX IF NOT EXISTS idx_log_session_table ON full_migration_log(migration_session_id, table_name);
CREATE INDEX IF NOT EXISTS idx_log_status ON full_migration_log(status, completed_at);
CREATE INDEX IF NOT EXISTS idx_mapping_source ON id_mapping_full(source_table, source_id);
CREATE INDEX IF NOT EXISTS idx_mapping_target ON id_mapping_full(target_table, target_id);

-- إدراج بيانات اختبارية
INSERT IGNORE INTO full_migration_sessions (session_name, options, status)
VALUES ('DATABASE_SETUP', '{"type": "setup"}', 'completed');

SELECT 'تم إعداد جداول النقل الشامل بنجاح!' as setup_message;
