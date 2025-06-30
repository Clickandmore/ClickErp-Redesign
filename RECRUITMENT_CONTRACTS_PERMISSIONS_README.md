# صلاحيات عقود الاستقدام - Recruitment Contracts Permissions

## نظرة عامة

تم إضافة 30 صلاحية جديدة لإدارة عقود الاستقدام في النظام، مقسمة إلى مجموعات منطقية حسب الوظائف المختلفة.

## الصلاحيات الأساسية للعقود

### عرض وإدارة العقود
- `rec_contracts_view` - عرض عقود الاستقدام
- `rec_contracts_create` - إنشاء عقد استقدام جديد
- `rec_contracts_edit` - تعديل عقد الاستقدام
- `rec_contracts_delete` - حذف عقد الاستقدام
- `rec_contracts_export` - تصدير عقود الاستقدام
- `rec_contracts_print` - طباعة عقد الاستقدام

## صلاحيات تغيير حالة العقد (مرحلة بمرحلة)

### المرحلة الأولى: بانتظار الاعتماد → عقود مساند
- `rec_contracts_change_to_msaned` - تغيير من بانتظار الاعتماد إلى عقود مساند

### المرحلة الثانية: عقود مساند → التفويض
- `rec_contracts_change_to_tafwed` - تغيير من عقود مساند إلى التفويض

### المرحلة الثالثة: التفويض → التفييز
- `rec_contracts_change_to_taffez` - تغيير من التفويض إلى التفييز

### المرحلة الرابعة: التفييز → التذكرة
- `rec_contracts_change_to_tazker` - تغيير من التفييز إلى التذكرة

## صلاحيات إدارة الرحلات (في مرحلة التذكرة)

### إدارة الرحلات
- `rec_contracts_add_travel` - إضافة رحلة للعقد
- `rec_contracts_edit_travel` - تعديل رحلة العقد
- `rec_contracts_delete_travel` - حذف رحلة العقد
- `rec_contracts_complete_travel` - تحديد اكتمال الرحلة
- `rec_contracts_view_travel` - عرض تفاصيل الرحلة

## صلاحيات إدارة الضمان

### إدارة الضمان
- `rec_contracts_warranty_view` - عرض العقود تحت الضمان
- `rec_contracts_warranty_manage` - إدارة فترة الضمان
- `rec_contracts_close_after_warranty` - إغلاق العقد بعد انتهاء الضمان

## صلاحيات طلبات الإلغاء

### إدارة طلبات الإلغاء
- `rec_contracts_cancel_view` - عرض طلبات إلغاء العقود
- `rec_contracts_cancel_create` - إنشاء طلب إلغاء عقد
- `rec_contracts_cancel_edit` - تعديل طلب إلغاء العقد
- `rec_contracts_cancel_delete` - حذف طلب إلغاء العقد
- `rec_contracts_cancel_approve` - الموافقة على إلغاء العقد
- `rec_contracts_cancel_reject` - رفض إلغاء العقد

## صلاحيات المرتجعات

### إدارة المرتجعات
- `rec_contracts_return` - إرجاع عقد الاستقدام
- `rec_contracts_return_view` - عرض العقود المرتجعة
- `rec_contracts_return_manage` - إدارة المرتجعات

## صلاحيات الإدارة العامة

### الإدارة والتقارير
- `rec_contracts_manage` - إدارة عقود الاستقدام
- `rec_contracts_reports` - تقارير عقود الاستقدام
- `rec_contracts_analytics` - إحصائيات عقود الاستقدام

## كيفية الاستخدام

### في الكود
```php
// التحقق من الصلاحية
if (auth()->user()->can('rec_contracts_view')) {
    // عرض العقود
}

// التحقق من مجموعة من الصلاحيات
if (auth()->user()->canAny(['rec_contracts_create', 'rec_contracts_edit'])) {
    // إجراء العملية
}
```

### في Blade Templates
```blade
@can('rec_contracts_create')
    <button>إنشاء عقد جديد</button>
@endcan

@canany(['rec_contracts_edit', 'rec_contracts_delete'])
    <div class="actions">
        @can('rec_contracts_edit')
            <button>تعديل</button>
        @endcan
        @can('rec_contracts_delete')
            <button>حذف</button>
        @endcan
    </div>
@endcanany
```

### في Controllers
```php
public function store(Request $request)
{
    $this->authorize('rec_contracts_create');
    
    // إنشاء العقد
}

public function update(Request $request, Contract $contract)
{
    $this->authorize('rec_contracts_edit');
    
    // تحديث العقد
}
```

## التطبيق في النظام

### 1. مراحل العقد
- **بانتظار الاعتماد**: العقد الجديد
- **عقود مساند**: بعد الموافقة الأولية
- **التفويض**: بعد إكمال المستندات
- **التفييز**: بعد إصدار التأشيرة
- **التذكرة**: جاهز للسفر

### 2. إدارة الرحلات
- إضافة رحلات للعقود في مرحلة التذكرة
- تتبع حالة الرحلات
- إكمال الرحلات

### 3. إدارة الضمان
- مراقبة العقود تحت الضمان
- إدارة فترة الضمان
- إغلاق العقود بعد انتهاء الضمان

### 4. طلبات الإلغاء
- إدارة طلبات إلغاء العقود
- الموافقة أو الرفض
- تتبع حالة الطلبات

### 5. المرتجعات
- إدارة عمليات إرجاع العقود
- تتبع العقود المرتجعة

## الملفات المحدثة

1. **Seeder**: `database/seeders/AddRecruitmentContractsCorePermissionsSeeder.php`
2. **Translations**: 
   - `resources/lang/ar/permissions.php`
   - `resources/lang/en/permissions.php`
3. **Controller**: `app/Http/Controllers/Permissions/PermissionsController.php`
4. **Helper**: `app/Helpers/NewPermKeys.php`

## ملاحظات مهمة

- تم تعيين جميع الصلاحيات تلقائياً لدور `Administrator`
- جميع الصلاحيات تحتوي على وصف مفصل باللغتين العربية والإنجليزية
- الصلاحيات منظمة في مجموعات منطقية في واجهة المستخدم
- يمكن إضافة أو تعديل الصلاحيات حسب احتياجات النظام

## التطوير المستقبلي

- إضافة صلاحيات إضافية حسب الحاجة
- ربط الصلاحيات مع واجهات المستخدم المختلفة
- إضافة تقارير مفصلة عن استخدام الصلاحيات
- تطوير نظام مراجعة الصلاحيات 
