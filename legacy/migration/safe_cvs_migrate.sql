-- نقل العاملات الآمن مع الأعمدة الموجودة فقط

-- تعطيل فحص Foreign Keys مؤقتاً
SET FOREIGN_KEY_CHECKS = 0;

-- إضافة عمود cv_status إذا لم يكن موجوداً
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'platinum_new' AND TABLE_NAME = 'maids' AND COLUMN_NAME = 'cv_status');
SET @sqlstmt := IF(@exist = 0, 'ALTER TABLE platinum_new.maids ADD COLUMN cv_status TINYINT DEFAULT 0', 'SELECT 1');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- إضافة سجلات افتراضية للجداول المرجعية
INSERT IGNORE INTO platinum_new.nationalities (id, phone_code, created_at) VALUES (1, '+966', NOW());
INSERT IGNORE INTO platinum_new.religions (id, created_at) VALUES (1, NOW());

-- نقل البيانات من cvs إلى maids (الأعمدة الأساسية فقط)
INSERT INTO platinum_new.maids (
    id, nationality_id, passport_num, price, salary, religion_id,
    age, cv_file, available, cv_status, status, is_show,
    created_at, updated_at, experience_years, experience_status,
    height, weight, image
)
SELECT
    id,
    COALESCE(national_id, 1) as nationality_id,
    COALESCE(passport_id, 'غير محدد') as passport_num,
    COALESCE(service_price, 0) as price,
    COALESCE(salary, 0) as salary,
    1 as religion_id,
    COALESCE(age_id, 25) as age,
    COALESCE(cv_file, 'default_cv.pdf') as cv_file,
    COALESCE(available, 1) as available,
    COALESCE(cv_status, 0) as cv_status,
    'available' as status,
    1 as is_show,
    COALESCE(created_at, NOW()) as created_at,
    COALESCE(updated_at, NOW()) as updated_at,
    '0' as experience_years,
    'no_experience' as experience_status,
    '160' as height,
    '55' as weight,
    COALESCE(images, '') as image
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
    'نقل العاملات مكتمل!' as message,
    COUNT(*) as total_maids,
    SUM(CASE WHEN cv_status = 0 THEN 1 ELSE 0 END) as غير_محجوز,
    SUM(CASE WHEN cv_status = 1 THEN 1 ELSE 0 END) as محجوز,
    SUM(CASE WHEN cv_status = 2 THEN 1 ELSE 0 END) as باك_آوت,
    SUM(CASE WHEN cv_status = 3 THEN 1 ELSE 0 END) as معلق,
    SUM(CASE WHEN cv_status = 4 THEN 1 ELSE 0 END) as تم_الترحيل,
    SUM(CASE WHEN cv_status = 5 THEN 1 ELSE 0 END) as هروب,
    SUM(available) as متاح_للحجز,
    COUNT(*) - SUM(available) as غير_متاح
FROM platinum_new.maids;

SELECT 'تم الحفاظ على جميع حالات cv_status!' as status;

