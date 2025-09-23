# تحليل قواعد البيانات ومقارنتها
## Database Analysis & Comparison Report

---

## 📊 معلومات عامة | General Information

### قاعدة البيانات الأولى: `erp_operations_new`
- **المصدر**: `erp_operations_back_Up.sql`
- **عدد الجداول**: 87 جدول
- **حجم البيانات**: 7,008 أسطر
- **تاريخ التصدير**: 13 سبتمبر 2025، 6:31 صباحاً
- **نوع النظام**: نظام ERP للعمليات والاستقدام

### قاعدة البيانات الثانية: `platinum`
- **المصدر**: `platinum_back_up.sql`
- **عدد الجداول**: 109 جدول
- **حجم البيانات**: 21,193 أسطر
- **تاريخ التصدير**: 13 سبتمبر 2025، 6:30 صباحاً
- **نوع النظام**: نظام Platinum للاستقدام والإدارة

---

## 🔍 تحليل البنية الأساسية | Core Structure Analysis

### الجداول الرئيسية المتطابقة وظيفياً | Functionally Similar Tables

| **قاعدة ERP Operations** | **قاعدة Platinum** | **الوظيفة** | **ملاحظات** |
|--------------------------|---------------------|-------------|-------------|
| `users` | `users` | المستخدمين الأساسيين | بنية مختلفة في الحقول |
| `users` + `user_addresses` | `customers` + `addresses` | بيانات العملاء | توزيع مختلف للحقول |
| `maids` | `cvs` | بيانات العاملات/السير الذاتية | أسماء وحقول مختلفة |
| `contracts` | `contracts` | العقود | بنية مختلفة كلياً |
| `orders` | `orders` | الطلبات | حقول مختلفة |
| `admins` | `staff` (تقريباً) | الإداريين | مفهوم مختلف |
| `activity_log` | `activity_log` | سجل الأنشطة | متطابق نسبياً |

### الجداول الخاصة بقاعدة ERP Operations فقط

**جداول الإعدادات والمحتوى:**
- `about_us` + `about_us_translations`
- `sliders` + `sliders_translations`
- `services` + `services_translations`
- `settings` + `settings_translations`
- `steps` + `steps_translations`

**جداول العمليات الإضافية:**
- `cancel_contract_requests`
- `contract_return_requests`
- `deportation_procedures` + `deportation_procedure_logs`
- `maid_unavailable_records`
- `rental_contracts` + `rental_contract_infos`
- `sponsorship_transfers`
- `travel_histories` + `travel_history_files`

**جداول الشكاوى والتواصل:**
- `complaints` + `complaint_feedbacks` + `complaint_types`
- `contacts`

### الجداول الخاصة بقاعدة Platinum فقط

**جداول إضافية للإدارة:**
- `accommodation_types`
- `blogs` + `blog_categories`
- `brands` + `brand_translations`
- `business_settings`
- `companies`
- `currencies`

**جداول العمليات المتقدمة:**
- `backout` (تراجع العمالة)
- `deportation_cvs`
- `escape_data` (بيانات الهروب)
- `external_requests` + steps + types
- `financial_requests` + steps + types
- `lodging_notes` (ملاحظات الإيواء)

**جداول التدريب والمراجع:**
- `trainees` + `training`
- `recruitment_references`
- `recruitment_requirements` + details

---

## 🔄 خريطة التطابق المقترحة | Proposed Mapping

### 1. المستخدمون والعملاء | Users & Customers

```sql
-- من ERP Operations إلى Platinum
users (ERP) → users (Platinum) + customers (Platinum)
user_addresses (ERP) → addresses (Platinum)
```

**حقول التطابق:**
- `users.name` → `users.name`
- `users.phone` → `users.phone` (تنسيق مختلف)
- `users.email` → `users.email`
- `users.national_num` → `customers.national_identification_number`

### 2. العاملات والسير الذاتية | Maids & CVs

```sql
-- من ERP Operations إلى Platinum
maids (ERP) → cvs (Platinum)
```

**حقول التطابق:**
- `maids.name` → استخراج من `cvs.images` أو حقل منفصل
- `maids.passport_num` → `cvs.passport_id`
- `maids.salary` → `cvs.salary`
- `maids.nationality_id` → `cvs.national_id`
- `maids.age` → `cvs.age_id` (نظام مختلف)
- `maids.cv_file` → `cvs.cv_file`

### 3. العقود | Contracts

```sql
-- من ERP Operations إلى Platinum  
contracts (ERP) → contracts (Platinum) + orders (Platinum)
```

**التحديات:**
- بنية مختلفة كلياً
- `contracts.maid_id` → يحتاج ربط مع `orders.biography_id`
- `contracts.user_id` → `orders.user_id`

### 4. الطلبات | Orders

```sql
-- من ERP Operations إلى Platinum
orders (ERP) → orders (Platinum) (مع تعديل الحقول)
```

**حقول التطابق:**
- `orders.order_code` → `orders.order_code`
- `orders.user_id` → `orders.user_id`
- `orders.maid_id` → `orders.biography_id`
- `orders.status` → `orders.status` (قيم مختلفة)

---

## ⚠️ التحديات الرئيسية | Major Challenges

### 1. اختلاف المفاهيم الأساسية
- **ERP**: يركز على `maids` كعاملات
- **Platinum**: يركز على `cvs` كسير ذاتية

### 2. اختلاف أنظمة الترقيم
- **ERP**: يستخدم `bigint UNSIGNED` للمفاتيح
- **Platinum**: يستخدم `int` للمفاتيح

### 3. اختلاف حالات الطلبات والعقود
- **ERP Status**: `new`, `pending`, `completed`, إلخ
- **Platinum Status**: `new`, `contract`, `under_work`, `musaned`, `traning`, `visa`, `finished`, `canceled`

### 4. البيانات المترجمة
- **ERP**: جداول منفصلة للترجمات (`_translations`)
- **Platinum**: قد تكون في جدول `translations` واحد

---

## 📋 خطة العمل المقترحة | Proposed Action Plan

### المرحلة 1: التحضير | Preparation Phase

1. **إنشاء جداول التطابق (Mapping Tables)**
   ```sql
   CREATE TABLE migration_mapping (
       source_table VARCHAR(50),
       target_table VARCHAR(50),
       source_id INT,
       target_id INT,
       migration_status ENUM('pending', 'completed', 'error'),
       created_at TIMESTAMP
   );
   ```

2. **تحليل البيانات الفعلية**
   - فحص عدد السجلات في كل جدول
   - تحديد البيانات المفقودة أو المتضاربة
   - إنشاء تقارير الجودة

### المرحلة 2: إعداد البيئة | Environment Setup

1. **إنشاء قاعدة بيانات مؤقتة للاختبار**
2. **تطوير سكريبتات التحويل**
3. **إعداد نظام السجلات (Logging)**

### المرحلة 3: التنفيذ | Implementation Phase

**أولوية النقل:**
1. الجداول المرجعية (Countries, Cities, Nationalities)
2. المستخدمون والعملاء
3. العاملات/السير الذاتية
4. الطلبات
5. العقود
6. البيانات المساعدة

### المرحلة 4: التحقق والاختبار | Verification & Testing

1. **التحقق من سلامة البيانات**
2. **اختبار العمليات الأساسية**
3. **مراجعة التطابق**

---

## 💾 السكريبتات المطلوبة | Required Scripts

### 1. سكريبت التحليل الأولي
```sql
-- إحصائيات البيانات
SELECT 'users' as table_name, COUNT(*) as record_count FROM users
UNION ALL
SELECT 'maids', COUNT(*) FROM maids
UNION ALL
SELECT 'contracts', COUNT(*) FROM contracts;
```

### 2. سكريبت تحضير البيانات
```sql
-- تنظيف البيانات وإعدادها للنقل
UPDATE users SET phone = REPLACE(phone, '+966', '') WHERE phone LIKE '+966%';
```

### 3. سكريبت النقل التدريجي
```sql
-- نقل المستخدمين
INSERT INTO platinum.users (name, email, phone, created_at)
SELECT name, email, phone, created_at 
FROM erp_operations.users 
WHERE id NOT IN (SELECT source_id FROM migration_mapping WHERE source_table = 'users');
```

---

## 📈 التوصيات | Recommendations

### 1. تكتيك النقل التدريجي
- البدء بالجداول البسيطة
- اختبار كل مرحلة قبل المتابعة
- إبقاء نسخ احتياطية دائمة

### 2. إدارة المخاطر
- تنفيذ العملية في ساعات الإغلاق
- إعداد خطة للرجوع السريع
- مراقبة الأداء أثناء النقل

### 3. ضمان الجودة
- التحقق من التطابق بعد كل مرحلة
- اختبار العمليات الحرجة
- توثيق كل خطوة

---

## 🔚 الخلاصة | Summary

قاعدة البيانات `erp_operations_new` تحتوي على **87 جدولاً** بينما `platinum` تحتوي على **109 جداول**. 

**النقاط الرئيسية:**
- توجد تطابق وظيفي لمعظم الجداول الأساسية
- الاختلافات الرئيسية في أسماء الجداول وبنية الحقول
- قاعدة Platinum أكثر تعقيداً وتحتوي على ميزات إضافية
- النقل ممكن ولكن يتطلب تخطيط دقيق وتطوير سكريبتات مخصصة

**الخطوة التالية:** مراجعة هذا التحليل وتحديد أولويات النقل قبل بدء تطوير السكريبتات.
