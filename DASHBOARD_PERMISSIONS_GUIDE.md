# دليل صلاحيات الصفحة الرئيسية (Dashboard Permissions)

## الصلاحيات المضافة

تم إضافة الصلاحيات التالية للتحكم في عرض أقسام الصفحة الرئيسية:

### 1. إحصائيات العملاء
- **الصلاحية**: `DASHBOARD_CLIENTS_COUNT`
- **الوصف**: عرض عدد العملاء الإجمالي والعملاء الجدد هذا الشهر
- **الترجمة العربية**: عرض عدد العملاء
- **الترجمة الإنجليزية**: View Clients Count

### 2. رسائل التواصل
- **الصلاحية**: `DASHBOARD_CONTACTS_COUNT`
- **الوصف**: عرض عدد رسائل التواصل
- **الترجمة العربية**: عرض عدد رسائل التواصل
- **الترجمة الإنجليزية**: View Contacts Count

### 3. الطلبات الحالية
- **الصلاحية**: `DASHBOARD_CURRENT_ORDERS_COUNT`
- **الوصف**: عرض عدد الطلبات الحالية
- **الترجمة العربية**: عرض عدد الطلبات الحالية
- **الترجمة الإنجليزية**: View Current Orders Count

### 4. السير الذاتية
- **الصلاحية**: `DASHBOARD_CV_COUNT`
- **الوصف**: عرض عدد السير الذاتية المتاحة
- **الترجمة العربية**: عرض عدد السير الذاتية
- **الترجمة الإنجليزية**: View CV Count

### 5. الموظفين
- **الصلاحية**: `DASHBOARD_EMPLOYEES_COUNT`
- **الوصف**: عرض عدد الموظفين
- **الترجمة العربية**: عرض عدد الموظفين
- **الترجمة الإنجليزية**: View Employees Count

### 6. السير الذاتية المكتملة
- **الصلاحية**: `DASHBOARD_COMPLETED_CV_COUNT`
- **الوصف**: عرض عدد السير الذاتية المكتملة
- **الترجمة العربية**: عرض عدد السير الذاتية المكتملة
- **الترجمة الإنجليزية**: View Completed CV Count

### 7. إحصائيات الطلبات
- **الصلاحية**: `DASHBOARD_ORDERS_STATISTICS`
- **الوصف**: عرض الرسم البياني لإحصائيات الطلبات
- **الترجمة العربية**: عرض إحصائيات الطلبات
- **الترجمة الإنجليزية**: View Orders Statistics

### 8. أفضل خدمة عملاء
- **الصلاحية**: `DASHBOARD_TOP_CUSTOMER_SERVICES`
- **الوصف**: عرض أفضل 3 موظفي خدمة عملاء
- **الترجمة العربية**: عرض أفضل خدمة عملاء
- **الترجمة الإنجليزية**: View Top Customer Services

### 9. أنواع الطلبات
- **الصلاحية**: `DASHBOARD_ORDERS_TYPES`
- **الوصف**: عرض رسم بياني لأنواع الطلبات
- **الترجمة العربية**: عرض أنواع الطلبات
- **الترجمة الإنجليزية**: View Orders Types

## عرض الصلاحيات من قاعدة البيانات

### التحديثات المطبقة

تم تعديل النظام ليعرض الصلاحيات من قاعدة البيانات بدلاً من الكود الثابت:

#### 1. تعديل الكونترولر (`PermissionsController.php`)
- تم تعديل دالة `organizePermissions()` لتعمل مع البيانات من قاعدة البيانات
- تم تعديل دالة `getPermissionDisplayName()` لتعرض `display_name` من قاعدة البيانات
- تم تعديل دالة `getPermissionDescription()` لتعرض `description` من قاعدة البيانات

#### 2. هيكل قاعدة البيانات
الجدول `permissions` يحتوي على الأعمدة التالية:
- `id` - المعرف الفريد
- `name` - اسم الصلاحية
- `guard_name` - نوع الحماية
- `group_name` - اسم المجموعة
- `section_name` - اسم القسم
- `display_name` - اسم العرض (مترجم)
- `description` - وصف الصلاحية
- `created_at` - تاريخ الإنشاء
- `updated_at` - تاريخ التحديث

#### 3. أولوية عرض البيانات
النظام يعرض البيانات بالترتيب التالي:
1. **أولوية أولى**: `display_name` من قاعدة البيانات
2. **أولوية ثانية**: الترجمة من ملفات الترجمة (`permissions.php`)
3. **أولوية ثالثة**: القيم الافتراضية المحددة في الكود

### مثال على كيفية عمل النظام

```php
// في الكونترولر
private function getPermissionDisplayName($permission)
{
    // 1. إذا كان هناك display_name محفوظ في قاعدة البيانات، استخدمه
    if (!empty($permission->display_name)) {
        return $permission->display_name;
    }

    // 2. إذا لم يكن هناك display_name، استخدم الترجمات من ملفات الترجمة
    $permissionName = $permission->name;
    $translation = __('permissions.' . $permissionName);
    
    if ($translation !== 'permissions.' . $permissionName) {
        return $translation;
    }

    // 3. إذا لم توجد ترجمة، استخدم القيم الافتراضية
    $displayNames = [
        'dashboard_clients_count' => 'عرض عدد العملاء',
        // ... المزيد من القيم
    ];

    return $displayNames[$permissionName] ?? $permissionName;
}
```

## الترجمات

### ملفات الترجمة المحدثة

#### العربية (`resources/lang/ar/permissions.php`)
```php
// صلاحيات أقسام الصفحة الرئيسية
'dashboard_clients_count' => 'عرض عدد العملاء',
'dashboard_contacts_count' => 'عرض عدد رسائل التواصل',
'dashboard_current_orders_count' => 'عرض عدد الطلبات الحالية',
'dashboard_cv_count' => 'عرض عدد السير الذاتية',
'dashboard_employees_count' => 'عرض عدد الموظفين',
'dashboard_completed_cv_count' => 'عرض عدد السير الذاتية المكتملة',
'dashboard_orders_statistics' => 'عرض إحصائيات الطلبات',
'dashboard_top_customer_services' => 'عرض أفضل خدمة عملاء',
'dashboard_orders_types' => 'عرض أنواع الطلبات',
```

#### الإنجليزية (`resources/lang/en/permissions.php`)
```php
// Dashboard Section Permissions
'dashboard_clients_count' => 'View Clients Count',
'dashboard_contacts_count' => 'View Contacts Count',
'dashboard_current_orders_count' => 'View Current Orders Count',
'dashboard_cv_count' => 'View CV Count',
'dashboard_employees_count' => 'View Employees Count',
'dashboard_completed_cv_count' => 'View Completed CV Count',
'dashboard_orders_statistics' => 'View Orders Statistics',
'dashboard_top_customer_services' => 'View Top Customer Services',
'dashboard_orders_types' => 'View Orders Types',
```

### استخدام الترجمات في الكود

#### في الـ Views
```php
{{ __('permissions.dashboard_clients_count') }}
```

#### في الـ Controllers
```php
$translatedName = __('permissions.dashboard_clients_count');
```

## كيفية الاستخدام

### في الـ Views (Blade Templates)
```php
@if (@AdminGuardHasPerm(\App\PermKeys::DASHBOARD_CLIENTS_COUNT))
    <!-- محتوى قسم العملاء -->
@endif
```

### في الـ Controllers
```php
if (auth()->guard('admin')->user()->can(\App\PermKeys::DASHBOARD_CLIENTS_COUNT)) {
    // عرض بيانات العملاء
}
```

### في الـ Middleware
```php
Route::middleware(['permission:' . \App\PermKeys::DASHBOARD_CLIENTS_COUNT])->group(function () {
    // Routes that require this permission
});
```

## إدارة الصلاحيات

### إضافة صلاحية للمستخدم
```php
$user = Admin::find(1);
$user->givePermissionTo(\App\PermKeys::DASHBOARD_CLIENTS_COUNT);
```

### إزالة صلاحية من المستخدم
```php
$user = Admin::find(1);
$user->revokePermissionTo(\App\PermKeys::DASHBOARD_CLIENTS_COUNT);
```

### فحص الصلاحية
```php
if ($user->hasPermissionTo(\App\PermKeys::DASHBOARD_CLIENTS_COUNT)) {
    // المستخدم لديه الصلاحية
}
```

### تحديث بيانات الصلاحية في قاعدة البيانات
```php
$permission = Permission::where('name', 'dashboard_clients_count')->first();
$permission->update([
    'display_name' => 'عرض عدد العملاء',
    'description' => 'إمكانية عرض عدد العملاء في لوحة التحكم',
    'group_name' => 'dashboard',
    'section_name' => 'dashboard'
]);
```

## ملاحظات مهمة

1. جميع الصلاحيات تستخدم guard `admin`
2. يجب أن يكون المستخدم مسجل دخول كمدير للوصول للصلاحيات
3. يمكن دمج عدة صلاحيات في شرط واحد
4. الصلاحيات تعمل بشكل مستقل، يمكن إعطاء صلاحية واحدة دون الأخرى
5. الترجمات متاحة باللغتين العربية والإنجليزية
6. يمكن استخدام الترجمات في واجهات إدارة الصلاحيات
7. **الآن النظام يعرض الصلاحيات من قاعدة البيانات بدلاً من الكود الثابت**
8. يمكن تعديل `display_name` و `description` مباشرة من قاعدة البيانات
9. النظام يدعم التحديث المباشر للصلاحيات من خلال واجهة الإدارة

## تحديث الصلاحيات

### إضافة صلاحيات جديدة
```bash
php artisan db:seed --class=DashboardPermissionsSeeder
```

### تحديث الصلاحيات مع الترجمات
```bash
php artisan db:seed --class=DashboardPermissionsWithTranslationsSeeder
```

### تحديث الصلاحيات مع display_name و description
```bash
php artisan db:seed --class=UpdatePermissionsWithDisplayNamesSeeder
```

## المزايا الجديدة

### 1. المرونة في التعديل
- يمكن تعديل أسماء الصلاحيات ووصفها مباشرة من قاعدة البيانات
- لا حاجة لتعديل الكود عند تغيير أسماء الصلاحيات

### 2. الأداء المحسن
- تقليل الاعتماد على الكود الثابت
- تحسين سرعة تحميل الصفحات

### 3. سهولة الإدارة
- واجهة إدارة واحدة لجميع الصلاحيات
- إمكانية التعديل المباشر من قاعدة البيانات

### 4. التوافق مع النظام الحالي
- الحفاظ على جميع الوظائف الموجودة
- دعم الترجمات المتعددة
- التوافق مع نظام الأدوار والصلاحيات الحالي
