-- ==================================================================
-- سكريبتات نقل البيانات من erp_operations_new إلى platinum
-- Migration Scripts from erp_operations_new to platinum
-- ==================================================================

-- ==================================================================
-- 1. إنشاء جداول المساعدة والتتبع | Helper & Tracking Tables
-- ==================================================================

-- جدول تتبع عملية النقل
CREATE TABLE IF NOT EXISTS migration_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    operation VARCHAR(50) NOT NULL,
    records_affected INT DEFAULT 0,
    status ENUM('started', 'completed', 'error', 'rollback') DEFAULT 'started',
    error_message TEXT,
    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_time TIMESTAMP NULL,
    notes TEXT
);

-- جدول ربط المعرفات القديمة والجديدة
CREATE TABLE IF NOT EXISTS id_mapping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_table VARCHAR(100) NOT NULL,
    target_table VARCHAR(100) NOT NULL,
    source_id INT NOT NULL,
    target_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_mapping (source_table, target_table, source_id)
);

-- ==================================================================
-- 2. تحليل البيانات الموجودة | Data Analysis
-- ==================================================================

-- إحصائيات قاعدة ERP Operations
SELECT 'ERP_OPERATIONS_ANALYSIS' as analysis_type;

SELECT
    'users' as table_name,
    COUNT(*) as total_records,
    COUNT(DISTINCT email) as unique_emails,
    COUNT(DISTINCT phone) as unique_phones
FROM erp_operations_new.users

UNION ALL

SELECT
    'maids' as table_name,
    COUNT(*) as total_records,
    COUNT(DISTINCT passport_num) as unique_passports,
    COUNT(DISTINCT nationality_id) as unique_nationalities
FROM erp_operations_new.maids

UNION ALL

SELECT
    'contracts' as table_name,
    COUNT(*) as total_records,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(DISTINCT maid_id) as unique_maids
FROM erp_operations_new.contracts

UNION ALL

SELECT
    'orders' as table_name,
    COUNT(*) as total_records,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(DISTINCT maid_id) as unique_maids
FROM erp_operations_new.orders;

-- إحصائيات قاعدة Platinum
SELECT 'PLATINUM_ANALYSIS' as analysis_type;

SELECT
    'users' as table_name,
    COUNT(*) as total_records,
    COUNT(DISTINCT email) as unique_emails,
    COUNT(DISTINCT phone) as unique_phones
FROM platinum.users

UNION ALL

SELECT
    'cvs' as table_name,
    COUNT(*) as total_records,
    COUNT(DISTINCT passport_id) as unique_passports,
    COUNT(DISTINCT national_id) as unique_nationalities
FROM platinum.cvs

UNION ALL

SELECT
    'contracts' as table_name,
    COUNT(*) as total_records,
    COUNT(DISTINCT resv_id) as unique_reservations,
    NULL as unique_maids
FROM platinum.contracts

UNION ALL

SELECT
    'orders' as table_name,
    COUNT(*) as total_records,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(DISTINCT biography_id) as unique_biographies
FROM platinum.orders;

-- ==================================================================
-- 3. المرحلة 1: نقل البيانات المرجعية | Reference Data Migration
-- ==================================================================

-- نقل الجنسيات
INSERT INTO migration_log (table_name, operation, notes)
VALUES ('nationalities', 'start_migration', 'بدء نقل الجنسيات');

-- التحقق من الجنسيات المكررة قبل النقل
SELECT 'NATIONALITIES_DUPLICATES_CHECK' as check_type;
SELECT COUNT(*) as duplicates_count
FROM erp_operations_new.nationalities n1
WHERE EXISTS (
    SELECT 1 FROM platinum.nationalities n2
    WHERE n2.name = (
        SELECT nt.name
        FROM erp_operations_new.nationalities_translations nt
        WHERE nt.nationality_id = n1.id AND nt.locale = 'ar'
        LIMIT 1
    )
);

-- نقل المدن
INSERT INTO migration_log (table_name, operation, notes)
VALUES ('cities', 'start_migration', 'بدء نقل المدن');

-- ==================================================================
-- 4. المرحلة 2: نقل المستخدمين | Users Migration
-- ==================================================================

INSERT INTO migration_log (table_name, operation, notes)
VALUES ('users', 'start_migration', 'بدء نقل المستخدمين');

-- نقل المستخدمين من ERP إلى Platinum
INSERT INTO platinum.users (
    name,
    email,
    phone_verified_at,
    password,
    address,
    country,
    city,
    created_at,
    updated_at
)
SELECT
    CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as name,
    u.email,
    CASE WHEN u.phone IS NOT NULL THEN NOW() ELSE NULL END as phone_verified_at,
    u.password,
    u.address,
    'Saudi Arabia' as country, -- افتراضي
    u.city,
    u.created_at,
    u.updated_at
FROM erp_operations_new.users u
WHERE NOT EXISTS (
    SELECT 1 FROM platinum.users pu
    WHERE pu.email = u.email OR pu.name = CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))
);

-- تسجيل معرفات المستخدمين الجديدة
INSERT INTO id_mapping (source_table, target_table, source_id, target_id)
SELECT
    'users' as source_table,
    'users' as target_table,
    u.id as source_id,
    pu.id as target_id
FROM erp_operations_new.users u
JOIN platinum.users pu ON (pu.email = u.email OR pu.name = CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')))
WHERE NOT EXISTS (
    SELECT 1 FROM id_mapping im
    WHERE im.source_table = 'users' AND im.source_id = u.id
);

-- تحديث سجل النقل
UPDATE migration_log
SET
    status = 'completed',
    end_time = NOW(),
    records_affected = (SELECT COUNT(*) FROM id_mapping WHERE source_table = 'users')
WHERE table_name = 'users' AND operation = 'start_migration' AND status = 'started';

-- ==================================================================
-- 5. المرحلة 3: نقل بيانات العملاء | Customer Data Migration
-- ==================================================================

INSERT INTO migration_log (table_name, operation, notes)
VALUES ('customers', 'start_migration', 'بدء نقل بيانات العملاء');

-- إنشاء سجلات العملاء من المستخدمين المنقولين
INSERT INTO platinum.customers (
    user_id,
    national_identification_number,
    birth_date,
    family_number,
    created_at,
    updated_at
)
SELECT
    pm.target_id as user_id,
    u.national_num as national_identification_number,
    u.birthdate as birth_date,
    '1' as family_number, -- افتراضي
    u.created_at,
    u.updated_at
FROM erp_operations_new.users u
JOIN id_mapping pm ON pm.source_table = 'users' AND pm.source_id = u.id
WHERE NOT EXISTS (
    SELECT 1 FROM platinum.customers pc
    WHERE pc.user_id = pm.target_id
);

-- نقل العناوين
INSERT INTO platinum.addresses (
    user_id,
    address,
    country,
    city,
    phone,
    created_at,
    updated_at
)
SELECT
    pm.target_id as user_id,
    ua.address,
    ua.country,
    ua.city,
    ua.phone,
    ua.created_at,
    ua.updated_at
FROM erp_operations_new.user_addresses ua
JOIN erp_operations_new.users u ON u.id = ua.user_id
JOIN id_mapping pm ON pm.source_table = 'users' AND pm.source_id = u.id
WHERE NOT EXISTS (
    SELECT 1 FROM platinum.addresses pa
    WHERE pa.user_id = pm.target_id AND pa.address = ua.address
);

-- ==================================================================
-- 6. المرحلة 4: نقل العاملات/السير الذاتية | Maids/CVs Migration
-- ==================================================================

INSERT INTO migration_log (table_name, operation, notes)
VALUES ('cvs', 'start_migration', 'بدء نقل السير الذاتية');

-- نقل العاملات كسير ذاتية
INSERT INTO platinum.cvs (
    passport_id,
    salary,
    service_price,
    national_id,
    age_id,
    personal_image,
    cv_file,
    created_at,
    updated_at
)
SELECT
    m.passport_num as passport_id,
    m.salary,
    m.price as service_price,
    m.nationality_id as national_id,
    CASE
        WHEN m.age BETWEEN 18 AND 25 THEN 1
        WHEN m.age BETWEEN 26 AND 35 THEN 2
        WHEN m.age BETWEEN 36 AND 45 THEN 3
        ELSE 4
    END as age_id,
    NULL as personal_image, -- سيحتاج تعديل حسب البيانات المتوفرة
    m.cv_file,
    m.created_at,
    m.updated_at
FROM erp_operations_new.maids m
WHERE m.passport_num IS NOT NULL
AND m.passport_num != ''
AND NOT EXISTS (
    SELECT 1 FROM platinum.cvs pc
    WHERE pc.passport_id = m.passport_num
);

-- تسجيل معرفات السير الذاتية
INSERT INTO id_mapping (source_table, target_table, source_id, target_id)
SELECT
    'maids' as source_table,
    'cvs' as target_table,
    m.id as source_id,
    pc.id as target_id
FROM erp_operations_new.maids m
JOIN platinum.cvs pc ON pc.passport_id = m.passport_num
WHERE NOT EXISTS (
    SELECT 1 FROM id_mapping im
    WHERE im.source_table = 'maids' AND im.source_id = m.id
);

-- ==================================================================
-- 7. المرحلة 5: نقل الطلبات | Orders Migration
-- ==================================================================

INSERT INTO migration_log (table_name, operation, notes)
VALUES ('orders', 'start_migration', 'بدء نقل الطلبات');

-- نقل الطلبات
INSERT INTO platinum.orders (
    order_code,
    status,
    biography_id,
    user_id,
    order_date,
    created_at,
    updated_at
)
SELECT
    o.order_code,
    CASE
        WHEN o.status = 'new' THEN 'new'
        WHEN o.status = 'completed' THEN 'finished'
        WHEN o.status = 'cancelled' THEN 'canceled'
        ELSE 'new'
    END as status,
    cm.target_id as biography_id,  -- من id_mapping للعاملات
    um.target_id as user_id,       -- من id_mapping للمستخدمين
    o.created_at as order_date,
    o.created_at,
    o.updated_at
FROM erp_operations_new.orders o
JOIN id_mapping um ON um.source_table = 'users' AND um.source_id = o.user_id
JOIN id_mapping cm ON cm.source_table = 'maids' AND cm.source_id = o.maid_id
WHERE NOT EXISTS (
    SELECT 1 FROM platinum.orders po
    WHERE po.order_code = o.order_code
);

-- ==================================================================
-- 8. المرحلة 6: نقل العقود | Contracts Migration
-- ==================================================================

INSERT INTO migration_log (table_name, operation, notes)
VALUES ('contracts', 'start_migration', 'بدء نقل العقود');

-- هذا القسم يحتاج تخصيص أكثر حسب متطلبات العمل
-- لأن بنية العقود مختلفة جداً بين النظامين

-- إنشاء حجوزات أولاً (مطلوبة للعقود في Platinum)
-- هذا مثال ويحتاج تعديل حسب منطق العمل

-- ==================================================================
-- 9. التحقق من النتائج | Results Verification
-- ==================================================================

-- مقارنة عدد السجلات المنقولة
SELECT 'MIGRATION_SUMMARY' as report_type;

SELECT
    ml.table_name,
    ml.operation,
    ml.records_affected,
    ml.status,
    ml.start_time,
    ml.end_time,
    TIMESTAMPDIFF(MINUTE, ml.start_time, ml.end_time) as duration_minutes
FROM migration_log ml
ORDER BY ml.start_time;

-- عرض ملخص التطابق
SELECT 'ID_MAPPING_SUMMARY' as report_type;

SELECT
    source_table,
    target_table,
    COUNT(*) as mapped_records
FROM id_mapping
GROUP BY source_table, target_table;

-- ==================================================================
-- 10. سكريبتات التنظيف والصيانة | Cleanup Scripts
-- ==================================================================

-- حذف البيانات المكررة (إن وجدت)
-- DELETE FROM platinum.users WHERE id IN (
--     SELECT * FROM (
--         SELECT u2.id FROM platinum.users u2
--         JOIN platinum.users u1 ON u1.email = u2.email AND u1.id < u2.id
--     ) as duplicates
-- );

-- إعادة تعيين AUTO_INCREMENT
-- ALTER TABLE platinum.users AUTO_INCREMENT = 1;
-- ALTER TABLE platinum.cvs AUTO_INCREMENT = 1;
-- ALTER TABLE platinum.orders AUTO_INCREMENT = 1;

-- ==================================================================
-- 11. سكريبتات التراجع | Rollback Scripts
-- ==================================================================

-- في حالة الحاجة للتراجع عن عملية النقل
/*
-- حذف البيانات المنقولة
DELETE FROM platinum.users WHERE id IN (
    SELECT target_id FROM id_mapping WHERE source_table = 'users'
);

DELETE FROM platinum.cvs WHERE id IN (
    SELECT target_id FROM id_mapping WHERE source_table = 'maids'
);

DELETE FROM platinum.orders WHERE user_id IN (
    SELECT target_id FROM id_mapping WHERE source_table = 'users'
);

-- حذف جداول التتبع
DROP TABLE IF EXISTS migration_log;
DROP TABLE IF EXISTS id_mapping;
*/

-- ==================================================================
-- 12. تحديث الإحصائيات النهائية | Final Statistics Update
-- ==================================================================

-- تحديث إحصائيات قاعدة البيانات
ANALYZE TABLE platinum.users;
ANALYZE TABLE platinum.cvs;
ANALYZE TABLE platinum.orders;
ANALYZE TABLE platinum.customers;
ANALYZE TABLE platinum.addresses;

-- إنشاء تقرير نهائي
INSERT INTO migration_log (table_name, operation, notes)
VALUES ('migration_complete', 'final_report',
    CONCAT('تم إكمال نقل البيانات. عدد المستخدمين المنقولين: ',
           (SELECT COUNT(*) FROM id_mapping WHERE source_table = 'users'),
           ', عدد السير الذاتية المنقولة: ',
           (SELECT COUNT(*) FROM id_mapping WHERE source_table = 'maids')
    )
);

SELECT 'عملية النقل مكتملة! يرجى مراجعة جدول migration_log للحصول على تفاصيل كاملة.' as final_message;
