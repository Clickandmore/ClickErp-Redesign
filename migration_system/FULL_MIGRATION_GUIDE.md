# دليل النقل الشامل للبيانات
# Complete Data Migration Guide

---

## 🎯 **الهدف:**
نقل جميع البيانات من قاعدة `erp_operations_new` إلى قاعدة `platinum` مع إضافة الجداول والحقول المفقودة تلقائياً.

---

## 📋 **ما سيتم نقله:**

### **📊 الإحصائيات العامة:**
- **87 جدول** من ERP Operations  
- **109 جدول** في Platinum (سيتم إضافة المفقود)
- **أكثر من 50,000 سجل** تقديرياً
- **6 مراحل** متدرجة للنقل

### **🗂️ أنواع البيانات:**

#### **1. البيانات المرجعية:**
- `countries` - البلدان
- `cities` - المدن  
- `nationalities` - الجنسيات
- `religions` - الأديان

#### **2. المستخدمون والعملاء:**
- `users` - المستخدمون ← `users` + `customers`
- `user_addresses` - العناوين ← `addresses`
- `admins` - الإداريون ← `staff`

#### **3. العاملات والسير الذاتية:**
- `maids` - العاملات ← `cvs` (السير الذاتية)
- `maid_jobs` - وظائف العاملات
- `maid_translations` - ترجمات بيانات العاملات

#### **4. الطلبات والعقود:**
- `orders` - الطلبات ← `orders`
- `contracts` - العقود ← `contracts` (مع تعديل البنية)
- `order_details` - تفاصيل الطلبات

#### **5. المحتوى والإعدادات:**
- `about_us` + `about_us_translations` - معلومات عنا
- `sliders` + `sliders_translations` - الشرائح
- `services` + `services_translations` - الخدمات
- `settings` - الإعدادات ← `app_settings`

#### **6. البيانات المساعدة:**
- `complaints` + `complaint_types` - الشكاوى
- `skills` - المهارات
- `notifications` - الإشعارات
- `permissions` + `roles` - الصلاحيات والأدوار

---

## 🔧 **التحسينات والإضافات:**

### **📝 الجداول الجديدة (ستُنشأ في Platinum):**
- `about_us` + `about_us_translations`
- `complaints` + `complaint_types`
- `deportation_procedures`
- `travel_histories`
- `sponsorship_transfers`
- `rental_contracts`

### **⚡ الحقول الجديدة (ستُضاف للجداول الموجودة):**

#### **في جدول `users`:**
- `first_name` - الاسم الأول
- `last_name` - الاسم الأخير  
- `national_num` - رقم الهوية
- `phone2` - هاتف إضافي
- `birthdate` - تاريخ الميلاد
- `register_from` - مصدر التسجيل
- `is_blocked` - محظور أم لا
- `status` - حالة الحساب

#### **في جدول `cvs`:**
- `name` - اسم العاملة
- `nationality_id` - الجنسية
- `religion_id` - الديانة
- `birth_date` - تاريخ الميلاد
- `available` - متاحة للعمل
- `is_pinned` - مثبتة
- `available_at` - تاريخ الإتاحة
- `status` - الحالة
- `experience_years` - سنوات الخبرة
- `experience_status` - حالة الخبرة

---

## 🚀 **طرق التنفيذ:**

### **1. 🌐 عبر الواجهة (سهل):**
```
http://127.0.0.1:8002/?page=full_migration
```

#### **المزايا:**
- واجهة سهلة الاستخدام
- مراقبة مباشرة للتقدم
- خيارات متقدمة للتحكم
- معاينة خطة النقل قبل البدء

#### **الخطوات:**
1. افتح الرابط
2. اختر خيارات النقل
3. انقر "معاينة خطة النقل" للمراجعة
4. انقر "بدء النقل الشامل"
5. راقب التقدم

### **2. ⚡ عبر سطر الأوامر (متقدم):**
```bash
cd /Applications/MAMP/htdocs/ERP_V/migration_system
./run_full_migration.sh full
```

#### **المزايا:**
- سرعة أكبر
- تشغيل في الخلفية
- سجلات مفصلة
- تحكم كامل

#### **الأوامر المتاحة:**
```bash
./run_full_migration.sh check    # فحص قواعد البيانات
./run_full_migration.sh backup   # نسخة احتياطية فقط
./run_full_migration.sh migrate  # النقل فقط
./run_full_migration.sh verify   # فحص النتائج
./run_full_migration.sh full     # كامل (مُوصى)
```

### **3. 🔧 يدوياً (للمطورين):**
```bash
# تشغيل سكريبت SQL مباشرة
mysql -h localhost -P 8889 -u root -proot platinum < complete_migration_script.sql
```

---

## ⚠️ **متطلبات مهمة قبل البدء:**

### **✅ قائمة التحقق:**
- [ ] قاعدة البيانات `erp_operations_new` محملة
- [ ] قاعدة البيانات `platinum` محملة  
- [ ] قاعدة البيانات `platinum_migration` جاهزة
- [ ] MAMP يعمل على البورت 8889
- [ ] مساحة قرص كافية (أكثر من 1GB)
- [ ] نسخة احتياطية حديثة

### **🛠️ إعداد قواعد البيانات:**
```bash
# إنشاء قاعدة ERP Operations
mysql -h localhost -P 8889 -u root -proot -e "CREATE DATABASE erp_operations_new;"
mysql -h localhost -P 8889 -u root -proot erp_operations_new < database/backUp/erp_operations_back_Up.sql

# إنشاء قاعدة Platinum  
mysql -h localhost -P 8889 -u root -proot -e "CREATE DATABASE platinum;"
mysql -h localhost -P 8889 -u root -proot platinum < database/backUp/platinum_back_up.sql

# إنشاء قاعدة إدارة النقل
mysql -h localhost -P 8889 -u root -proot -e "CREATE DATABASE platinum_migration;"
mysql -h localhost -P 8889 -u root -proot platinum_migration < simple_setup.sql
```

---

## ⏱️ **الجدول الزمني المتوقع:**

| **المرحلة** | **الوقت المقدر** | **الوصف** |
|-------------|-----------------|-----------|
| التحضير والنسخ الاحتياطي | 5-10 دقائق | إعداد البيئة |
| البيانات المرجعية | 2-5 دقائق | البلدان والمدن |
| المستخدمون والعملاء | 10-20 دقيقة | آلاف المستخدمين |
| العاملات والسير الذاتية | 15-30 دقيقة | تحويل معقد |
| الطلبات والعقود | 10-20 دقيقة | ربط المعرفات |
| البيانات المساعدة | 5-15 دقيقة | الباقي |
| **الإجمالي** | **47-100 دقيقة** | **تقدير شامل** |

---

## 📊 **مراقبة التقدم:**

### **🌐 عبر الواجهة:**
- تحديث مباشر للتقدم
- إحصائيات في الوقت الفعلي
- إمكانية الإيقاف المؤقت

### **📋 عبر السجلات:**
```bash
# مراقبة السجل المباشر
tail -f /tmp/migration_progress.log

# فحص سجل النظام
tail -f /tmp/migration_server.log
```

### **🗃️ قاعدة البيانات:**
```sql
-- فحص حالة النقل
SELECT * FROM platinum_migration.full_migration_log ORDER BY started_at DESC;

-- إحصائيات سريعة
SELECT 
    phase_name,
    COUNT(*) as tables_count,
    SUM(migrated_records) as total_records,
    AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_duration_minutes
FROM platinum_migration.full_migration_log 
WHERE status = 'completed'
GROUP BY phase_name;
```

---

## 🔍 **التحقق من النتائج:**

### **📈 إحصائيات النقل:**
```sql
-- مقارنة عدد السجلات
SELECT 'ERP Operations' as source, 
       'users' as table_name, 
       COUNT(*) as record_count 
FROM erp_operations_new.users
UNION ALL
SELECT 'Platinum', 'users', COUNT(*) FROM platinum.users;
```

### **🔗 فحص التطابقات:**
```sql
-- فحص ربط المعرفات
SELECT 
    source_table,
    target_table,
    COUNT(*) as mapped_count
FROM platinum_migration.id_mapping_full
GROUP BY source_table, target_table;
```

---

## 🚨 **استكشاف الأخطاء:**

### **❌ مشاكل شائعة وحلولها:**

#### **خطأ: "قاعدة البيانات غير موجودة"**
```bash
# الحل: إنشاء قواعد البيانات
./run_full_migration.sh check
```

#### **خطأ: "نفاد المساحة"**
```bash
# الحل: تنظيف المساحة أو زيادتها
df -h
sudo rm -rf /tmp/*
```

#### **خطأ: "انقطع الاتصال"**
```bash
# الحل: إعادة تشغيل MAMP وإعادة المحاولة
./run_full_migration.sh verify  # فحص ما تم نقله
```

#### **خطأ: "تضارب في المعرفات"**
```sql
-- الحل: تحديث AUTO_INCREMENT
ALTER TABLE users AUTO_INCREMENT = 10000;
ALTER TABLE cvs AUTO_INCREMENT = 10000;
```

---

## 🔄 **الاستعادة والتراجع:**

### **📦 استعادة من النسخة الاحتياطية:**
```bash
# العثور على النسخة الاحتياطية
ls -la backups/

# الاستعادة
mysql -h localhost -P 8889 -u root -proot platinum < backups/platinum_pre_migration_YYYYMMDD_HHMMSS.sql
```

### **🗑️ تنظيف النقل الفاشل:**
```sql
-- حذف البيانات المنقولة جزئياً
DELETE FROM platinum.users WHERE register_from = 'migration';
DELETE FROM platinum.cvs WHERE cv_rev_type = 1;

-- إعادة تعيين العدادات
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE cvs AUTO_INCREMENT = 1;
```

---

## 🏁 **بدء النقل الآن:**

### **🎯 الطريقة الموصى بها (واجهة ويب):**

1. **افتح الرابط:**
   ```
   http://127.0.0.1:8002/?page=full_migration
   ```

2. **اتبع الخطوات:**
   - اختر خيارات النقل
   - فعّل إنشاء النسخة الاحتياطية
   - انقر "معاينة خطة النقل"
   - راجع التفاصيل
   - انقر "بدء النقل الشامل"

3. **راقب التقدم:**
   - ستظهر شاشة مراقبة مباشرة
   - يمكنك الإيقاف المؤقت إذا لزم الأمر
   - ستحصل على تقرير نهائي مفصل

### **⚡ الطريقة السريعة (سطر الأوامر):**

```bash
cd /Applications/MAMP/htdocs/ERP_V/migration_system
./run_full_migration.sh full
```

---

## 📞 **الدعم والمساعدة:**

### **📋 قبل الاتصال بالدعم:**
1. راجع ملف السجل: `/tmp/migration_progress.log`
2. فحص قواعد البيانات: `./run_full_migration.sh check`
3. تأكد من تشغيل MAMP
4. تأكد من وجود مساحة كافية

### **📊 معلومات مفيدة للدعم:**
- إصدار PHP: `php --version`
- حالة MAMP: `ps aux | grep -i mamp`
- استخدام القرص: `df -h`
- سجل النقل: محتويات `/tmp/migration_progress.log`

---

## 🎉 **بعد إكمال النقل:**

### **✅ التحقق من النجاح:**
1. **فحص عدد السجلات:** تطابق الأعداد بين القواعد
2. **اختبار تسجيل الدخول:** للمستخدمين المنقولين
3. **فحص البيانات:** صحة البيانات المنقولة
4. **اختبار الميزات:** عمل الطلبات والبحث

### **📋 خطوات ما بعد النقل:**
1. **تحديث الفهارس:** `ANALYZE TABLE` لجميع الجداول
2. **ضبط AUTO_INCREMENT:** لتجنب تضارب المعرفات
3. **اختبار شامل:** لجميع ميزات النظام
4. **توثيق التغييرات:** حفظ تقرير النقل

---

## 🔑 **نصائح للنجاح:**

### **✅ قبل البدء:**
- تأكد من استقرار الشبكة
- أغلق التطبيقات الأخرى الثقيلة
- تأكد من عدم انقطاع الكهرباء
- احتفظ بنسخة من قواعد البيانات الأصلية

### **⏳ أثناء النقل:**
- لا تغلق المتصفح أو الطرفية
- راقب استخدام الذاكرة والقرص
- لا تشغل عمليات أخرى على قاعدة البيانات
- كن صبوراً - العملية تحتاج وقت

### **🎯 بعد النقل:**
- احتفظ بالنسخ الاحتياطية لفترة
- اختبر النظام بشكل شامل
- وثق أي مشاكل أو ملاحظات
- درب المستخدمين على النظام الجديد

---

**🚀 ابدأ النقل الآن والاستمتع بنظامك المطور! 🎉**
