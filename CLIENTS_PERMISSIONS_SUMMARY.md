# دليل صلاحيات العملاء - Clients Permissions Guide

## نظرة عامة
تم تحديث صلاحيات العملاء لتشمل جميع العمليات المطلوبة مع إضافة صلاحيات جديدة مفصلة ومترجمة.

## الصلاحيات المضافة

### 1. صلاحيات العرض الأساسية
- **`CLIENTS_VIEW`** - عرض العملاء
- **`CLIENTS_SHOW`** - عرض تفاصيل العميل
- **`CLIENTS_CREATE`** - إضافة عميل جديد
- **`CLIENTS_EDIT`** - تعديل بيانات العميل
- **`CLIENTS_DELETE`** - حذف العميل

### 2. صلاحيات التصدير
- **`CLIENTS_EXPORT_PDF`** - تصدير العملاء بصيغة PDF
- **`CLIENTS_EXPORT_EXCEL`** - تصدير العملاء بصيغة Excel

### 3. صلاحيات إدارة الحالة
- **`CLIENTS_TOGGLE_STATUS`** - تغيير حالة العميل (تفعيل/إلغاء تفعيل)

### 4. صلاحيات إضافية
- **`CLIENTS_SETTINGS`** - إعدادات العملاء

## التطبيق في الكود

### 1. في ملف العرض (index.blade.php)
```php
@if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_CREATE))
    <a href="{{ route('clients.create') }}" class="btn btn-primary">
        <i class="fa fa-plus"></i> {{helperTrans("ingaz.add_client")}}
    </a>
@endif

@if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EXPORT_PDF))
    <a href="{{ route('clients.export.pdf') }}" class="btn btn-danger" target="_blank">
        <i class="fa fa-file-pdf"></i> {{helperTrans("ingaz.export_pdf")}}
    </a>
@endif

@if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EXPORT_EXCEL))
    <a href="{{ route('clients.export.excel') }}" class="btn btn-success">
        <i class="fa fa-file-excel"></i> {{helperTrans("ingaz.export_excel")}}
    </a>
@endif
```

### 2. في UserController
```php
public function create(Request $request)
{
    if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_CREATE)) {
        abort(403, 'ليس لديك صلاحية لإضافة عميل جديد');
    }
    // ... باقي الكود
}

public function show($id)
{
    if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_SHOW)) {
        abort(403, 'ليس لديك صلاحية لعرض تفاصيل العميل');
    }
    // ... باقي الكود
}

public function exportPdf()
{
    if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EXPORT_PDF)) {
        abort(403, 'ليس لديك صلاحية لتصدير بيانات العملاء بصيغة PDF');
    }
    // ... باقي الكود
}
```

### 3. في أزرار العمليات
```php
// Show client details
if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_SHOW)) {
    $buttons .= '<a href="' . route($this->mainRoute . '.show', $row->id) . '" class="btn btn-sm btn-info me-1">' . __('ingaz.show') . '</a> ';
}

// Edit client
if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EDIT)) {
    $buttons .= '<a href="' . route($this->mainRoute . '.edit', $row->id) . '" class="btn btn-sm btn-primary me-1">' . __('ingaz.edit') . '</a> ';
}

// Toggle status
if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_TOGGLE_STATUS)) {
    $buttons .= '<button class="btn btn-sm btn-warning me-1 update-status" data-id="' . $row->id . '">' . __('ingaz.toggle_status') . '</button> ';
}
```

## الترجمات المضافة

### العربية (ar/permissions.php)
```php
'clients_export_pdf' => 'تصدير العملاء بصيغة PDF',
'clients_export_excel' => 'تصدير العملاء بصيغة Excel',
'clients_toggle_status' => 'تغيير حالة العميل',
'clients_show' => 'عرض تفاصيل العميل',
'clients_manage' => 'إدارة العملاء',
'clients_import' => 'استيراد بيانات العملاء',
'clients_analytics' => 'تحليلات العملاء',
'clients_reports' => 'تقارير العملاء',
'clients_notifications' => 'إشعارات العملاء',
'clients_settings' => 'إعدادات العملاء',
```

### الإنجليزية (en/permissions.php)
```php
'clients_export_pdf' => 'Export Clients as PDF',
'clients_export_excel' => 'Export Clients as Excel',
'clients_toggle_status' => 'Toggle Client Status',
'clients_show' => 'Show Client Details',
'clients_manage' => 'Manage Clients',
'clients_import' => 'Import Clients Data',
'clients_analytics' => 'Clients Analytics',
'clients_reports' => 'Clients Reports',
'clients_notifications' => 'Clients Notifications',
'clients_settings' => 'Clients Settings',
```

## كيفية الاستخدام

### 1. إضافة صلاحية جديدة
```php
// في PermKeys.php
const CLIENTS_NEW_PERMISSION = 'clients_new_permission';

// في الترجمات
'clients_new_permission' => 'الصلاحية الجديدة',

// في Seeder
[
    'name' => 'clients_new_permission',
    'display_name' => 'الصلاحية الجديدة',
    'description' => 'وصف الصلاحية الجديدة',
    'guard_name' => 'admin'
]
```

### 2. التحقق من الصلاحية
```php
// في Controller
if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_NEW_PERMISSION)) {
    abort(403, 'رسالة الخطأ');
}

// في Blade
@if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_NEW_PERMISSION))
    // المحتوى المحمي
@endif
```

### 3. في القائمة الجانبية
```php
@if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_VIEW))
    <li>
        <a class="m-link" href="{{route('clients.index')}}">
            <i class="fa fa-user"></i>
            <span class="ms-2">{{ helperTrans('ingaz.clients') }}</span>
        </a>
    </li>
@endif
```

## الملفات المحدثة

1. **`app/Helpers/PermKeys.php`** - إضافة الصلاحيات الجديدة
2. **`app/Http/Controllers/ingaz/UserController.php`** - إضافة فحوصات الصلاحيات
3. **`resources/views/admin/ingaz/clients/index.blade.php`** - إضافة فحوصات الصلاحيات للأزرار
4. **`resources/lang/ar/permissions.php`** - الترجمات العربية
5. **`resources/lang/en/permissions.php`** - الترجمات الإنجليزية
6. **`resources/lang/ar/ingaz.php`** - ترجمات إضافية
7. **`resources/lang/en/ingaz.php`** - ترجمات إضافية
8. **`database/seeders/ClientsPermissionsSeeder.php`** - Seeder للصلاحيات الجديدة
9. **`app/Http/Controllers/Permissions/PermissionsController.php`** - تحديث عرض الصلاحيات

## تشغيل التحديثات

```bash
# تشغيل Seeder لإضافة الصلاحيات الجديدة
php artisan db:seed --class=ClientsPermissionsSeeder

# مسح الكاش إذا لزم الأمر
php artisan cache:clear
php artisan config:clear
```

## ملاحظات مهمة

1. **الأمان**: جميع العمليات محمية بفحوصات الصلاحيات
2. **المرونة**: يمكن إضافة صلاحيات جديدة بسهولة
3. **الترجمة**: جميع الصلاحيات مترجمة بالعربية والإنجليزية
4. **التنظيم**: الصلاحيات منظمة في مجموعات وأقسام واضحة
5. **التوافق**: النظام متوافق مع النظام الحالي للصلاحيات

## استكشاف الأخطاء

إذا واجهت مشاكل:

1. تأكد من تشغيل Seeder
2. تحقق من وجود الصلاحيات في قاعدة البيانات
3. تأكد من إضافة الترجمات المطلوبة
4. تحقق من صحة أسماء الصلاحيات في الكود

## التطوير المستقبلي

يمكن إضافة صلاحيات جديدة مثل:
- `CLIENTS_BULK_ACTIONS` - عمليات جماعية
- `CLIENTS_IMPORT` - استيراد العملاء
- `CLIENTS_ANALYTICS` - تحليلات متقدمة
- `CLIENTS_REPORTS` - تقارير مفصلة
