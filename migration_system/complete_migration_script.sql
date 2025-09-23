-- =================================================================
-- سكريبت النقل الشامل من erp_operations_new إلى platinum
-- Complete Migration Script from erp_operations_new to platinum
-- =================================================================

-- إعدادات أولية
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- =================================================================
-- 1. إنشاء جداول التتبع والإدارة
-- =================================================================

-- جدول تتبع النقل الشامل
CREATE TABLE IF NOT EXISTS `full_migration_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `phase_name` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(100) NOT NULL,
    `source_records` INT DEFAULT 0,
    `migrated_records` INT DEFAULT 0,
    `failed_records` INT DEFAULT 0,
    `status` ENUM('pending', 'in_progress', 'completed', 'failed', 'skipped') DEFAULT 'pending',
    `started_at` TIMESTAMP NULL,
    `completed_at` TIMESTAMP NULL,
    `error_message` TEXT NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- جدول ربط المعرفات الشامل
CREATE TABLE IF NOT EXISTS `id_mapping_full` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `source_table` VARCHAR(100) NOT NULL,
    `target_table` VARCHAR(100) NOT NULL,
    `source_id` INT NOT NULL,
    `target_id` INT NOT NULL,
    `additional_data` JSON NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_mapping (`source_table`, `target_table`, `source_id`)
) ENGINE=InnoDB;

-- =================================================================
-- 2. إضافة الجداول المفقودة من ERP إلى Platinum
-- =================================================================

-- إنشاء جداول ERP المفقودة في Platinum

-- جدول about_us
CREATE TABLE IF NOT EXISTS `about_us` (
    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول about_us_translations
CREATE TABLE IF NOT EXISTS `about_us_translations` (
    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    `about_us_id` bigint UNSIGNED NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` varchar(1000) NOT NULL,
    `locale` varchar(255) NOT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`about_us_id`) REFERENCES `about_us`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول complaints
CREATE TABLE IF NOT EXISTS `complaints` (
    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(255) DEFAULT NULL,
    `phone` varchar(255) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `complaint_type_id` bigint UNSIGNED DEFAULT NULL,
    `subject` varchar(255) DEFAULT NULL,
    `message` text,
    `status` varchar(255) NOT NULL DEFAULT 'new',
    `priority` varchar(255) NOT NULL DEFAULT 'medium',
    `resolved_at` timestamp NULL DEFAULT NULL,
    `resolved_by` bigint UNSIGNED DEFAULT NULL,
    `resolution_notes` text,
    `user_id` bigint UNSIGNED DEFAULT NULL,
    `order_id` bigint UNSIGNED DEFAULT NULL,
    `contract_id` bigint UNSIGNED DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول complaint_types
CREATE TABLE IF NOT EXISTS `complaint_types` (
    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text,
    `is_active` tinyint(1) NOT NULL DEFAULT '1',
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول deportation_procedures
CREATE TABLE IF NOT EXISTS `deportation_procedures` (
    `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
    `maid_id` bigint UNSIGNED NOT NULL,
    `user_id` bigint UNSIGNED DEFAULT NULL,
    `contract_id` bigint UNSIGNED DEFAULT NULL,
    `reason` varchar(255) DEFAULT NULL,
    `deportation_date` date DEFAULT NULL,
    `status` varchar(255) NOT NULL DEFAULT 'pending',
    `notes` text,
    `documents` text,
    `cost` decimal(10,2) DEFAULT NULL,
    `created_by` bigint UNSIGNED DEFAULT NULL,
    `created_at` timestamp NULL DEFAULT NULL,
    `updated_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- 3. إضافة الحقول المفقودة للجداول الموجودة
-- =================================================================

-- إضافة حقول للمستخدمين
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `first_name` varchar(255) DEFAULT NULL AFTER `name`,
ADD COLUMN IF NOT EXISTS `last_name` varchar(255) DEFAULT NULL AFTER `first_name`,
ADD COLUMN IF NOT EXISTS `national_num` varchar(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `phone2` varchar(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `birthdate` date DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `register_from` varchar(255) NOT NULL DEFAULT 'web',
ADD COLUMN IF NOT EXISTS `is_blocked` tinyint NOT NULL DEFAULT '0',
ADD COLUMN IF NOT EXISTS `status` tinyint NOT NULL DEFAULT '1';

-- إضافة حقول للسير الذاتية (CVs)
ALTER TABLE `cvs`
ADD COLUMN IF NOT EXISTS `name` varchar(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `nationality_id` int DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `religion_id` int DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `birth_date` date DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `available` tinyint(1) NOT NULL DEFAULT '1',
ADD COLUMN IF NOT EXISTS `is_pinned` tinyint(1) NOT NULL DEFAULT '0',
ADD COLUMN IF NOT EXISTS `available_at` date DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `status` varchar(255) NOT NULL DEFAULT 'available',
ADD COLUMN IF NOT EXISTS `is_show` tinyint(1) NOT NULL DEFAULT '1',
ADD COLUMN IF NOT EXISTS `experience_years` varchar(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `experience_status` enum('has_experience','no_experience') NOT NULL DEFAULT 'no_experience';

-- إضافة حقول للعناوين
ALTER TABLE `addresses`
ADD COLUMN IF NOT EXISTS `lat` double(8,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `lng` double(8,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `set_default` int NOT NULL DEFAULT '0';

-- =================================================================
-- 4. المرحلة 1: نقل البيانات المرجعية
-- =================================================================

INSERT INTO full_migration_log (phase_name, table_name, status, started_at) VALUES ('البيانات المرجعية', 'countries', 'in_progress', NOW());

-- نقل البلدان
INSERT IGNORE INTO countries (id, name, created_at, updated_at)
SELECT id,
       (SELECT name FROM erp_operations_new.countries WHERE id = c.id LIMIT 1) as name,
       created_at,
       updated_at
FROM erp_operations_new.countries c;

UPDATE full_migration_log SET status = 'completed', completed_at = NOW(), migrated_records = ROW_COUNT()
WHERE table_name = 'countries' AND status = 'in_progress';

-- نقل المدن
INSERT INTO full_migration_log (phase_name, table_name, status, started_at) VALUES ('البيانات المرجعية', 'cities', 'in_progress', NOW());

INSERT IGNORE INTO cities (id, country_id, name, created_at, updated_at)
SELECT id, country_id, name, created_at, updated_at
FROM erp_operations_new.cities;

UPDATE full_migration_log SET status = 'completed', completed_at = NOW(), migrated_records = ROW_COUNT()
WHERE table_name = 'cities' AND status = 'in_progress';

-- نقل الجنسيات
INSERT INTO full_migration_log (phase_name, table_name, status, started_at) VALUES ('البيانات المرجعية', 'nationalities', 'in_progress', NOW());

INSERT IGNORE INTO nationalities (id, name, created_at, updated_at)
SELECT n.id,
       COALESCE((SELECT name FROM erp_operations_new.nationalities_translations WHERE nationality_id = n.id AND locale = 'ar' LIMIT 1), 'غير محدد') as name,
       n.created_at,
       n.updated_at
FROM erp_operations_new.nationalities n;

UPDATE full_migration_log SET status = 'completed', completed_at = NOW(), migrated_records = ROW_COUNT()
WHERE table_name = 'nationalities' AND status = 'in_progress';

-- =================================================================
-- 5. المرحلة 2: نقل المستخدمين والإداريون
-- =================================================================

INSERT INTO full_migration_log (phase_name, table_name, status, started_at) VALUES ('المستخدمون والإداريون', 'users', 'in_progress', NOW());

-- نقل المستخدمين
INSERT INTO users (
    name, first_name, last_name, phone, email, password,
    address, city, country, national_num, phone2, birthdate,
    nationality_id, register_from, is_blocked, status,
    created_at, updated_at
)
SELECT
    COALESCE(name, CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))) as name,
    first_name,
    last_name,
    -- تنظيف رقم الهاتف
    CASE
        WHEN phone LIKE '+966%' THEN SUBSTRING(phone, 5)
        WHEN phone LIKE '966%' THEN SUBSTRING(phone, 4)
        WHEN phone LIKE '0%' THEN phone
        ELSE CONCAT('0', phone)
    END as phone,
    email,
    password,
    address,
    city,
    'Saudi Arabia' as country,
    national_num,
    phone2,
    birthdate,
    nationality_id,
    'migration' as register_from,
    0 as is_blocked,
    1 as status,
    created_at,
    updated_at
FROM erp_operations_new.users u
WHERE NOT EXISTS (
    SELECT 1 FROM users pu
    WHERE (pu.email = u.email AND u.email IS NOT NULL)
       OR (pu.phone = u.phone AND u.phone IS NOT NULL)
);

-- حفظ تطابق المستخدمين
INSERT INTO id_mapping_full (source_table, target_table, source_id, target_id)
SELECT
    'users' as source_table,
    'users' as target_table,
    u.id as source_id,
    pu.id as target_id
FROM erp_operations_new.users u
JOIN users pu ON (pu.email = u.email OR pu.phone = u.phone)
WHERE NOT EXISTS (
    SELECT 1 FROM id_mapping_full im
    WHERE im.source_table = 'users' AND im.source_id = u.id
);

UPDATE full_migration_log SET status = 'completed', completed_at = NOW(), migrated_records = ROW_COUNT()
WHERE table_name = 'users' AND status = 'in_progress';

-- نقل العناوين
INSERT INTO full_migration_log (phase_name, table_name, status, started_at) VALUES ('المستخدمون والإداريون', 'addresses', 'in_progress', NOW());

INSERT INTO addresses (
    user_id, address, country, city, postal_code, phone,
    lat, lng, set_default, created_at, updated_at
)
SELECT
    im.target_id as user_id,
    ua.address,
    ua.country,
    ua.city,
    ua.postal_code,
    ua.phone,
    ua.lat,
    ua.lng,
    ua.set_default,
    ua.created_at,
    ua.updated_at
FROM erp_operations_new.user_addresses ua
JOIN id_mapping_full im ON im.source_table = 'users' AND im.source_id = ua.user_id
WHERE NOT EXISTS (
    SELECT 1 FROM addresses pa
    WHERE pa.user_id = im.target_id AND pa.address = ua.address
);

UPDATE full_migration_log SET status = 'completed', completed_at = NOW(), migrated_records = ROW_COUNT()
WHERE table_name = 'addresses' AND status = 'in_progress';

-- نقل بيانات العملاء
INSERT INTO customers (
    user_id, national_identification_number, accommodation_type,
    childrens_under_age, family_number, birth_date, other_phone,
    rooms_number, delivery_city, job, created_at, updated_at
)
SELECT
    im.target_id as user_id,
    u.national_num as national_identification_number,
    'شقة' as accommodation_type,
    '0' as childrens_under_age,
    '1' as family_number,
    u.birthdate as birth_date,
    u.phone2 as other_phone,
    '3' as rooms_number,
    u.city as delivery_city,
    'غير محدد' as job,
    u.created_at,
    u.updated_at
FROM erp_operations_new.users u
JOIN id_mapping_full im ON im.source_table = 'users' AND im.source_id = u.id
WHERE NOT EXISTS (
    SELECT 1 FROM customers pc
    WHERE pc.user_id = im.target_id
);

-- =================================================================
-- 6. المرحلة 3: نقل العاملات إلى CVs
-- =================================================================

INSERT INTO full_migration_log (phase_name, table_name, status, started_at) VALUES ('البيانات الأساسية', 'cvs', 'in_progress', NOW());

-- نقل العاملات
INSERT INTO cvs (
    passport_id, name, nationality_id, religion_id, birth_date,
    salary, service_price, age_id, social_id, region_id,
    personal_image, cv_file, cv_rev_type, available, is_pinned,
    available_at, status, is_show, experience_years, experience_status,
    created_at, updated_at
)
SELECT
    m.passport_num as passport_id,
    m.name,
    m.nationality_id,
    m.religion_id,
    m.birth_date,
    m.salary,
    m.price as service_price,
    -- تحويل العمر إلى فئات
    CASE
        WHEN m.age <= 25 THEN 1
        WHEN m.age <= 35 THEN 2
        WHEN m.age <= 45 THEN 3
        ELSE 4
    END as age_id,
    1 as social_id, -- افتراضي
    m.religion_id as region_id,
    m.cv_preview as personal_image,
    m.cv_file,
    1 as cv_rev_type,
    m.available,
    m.is_pinned,
    m.available_at,
    m.status,
    m.is_show,
    m.experience_years,
    m.experience_status,
    m.created_at,
    m.updated_at
FROM erp_operations_new.maids m
WHERE m.passport_num IS NOT NULL
AND m.passport_num != ''
AND NOT EXISTS (
    SELECT 1 FROM cvs c
    WHERE c.passport_id = m.passport_num
);

-- حفظ تطابق العاملات
INSERT INTO id_mapping_full (source_table, target_table, source_id, target_id)
SELECT
    'maids' as source_table,
    'cvs' as target_table,
    m.id as source_id,
    c.id as target_id
FROM erp_operations_new.maids m
JOIN cvs c ON c.passport_id = m.passport_num
WHERE NOT EXISTS (
    SELECT 1 FROM id_mapping_full im
    WHERE im.source_table = 'maids' AND im.source_id = m.id
);

UPDATE full_migration_log SET status = 'completed', completed_at = NOW(), migrated_records = ROW_COUNT()
WHERE table_name = 'cvs' AND status = 'in_progress';

-- =================================================================
-- 7. المرحلة 4: نقل الطلبات
-- =================================================================

INSERT INTO full_migration_log (phase_name, table_name, status, started_at) VALUES ('البيانات الأساسية', 'orders', 'in_progress', NOW());

-- نقل الطلبات
INSERT INTO orders (
    order_code, status, user_id, biography_id, admin_id,
    order_date, order_complete, order_canceled,
    created_at, updated_at
)
SELECT
    o.order_code,
    CASE
        WHEN o.status = 'new' THEN 'new'
        WHEN o.status = 'completed' THEN 'finished'
        WHEN o.status = 'cancelled' THEN 'canceled'
        WHEN o.status = 'pending' THEN 'under_work'
        ELSE 'new'
    END as status,
    um.target_id as user_id,
    cm.target_id as biography_id,
    NULL as admin_id, -- يمكن ربطه لاحقاً
    o.created_at as order_date,
    o.status_changed_at as order_complete,
    CASE WHEN o.status = 'cancelled' THEN o.updated_at ELSE NULL END as order_canceled,
    o.created_at,
    o.updated_at
FROM erp_operations_new.orders o
LEFT JOIN id_mapping_full um ON um.source_table = 'users' AND um.source_id = o.user_id
LEFT JOIN id_mapping_full cm ON cm.source_table = 'maids' AND cm.source_id = o.maid_id
WHERE o.order_code IS NOT NULL
AND um.target_id IS NOT NULL
AND cm.target_id IS NOT NULL
AND NOT EXISTS (
    SELECT 1 FROM orders po
    WHERE po.order_code = o.order_code
);

UPDATE full_migration_log SET status = 'completed', completed_at = NOW(), migrated_records = ROW_COUNT()
WHERE table_name = 'orders' AND status = 'in_progress';

-- =================================================================
-- 8. المرحلة 5: نقل باقي الجداول
-- =================================================================

-- نقل إعدادات التطبيق
INSERT INTO full_migration_log (phase_name, table_name, status, started_at) VALUES ('البيانات المساعدة', 'app_settings', 'in_progress', NOW());

INSERT IGNORE INTO app_settings (setting_key, setting_value, created_at, updated_at)
SELECT
    s.key as setting_key,
    s.value as setting_value,
    s.created_at,
    s.updated_at
FROM erp_operations_new.settings s
WHERE NOT EXISTS (
    SELECT 1 FROM app_settings a
    WHERE a.setting_key = s.key
);

UPDATE full_migration_log SET status = 'completed', completed_at = NOW(), migrated_records = ROW_COUNT()
WHERE table_name = 'app_settings' AND status = 'in_progress';

-- نقل الشكاوى
INSERT INTO full_migration_log (phase_name, table_name, status, started_at) VALUES ('البيانات المساعدة', 'complaints', 'in_progress', NOW());

INSERT INTO complaints (
    name, phone, email, complaint_type_id, subject, message,
    status, user_id, order_id, created_at, updated_at
)
SELECT
    c.name,
    c.phone,
    c.email,
    COALESCE(c.complaint_type_id, 1) as complaint_type_id,
    c.subject,
    c.message,
    CASE
        WHEN c.status = 'new' THEN 'new'
        WHEN c.status = 'resolved' THEN 'resolved'
        WHEN c.status = 'closed' THEN 'closed'
        ELSE 'new'
    END as status,
    um.target_id as user_id,
    -- ربط الطلب إذا أمكن
    (SELECT po.id FROM orders po WHERE po.order_code = c.order_code LIMIT 1) as order_id,
    c.created_at,
    c.updated_at
FROM erp_operations_new.complaints c
LEFT JOIN id_mapping_full um ON um.source_table = 'users' AND um.source_id = c.user_id
WHERE NOT EXISTS (
    SELECT 1 FROM complaints pc
    WHERE pc.email = c.email AND pc.created_at = c.created_at
);

UPDATE full_migration_log SET status = 'completed', completed_at = NOW(), migrated_records = ROW_COUNT()
WHERE table_name = 'complaints' AND status = 'in_progress';

-- نقل أنواع الشكاوى
INSERT IGNORE INTO complaint_types (id, name, created_at, updated_at)
SELECT id, name, created_at, updated_at
FROM erp_operations_new.complaint_types;

-- نقل المهارات
INSERT INTO full_migration_log (phase_name, table_name, status, started_at) VALUES ('البيانات المساعدة', 'skills', 'in_progress', NOW());

INSERT IGNORE INTO skills (id, name, created_at, updated_at)
SELECT s.id,
       COALESCE((SELECT name FROM erp_operations_new.skills_translations WHERE skill_id = s.id AND locale = 'ar' LIMIT 1), 'مهارة') as name,
       s.created_at,
       s.updated_at
FROM erp_operations_new.skills s;

UPDATE full_migration_log SET status = 'completed', completed_at = NOW(), migrated_records = ROW_COUNT()
WHERE table_name = 'skills' AND status = 'in_progress';

-- =================================================================
-- 9. نقل البيانات المترجمة والمحتوى
-- =================================================================

-- نقل About Us
INSERT INTO about_us (id, created_at, updated_at)
SELECT id, created_at, updated_at
FROM erp_operations_new.about_us
WHERE NOT EXISTS (SELECT 1 FROM about_us a WHERE a.id = erp_operations_new.about_us.id);

INSERT INTO about_us_translations (about_us_id, title, description, locale, created_at, updated_at)
SELECT about_us_id, title, description, locale, created_at, updated_at
FROM erp_operations_new.about_us_translations;

-- نقل الإعدادات
INSERT IGNORE INTO general_settings (setting_key, setting_value, created_at, updated_at)
SELECT
    s.key as setting_key,
    s.value as setting_value,
    s.created_at,
    s.updated_at
FROM erp_operations_new.settings s;

-- =================================================================
-- 10. نقل البيانات الخاصة بـ ERP فقط
-- =================================================================

-- نقل سجلات السفر
CREATE TABLE IF NOT EXISTS `travel_histories` AS
SELECT * FROM erp_operations_new.travel_histories;

-- نقل إجراءات الترحيل
INSERT INTO deportation_procedures (
    maid_id, reason, deportation_date, status, notes, created_at, updated_at
)
SELECT
    cm.target_id as maid_id,
    dp.reason,
    dp.deportation_date,
    dp.status,
    dp.notes,
    dp.created_at,
    dp.updated_at
FROM erp_operations_new.deportation_procedures dp
LEFT JOIN id_mapping_full cm ON cm.source_table = 'maids' AND cm.source_id = dp.maid_id
WHERE cm.target_id IS NOT NULL;

-- =================================================================
-- 11. تحديث الإحصائيات والفهارس
-- =================================================================

-- إعادة بناء الفهارس
ANALYZE TABLE users;
ANALYZE TABLE cvs;
ANALYZE TABLE orders;
ANALYZE TABLE addresses;
ANALYZE TABLE customers;

-- تحديث AUTO_INCREMENT لتجنب التضارب
SELECT CONCAT('ALTER TABLE ', table_name, ' AUTO_INCREMENT = ', AUTO_INCREMENT + 1000, ';') as sql_command
FROM information_schema.tables
WHERE table_schema = 'platinum'
AND AUTO_INCREMENT IS NOT NULL;

-- =================================================================
-- 12. إنشاء ملخص النقل
-- =================================================================

-- إدراج ملخص نهائي
INSERT INTO full_migration_log (phase_name, table_name, status, notes, completed_at)
VALUES (
    'ملخص النقل',
    'SUMMARY',
    'completed',
    CONCAT(
        'تم نقل إجمالي ',
        (SELECT SUM(migrated_records) FROM full_migration_log WHERE table_name != 'SUMMARY'),
        ' سجل من ',
        (SELECT COUNT(DISTINCT table_name) FROM full_migration_log WHERE table_name != 'SUMMARY'),
        ' جدول'
    ),
    NOW()
);

-- عرض تقرير النقل النهائي
SELECT
    'تقرير النقل الشامل' as report_title,
    phase_name,
    table_name,
    source_records,
    migrated_records,
    failed_records,
    status,
    TIMESTAMPDIFF(MINUTE, started_at, completed_at) as duration_minutes
FROM full_migration_log
ORDER BY created_at;

-- عرض إحصائيات التطابق
SELECT
    'إحصائيات التطابق' as mapping_title,
    source_table,
    target_table,
    COUNT(*) as mapped_records
FROM id_mapping_full
GROUP BY source_table, target_table;

-- التحقق من سلامة البيانات
SELECT
    'فحص سلامة البيانات' as integrity_check,
    'users' as table_name,
    COUNT(*) as total_records,
    COUNT(DISTINCT email) as unique_emails,
    COUNT(DISTINCT phone) as unique_phones
FROM users
WHERE email IS NOT NULL OR phone IS NOT NULL

UNION ALL

SELECT
    'فحص سلامة البيانات' as integrity_check,
    'cvs' as table_name,
    COUNT(*) as total_records,
    COUNT(DISTINCT passport_id) as unique_passports,
    COUNT(DISTINCT nationality_id) as unique_nationalities
FROM cvs
WHERE passport_id IS NOT NULL

UNION ALL

SELECT
    'فحص سلامة البيانات' as integrity_check,
    'orders' as table_name,
    COUNT(*) as total_records,
    COUNT(DISTINCT order_code) as unique_codes,
    COUNT(DISTINCT user_id) as unique_users
FROM orders
WHERE order_code IS NOT NULL;

-- إنهاء المعاملة
SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

SELECT 'تم إكمال النقل الشامل بنجاح! 🎉' as final_message;
