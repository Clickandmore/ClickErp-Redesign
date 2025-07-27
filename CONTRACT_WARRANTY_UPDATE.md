# تحديث نظام العقود والضمان

## التحديثات المنجزة

### 1. تحديث حالات العقود
- تم تغيير حالة `closed` إلى `completed` (عقد مكتمل)
- تم إزالة حالة `under_warranty` من القائمة الجانبية
- تم دمج جميع العقود المكتملة تحت حالة واحدة `completed`

### 2. إضافة نظام الضمان
- تم إضافة حقل `warranty_status` إلى جدول العقود
- تم إضافة حقول `warranty_start_date` و `warranty_end_date`
- مدة الضمان: 90 يوم من تاريخ الوصول

### 3. حالات الضمان
- **نشط (active)**: الضمان ساري المفعول
- **منتهي (expired)**: انتهت مدة الضمان
- **غير قابل للتطبيق (not_applicable)**: لا يوجد رحلة وصول

### 4. تحديث واجهة المستخدم
- تم إضافة عمود "حالة الضمان" في جدول العقود
- تم إضافة فلتر حالة الضمان
- تم إضافة معلومات الضمان في صفحة تفاصيل العقد

### 5. تحديث إكمال الرحلة
- عند إكمال الرحلة، يتم تحديث حالة العقد إلى `completed`
- يتم حساب حالة الضمان تلقائياً
- يتم إنشاء سجل حالة العقد

### 6. Commands الجديدة
- `contracts:update-warranty-status`: تحديث حالة الضمان للعقود المكتملة
- `contracts:update-existing-warranty`: تحديث حالة الضمان للعقود الموجودة

### 7. Schedule
- تم إضافة schedule يومي لتحديث حالة الضمان تلقائياً

## كيفية الاستخدام

### تشغيل التحديثات
```bash
# تشغيل migrations
php artisan migrate

# تحديث حالة الضمان للعقود الموجودة
php artisan contracts:update-existing-warranty

# تحديث حالة الضمان (يمكن تشغيله يدوياً)
php artisan contracts:update-warranty-status
```

### في الواجهة
1. **صفحة العقود**: يمكن تصفية العقود حسب حالة الضمان
2. **صفحة التفاصيل**: عرض معلومات الضمان للعقود المكتملة
3. **إكمال الرحلة**: تحديث تلقائي لحالة العقد والضمان

## الملفات المحدثة
- `app/Enums/ingaz/ContractStatusEnum.php`
- `app/Models/Contract.php`
- `app/Http/Controllers/ingaz/RecruitmentContractController.php`
- `resources/views/admin/ingaz/RecruitmentContract/index.blade.php`
- `resources/views/admin/ingaz/RecruitmentContract/details.blade.php`
- `app/Console/Commands/UpdateWarrantyStatus.php`
- `app/Console/Commands/UpdateExistingContractsWarranty.php`
- `app/Console/Kernel.php`

## Migrations الجديدة
- `add_warranty_status_to_contracts_table`
- `update_completed_contracts_status`
- `remove_warranty_contracts_sidebar` 
