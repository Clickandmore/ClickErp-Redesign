-- إصلاح مباشر لجدول nationalities

USE platinum_new;

-- تعطيل فحص Foreign Keys
SET FOREIGN_KEY_CHECKS = 0;

-- مسح البيانات الحالية
DELETE FROM nationalities;

-- إدراج البيانات الأساسية المطلوبة للعاملات (حسب الـ IDs المستخدمة)
INSERT INTO nationalities (id, phone_code, logo, created_at, updated_at) VALUES
(1, '+966', 'saudi.png', NOW(), NOW()),
(18, '+63', 'philippines.png', NOW(), NOW()),
(100, '+254', 'kenya.png', NOW(), NOW()),
(113, '+256', 'uganda.png', NOW(), NOW()),
(150, '+251', 'ethiopia.png', NOW(), NOW()),
(174, '+880', 'bangladesh.png', NOW(), NOW()),
(299, '+94', 'sri_lanka.png', NOW(), NOW()),
(300, '+977', 'nepal.png', NOW(), NOW()),
(302, '+20', 'egypt.png', NOW(), NOW());

-- نقل من القاعدة القديمة مع التحويلات الصحيحة
INSERT IGNORE INTO nationalities (id, phone_code, logo, created_at, updated_at)
SELECT
    id,
    CASE
        WHEN code = 'SA' THEN '+966'
        WHEN code = 'PH' THEN '+63'
        WHEN code = 'KE' THEN '+254'
        WHEN code = 'UG' THEN '+256'
        WHEN code = 'ET' THEN '+251'
        WHEN code = 'BD' THEN '+880'
        WHEN code = 'LK' THEN '+94'
        WHEN code = 'NP' THEN '+977'
        WHEN code = 'EG' THEN '+20'
        ELSE '+966'
    END as phone_code,
    COALESCE(flag_image, CONCAT('flag_', id, '.png')) as logo,
    COALESCE(created_at, NOW()) as created_at,
    COALESCE(updated_at, NOW()) as updated_at
FROM platinum.nationalities
ON DUPLICATE KEY UPDATE
    phone_code = VALUES(phone_code),
    logo = VALUES(logo),
    updated_at = NOW();

-- إعادة تفعيل Foreign Keys
SET FOREIGN_KEY_CHECKS = 1;

-- فحص النتيجة
SELECT 'إصلاح nationalities مكتمل' as status;

SELECT
    COUNT(*) as total_nationalities,
    MIN(id) as min_id,
    MAX(id) as max_id,
    GROUP_CONCAT(id ORDER BY id) as available_ids
FROM nationalities;

-- فحص العاملات التي تحتاج هذه الـ IDs
SELECT
    'فحص العاملات' as check_type,
    COUNT(*) as total_maids,
    COUNT(DISTINCT nationality_id) as unique_nationalities_used
FROM maids;

SELECT 'جاهز لنقل العاملات!' as ready_status;
