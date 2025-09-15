-- إعداد مبسط لقاعدة البيانات
-- Simple Database Setup

USE platinum_migration;

-- جدول تتبع عمليات النقل
CREATE TABLE IF NOT EXISTS `migration_batches` (
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
    `notes` text
) ENGINE=InnoDB;

-- جدول تفاصيل الأخطاء
CREATE TABLE IF NOT EXISTS `migration_errors` (
    `id` int AUTO_INCREMENT PRIMARY KEY,
    `batch_id` int NOT NULL,
    `row_number` int NOT NULL,
    `error_type` varchar(50) NOT NULL,
    `error_message` text NOT NULL,
    `raw_data` json DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`batch_id`) REFERENCES `migration_batches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- جدول ربط المعرفات
CREATE TABLE IF NOT EXISTS `id_mapping` (
    `id` int AUTO_INCREMENT PRIMARY KEY,
    `source_table` varchar(100) NOT NULL,
    `target_table` varchar(100) NOT NULL,
    `source_id` int NOT NULL,
    `target_id` int NOT NULL,
    `additional_data` json DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_mapping (`source_table`, `target_table`, `source_id`)
) ENGINE=InnoDB;

-- جدول البيانات المؤقتة
CREATE TABLE IF NOT EXISTS `temp_import_data` (
    `id` int AUTO_INCREMENT PRIMARY KEY,
    `batch_id` int NOT NULL,
    `row_number` int NOT NULL,
    `raw_data` json NOT NULL,
    `processed_data` json DEFAULT NULL,
    `validation_status` enum('pending', 'valid', 'invalid') DEFAULT 'pending',
    `validation_errors` json DEFAULT NULL,
    `is_processed` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`batch_id`) REFERENCES `migration_batches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- جدول الإعدادات
CREATE TABLE IF NOT EXISTS `migration_settings` (
    `id` int AUTO_INCREMENT PRIMARY KEY,
    `setting_key` varchar(100) NOT NULL UNIQUE,
    `setting_value` text,
    `setting_type` enum('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    `description` text,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- جدول قواعد التحقق
CREATE TABLE IF NOT EXISTS `validation_rules` (
    `id` int AUTO_INCREMENT PRIMARY KEY,
    `table_name` varchar(100) NOT NULL,
    `field_name` varchar(100) NOT NULL,
    `rule_type` enum('required', 'unique', 'email', 'phone', 'numeric', 'date', 'regex', 'custom') NOT NULL,
    `rule_value` text DEFAULT NULL,
    `error_message` varchar(255) NOT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `priority` int DEFAULT 1
) ENGINE=InnoDB;

-- إدراج الإعدادات الافتراضية
INSERT IGNORE INTO `migration_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('batch_size', '500', 'integer', 'عدد السجلات المعالجة في المرة الواحدة'),
('max_file_size', '10485760', 'integer', 'الحد الأقصى لحجم ملف CSV بالبايت'),
('allowed_extensions', '["csv","xlsx"]', 'json', 'أنواع الملفات المسموحة'),
('backup_before_import', 'true', 'boolean', 'إنشاء نسخة احتياطية قبل الاستيراد'),
('validate_before_import', 'true', 'boolean', 'التحقق من البيانات قبل الاستيراد'),
('auto_rollback_on_error', 'true', 'boolean', 'التراجع التلقائي في حالة الخطأ');

-- إدراج قواعد التحقق للمستخدمين
INSERT IGNORE INTO `validation_rules` (`table_name`, `field_name`, `rule_type`, `rule_value`, `error_message`) VALUES
('users', 'name', 'required', NULL, 'اسم المستخدم مطلوب'),
('users', 'email', 'email', NULL, 'البريد الإلكتروني غير صحيح'),
('users', 'phone', 'required', NULL, 'رقم الهاتف مطلوب'),
('cvs', 'passport_id', 'required', NULL, 'رقم جواز السفر مطلوب'),
('cvs', 'salary', 'numeric', NULL, 'الراتب يجب أن يكون رقماً');

-- تسجيل إكمال الإعداد
INSERT IGNORE INTO migration_batches (batch_name, source_table, target_table, status, notes, successful_records)
VALUES ('DATABASE_SETUP', 'system', 'system', 'completed', 'تم إعداد قاعدة البيانات بنجاح', 1);

SELECT 'تم إنشاء قاعدة البيانات بنجاح!' as setup_status;
