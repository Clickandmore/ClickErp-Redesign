-- نقل العاملات الأساسي

USE platinum_new;

-- تعطيل فحص Foreign Keys مؤقتاً
SET FOREIGN_KEY_CHECKS = 0;

-- إضافة عمود cv_status بطريقة آمنة
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
               WHERE TABLE_SCHEMA = 'platinum_new'
               AND TABLE_NAME = 'maids'
               AND COLUMN_NAME = 'cv_status');

-- إضافة العمود فقط إذا لم يكن موجوداً
SET @sql := IF(@exist = 0,
    'ALTER TABLE maids ADD COLUMN cv_status TINYINT DEFAULT 0',
    'SELECT "cv_status already exists" as info');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- إضافة سجلات افتراضية للمراجع
INSERT IGNORE INTO nationalities (id, phone_code, created_at) VALUES (1, '+966', NOW());
INSERT IGNORE INTO religions (id, created_at) VALUES (1, NOW());

-- إضافة قيم افتراضية للأعمدة المطلوبة في maids
ALTER TABLE maids MODIFY COLUMN height VARCHAR(255) DEFAULT '160';
ALTER TABLE maids MODIFY COLUMN weight VARCHAR(255) DEFAULT '55';
ALTER TABLE maids MODIFY COLUMN experience_years VARCHAR(255) DEFAULT '0';

-- نقل البيانات الأساسية فقط
INSERT INTO maids (
    id, nationality_id, passport_num, price, salary, religion_id,
    age, cv_file, available, cv_status, status, is_show,
    created_at, updated_at
)
SELECT
    id,
    COALESCE(national_id, 1) as nationality_id,
    COALESCE(passport_id, 'CV_' || id) as passport_num,
    COALESCE(service_price, 0) as price,
    COALESCE(salary, 0) as salary,
    1 as religion_id,
    COALESCE(age_id, 25) as age,
    COALESCE(cv_file, CONCAT('cv_', id, '.pdf')) as cv_file,
    CASE
        WHEN cv_status = 0 THEN 1
        ELSE 0
    END as available,
    COALESCE(cv_status, 0) as cv_status,
    CASE
        WHEN cv_status IN (0, 1) THEN 'available'
        ELSE 'unavailable'
    END as status,
    1 as is_show,
    COALESCE(created_at, NOW()) as created_at,
    COALESCE(updated_at, NOW()) as updated_at
FROM platinum.cvs
ON DUPLICATE KEY UPDATE
    nationality_id = VALUES(nationality_id),
    passport_num = VALUES(passport_num),
    price = VALUES(price),
    salary = VALUES(salary),
    age = VALUES(age),
    cv_file = VALUES(cv_file),
    available = VALUES(available),
    cv_status = VALUES(cv_status),
    status = VALUES(status),
    updated_at = NOW();

-- إعادة تفعيل Foreign Key checks
SET FOREIGN_KEY_CHECKS = 1;

-- عرض النتائج
SELECT
    'نتائج نقل العاملات' as report_title,
    COUNT(*) as total_maids,
    SUM(CASE WHEN cv_status = 0 THEN 1 ELSE 0 END) as غير_محجوز,
    SUM(CASE WHEN cv_status = 1 THEN 1 ELSE 0 END) as محجوز,
    SUM(CASE WHEN cv_status = 2 THEN 1 ELSE 0 END) as باك_آوت,
    SUM(CASE WHEN cv_status = 3 THEN 1 ELSE 0 END) as معلق,
    SUM(CASE WHEN cv_status = 4 THEN 1 ELSE 0 END) as تم_الترحيل,
    SUM(CASE WHEN cv_status = 5 THEN 1 ELSE 0 END) as هروب
FROM maids;

SELECT 'تم الحفاظ على cv_status بدقة!' as cv_status_message;
