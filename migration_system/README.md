# نظام نقل البيانات | Data Migration System

نظام شامل لنقل البيانات من قاعدة البيانات القديمة (ERP Operations) إلى قاعدة البيانات الجديدة (Platinum) مع واجهة مستخدم سهلة الاستخدام.

## 🌟 المزايا الرئيسية

- **واجهة مستخدم باللغة العربية** مع دعم كامل للـ RTL
- **رفع ملفات CSV** مع معاينة البيانات قبل النقل
- **نظام تحقق متقدم** من صحة البيانات
- **مراقبة مباشرة** لعمليات النقل مع شريط التقدم
- **إدارة الأخطاء** مع تسجيل مفصل وإمكانية التراجع
- **ربط تلقائي للمعرفات** بين قواعد البيانات
- **تقارير وإحصائيات شاملة**

## 📋 المتطلبات

### متطلبات الخادم
- PHP 7.4 أو أحدث
- MySQL 5.7 أو أحدث
- Apache/Nginx مع mod_rewrite
- 128MB+ من الذاكرة
- 500MB+ من مساحة القرص

### المكتبات المطلوبة
- PDO MySQL Extension
- JSON Extension
- mbstring Extension
- fileinfo Extension

### اختياري (لدعم ملفات Excel)
- PhpSpreadsheet Library

## 🚀 التثبيت

### 1. إعداد قاعدة البيانات

```bash
# إنشاء قاعدة البيانات
mysql -u root -p -e "CREATE DATABASE platinum_migration CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# تشغيل سكريبت الإعداد
mysql -u root -p platinum_migration < database_setup.sql
```

### 2. تهيئة إعدادات الاتصال

عدّل ملف `config/database.php`:

```php
'migration' => [
    'host' => 'localhost',
    'username' => 'your_username',
    'password' => 'your_password',
    'database' => 'platinum_migration',
    'charset' => 'utf8mb4',
    'port' => 3306
],
```

### 3. ضبط الصلاحيات

```bash
# إعطاء صلاحيات للملفات
chmod -R 755 migration_system/
chmod -R 777 migration_system/uploads/
chmod -R 777 migration_system/logs/
```

### 4. اختبار النظام

افتح المتصفح وانتقل إلى: `http://your-domain/migration_system/`

## 📁 هيكل المشروع

```
migration_system/
├── index.php                 # الصفحة الرئيسية
├── config/
│   └── database.php         # إعدادات قاعدة البيانات
├── includes/
│   └── functions.php        # الوظائف المساعدة
├── pages/
│   ├── dashboard.php        # لوحة التحكم
│   ├── upload.php          # رفع الملفات
│   ├── batches.php         # دفعات النقل
│   ├── mapping.php         # ربط المعرفات
│   ├── reports.php         # التقارير
│   └── settings.php        # الإعدادات
├── api/
│   ├── upload_handler.php  # معالج رفع الملفات
│   ├── get_batch_status.php # حالة الدفعات
│   ├── get_batch_details.php # تفاصيل الدفعات
│   └── batch_actions.php   # عمليات الدفعات
├── processors/
│   └── batch_processor.php # معالج البيانات الأساسي
├── assets/
│   ├── css/style.css       # الأنماط المخصصة
│   └── js/app.js          # JavaScript الرئيسي
├── templates/
│   ├── users.csv          # قالب المستخدمين
│   ├── maids.csv          # قالب العاملات
│   └── orders.csv         # قالب الطلبات
├── uploads/               # ملفات مرفوعة (تُنشأ تلقائياً)
└── logs/                 # ملفات السجلات (تُنشأ تلقائياً)
```

## 💡 كيفية الاستخدام

### 1. رفع ملف البيانات

1. انتقل إلى صفحة "رفع الملفات"
2. اختر نوع البيانات (مستخدمين، عاملات، طلبات)
3. اسحب الملف أو انقر لاختياره
4. اضبط خيارات المعالجة
5. انقر "بدء النقل"

### 2. مراقبة العملية

- راقب التقدم في الوقت الفعلي
- اعرض تفاصيل الدفعات والأخطاء
- تابع الإحصائيات في لوحة التحكم

### 3. إدارة التطابقات

- اعرض ربط المعرفات بين القواعد
- تصدير تقارير التطابق
- حذف التطابقات عند الحاجة

## 📊 أنواع البيانات المدعومة

### المستخدمون (users → users + customers)
- الحقول المطلوبة: name, email, phone
- الحقول الاختيارية: password, address, city, country

### العاملات (maids → cvs)
- الحقول المطلوبة: passport_id, salary, national_id
- الحقول الاختيارية: age_id, skills, personal_image

### الطلبات (orders → orders)
- الحقول المطلوبة: order_code, user_id, biography_id
- الحقول الاختيارية: status, order_date

## 🔧 الإعدادات المتقدمة

### تخصيص حجم الدفعة

```php
// في ملف migration_settings
'batch_size' => 1000  // عدد السجلات في الدفعة الواحدة
```

### تخصيص حجم الملف الأقصى

```php
'max_file_size' => 10485760  // 10MB
```

### تخصيص قواعد التحقق

```sql
INSERT INTO validation_rules (table_name, field_name, rule_type, rule_value, error_message) 
VALUES ('users', 'custom_field', 'regex', '^[a-zA-Z]+$', 'الحقل يجب أن يحتوي على أحرف فقط');
```

## 🚨 استكشاف الأخطاء

### مشاكل شائعة وحلولها

#### خطأ في الاتصال بقاعدة البيانات
```
الحل: تأكد من صحة بيانات الاتصال في config/database.php
```

#### خطأ في رفع الملف
```
الحل: تأكد من صلاحيات مجلد uploads/ (777)
```

#### بطء في المعالجة
```
الحل: قلل حجم الدفعة في الإعدادات أو زيد memory_limit في PHP
```

#### أخطاء في التحقق من البيانات
```
الحل: راجع قواعد التحقق في جدول validation_rules
```

### تفعيل وضع التطوير (Debug Mode)

```php
// في أعلى index.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### عرض السجلات التفصيلية

```bash
# مراقبة ملف السجل
tail -f logs/activity.log
```

## 🔒 الأمان

### حماية الملفات الحساسة

```apache
# في ملف .htaccess
<Files "*.php">
    Require all granted
</Files>

<FilesMatch "\.(log|sql)$">
    Require all denied
</FilesMatch>
```

### تنظيف الملفات المؤقتة

يتم حذف الملفات المرفوعة تلقائياً بعد 7 أيام. لحذف يدوي:

```sql
-- حذف البيانات المؤقتة القديمة
DELETE FROM temp_import_data WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
```

## 📈 الأداء والتحسين

### نصائح لتحسين الأداء

1. **استخدم دفعات صغيرة** (500-1000 سجل) لتجنب timeout
2. **قم بالنسخ الاحتياطي** قبل النقل الكبير
3. **شغّل النقل في ساعات الهدوء** لتجنب الحمل على الخادم
4. **راقب استخدام الذاكرة** وزد memory_limit عند الحاجة

### مراقبة الأداء

```sql
-- عرض إحصائيات الأداء
SELECT 
    AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_duration_minutes,
    MAX(successful_records) as max_records_per_batch,
    COUNT(*) as total_batches
FROM migration_batches 
WHERE status = 'completed' 
AND started_at IS NOT NULL;
```

## 🔄 النسخ الاحتياطي والاستعادة

### إنشاء نسخة احتياطية

```bash
# نسخة احتياطية من البيانات
mysqldump -u root -p platinum > backup_$(date +%Y%m%d_%H%M%S).sql

# نسخة احتياطية من ملفات النظام
tar -czf migration_system_backup.tar.gz migration_system/
```

### استعادة البيانات

```bash
# استعادة قاعدة البيانات
mysql -u root -p platinum < backup_20250913_120000.sql
```

## 📞 الدعم والمساعدة

### معلومات التواصل

- **المطور**: فريق التطوير
- **البريد الإلكتروني**: support@example.com
- **الهاتف**: +966 XX XXX XXXX

### تسجيل المشاكل

عند مواجهة مشكلة، يرجى توفير:
1. وصف تفصيلي للمشكلة
2. خطوات إعادة الإنتاج
3. رسائل الخطأ (إن وجدت)
4. ملف السجل الحالي

## 📋 قائمة المراجعة قبل الإنتاج

- [ ] اختبار جميع أنواع الملفات المدعومة
- [ ] التأكد من صحة قواعد التحقق
- [ ] فحص صلاحيات الملفات والمجلدات
- [ ] إجراء نسخة احتياطية من البيانات
- [ ] اختبار عملية الاستعادة
- [ ] تحديث معلومات الاتصال
- [ ] إعداد مراقبة النظام
- [ ] تدريب المستخدمين

## 📝 سجل التغييرات

### الإصدار 1.0.0 (2025-09-13)
- إطلاق النسخة الأولى
- دعم ملفات CSV
- نظام التحقق من البيانات
- واجهة مستخدم باللغة العربية
- تقارير وإحصائيات شاملة

---

**تم تطويره بواسطة**: فريق التطوير  
**تاريخ آخر تحديث**: 13 سبتمبر 2025  
**الإصدار**: 1.0.0
