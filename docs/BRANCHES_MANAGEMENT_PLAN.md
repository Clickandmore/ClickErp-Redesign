# خطة إدارة فروع الشركة في لوحة التحكم

هذا الملف هو المرجع التنفيذي لبناء نظام فروع الشركة داخل لوحة التحكم:

```text
http://127.0.0.1:8000/dashboard
```

الهدف ليس مجرد إضافة شاشة فروع، بل بناء طبقة تنظيمية تساعد على إدارة الموظفين والطلبات والعقود والتقارير حسب الفرع مع أقل تأثير ممكن على سير العمل الحالي.

---

## 1. الهدف

إضافة نظام لإدارة فروع الشركة بحيث يمكن:

- إنشاء وتعديل وتعطيل الفروع.
- ربط موظفي لوحة التحكم بفروع الشركة.
- عرض إحصائيات وتقارير كل فرع.
- تقييد رؤية البيانات حسب الفرع عند الحاجة.
- الحفاظ على عمل النظام الحالي بدون كسر الشاشات أو التقارير.
- تفعيل عزل البيانات تدريجيا بعد الاختبار.

---

## 2. المبادئ الحاكمة

### 2.1 لا نكسر السلوك الحالي

أي حقل جديد يجب أن يكون اختياريا في البداية، خصوصا `branch_id`.

لا يتم إجبار السجلات القديمة على فرع محدد قبل مراجعة البيانات.

### 2.2 لا نستخدم Global Scope في البداية

استخدام Global Scope لعزل الفروع من أول مرحلة خطر، لأنه قد يؤثر على:

- الداشبورد.
- التقارير.
- الفواتير.
- العقود.
- شاشات Ajax و DataTables.
- أوامر Artisan.

الأفضل استخدام خدمة مركزية للفلترة ثم تطبيقها تدريجيا في الشاشات المطلوبة.

### 2.3 الفروع ليست المكاتب الخارجية

يوجد في النظام مفهوم `recruitment_offices`، وهو مستخدم للمكاتب الخارجية ومكاتب الاستقدام المرتبطة بالسير الذاتية والعاملات.

لا نستخدم هذا الجدول كفروع للشركة.

الفروع الداخلية تحتاج جدولا مستقلا باسم `branches`.

### 2.4 التفعيل على مراحل

نبدأ بإدارة الفروع وربط الموظفين، ثم نضيف فلتر الداشبورد، ثم ربط السجلات الجديدة، ثم عزل القوائم والتقارير.

---

## 3. النطاق

### داخل النطاق

- جدول الفروع.
- شاشة إدارة الفروع.
- صلاحيات الفروع.
- ربط موظفي `admins` بالفروع.
- فلتر الفروع في الداشبورد.
- ربط السجلات التشغيلية الجديدة بالفرع.
- فلترة البيانات حسب الفرع في القوائم المهمة.
- تقارير أداء الفروع.

### خارج النطاق في المرحلة الأولى

- إعادة هيكلة كل النظام حول الفروع دفعة واحدة.
- تغيير جداول المكاتب الخارجية.
- تعديل كل التقارير القديمة مرة واحدة.
- فرض عزل كامل على كل الاستعلامات.
- دعم أكثر من فرع لنفس الموظف، إلا إذا تقرر ذلك لاحقا.

---

## 4. تحليل الوضع الحالي

### 4.1 المسارات الحالية

لوحة التحكم الأساسية تعمل من:

```text
routes/admin.php
```

أهم المسارات الحالية:

```text
dashboard
dashboard-pro
dashboard-old
dashboard/employees/{employee}
dashboard/admins
```

المسارات محمية غالبا بـ:

```text
admin
admin.route.permission
```

### 4.2 نظام الصلاحيات

المشروع يستخدم:

- Spatie Permission.
- Middleware باسم `EnsureAdminRoutePermission`.
- مصفوفة صلاحيات داخل `App\Support\AdminRoutePermissionMatrix`.
- مفاتيح صلاحيات داخل `App\Helpers\PermKeys.php`.

أي إضافة للفروع يجب أن تمر عبر نفس منظومة الصلاحيات، وليس عبر تحقق منفصل داخل الواجهات فقط.

### 4.3 المستخدمون الإداريون

نموذج الأدمن:

```text
App\Models\auth\Admin
```

جدول الأدمن:

```text
admins
```

يوجد حاليا:

- `role_id`
- بيانات التواصل.
- حالة المستخدم.
- بيانات الجلسة والنشاط.

المطلوب إضافة `branch_id` اختياريا في البداية.

### 4.4 الداشبورد

الداشبورد يستخدم:

```text
App\Http\Controllers\admin\DashboardController
```

وفيه إحصائيات مباشرة على جداول مثل:

- `orders`
- `maids`
- `users`
- `contracts`
- `rental_contracts`
- `sponsorship_transfers`
- `recruitment_offices`

لذلك فلترة الفروع يجب أن تتم بحذر، لأن الداشبورد يجمع بيانات من عدة مصادر.

---

## 5. التصميم المقترح للبيانات

### 5.1 جدول الفروع

اسم الجدول:

```text
branches
```

الحقول المقترحة:

```text
id
name
code
city
address
phone
email
manager_id
status
is_main
notes
created_by
updated_by
created_at
updated_at
deleted_at
```

### 5.2 شرح الحقول

| الحقل | النوع | ملاحظات |
|---|---|---|
| `name` | string | اسم الفرع |
| `code` | string nullable unique | كود داخلي للفرع |
| `city` | string nullable | المدينة |
| `address` | text nullable | العنوان |
| `phone` | string nullable | هاتف الفرع |
| `email` | string nullable | بريد الفرع |
| `manager_id` | foreign nullable | مدير الفرع من جدول `admins` |
| `status` | string | `active` أو `inactive` |
| `is_main` | boolean | تحديد الفرع الرئيسي |
| `notes` | text nullable | ملاحظات داخلية |
| `created_by` | foreign nullable | من أنشأ الفرع |
| `updated_by` | foreign nullable | آخر من عدل الفرع |
| `deleted_at` | timestamp nullable | حذف ناعم |

### 5.3 ربط الموظفين بالفروع

إضافة حقل إلى جدول:

```text
admins.branch_id
```

الخصائص:

- `nullable`.
- مفتاح أجنبي إلى `branches.id`.
- عند حذف الفرع يكون `nullOnDelete`.

### 5.4 الجداول التشغيلية المرشحة للربط

يتم إضافة `branch_id` تدريجيا إلى:

```text
orders
contracts
rental_contracts
sponsorship_transfers
invoices
complaints
support_tickets
maids
```

الأولوية:

1. `orders`
2. `contracts`
3. `rental_contracts`
4. `invoices`
5. `complaints`
6. `support_tickets`
7. `maids`

---

## 6. نماذج Laravel المطلوبة

### 6.1 نموذج Branch

ملف مقترح:

```text
app/Models/Branch.php
```

العلاقات المطلوبة:

```text
manager()       belongsTo Admin
admins()        hasMany Admin
orders()        hasMany Order
contracts()     hasMany Contract
createdBy()     belongsTo Admin
updatedBy()     belongsTo Admin
```

### 6.2 تعديل نموذج Admin

إضافة علاقات:

```text
branch()        belongsTo Branch
managedBranch() hasOne Branch عبر manager_id
```

إضافة دوال مساعدة:

```text
hasGlobalBranchAccess()
canAccessBranch($branchId)
currentBranchId()
```

لا توضع قواعد الوصول المعقدة داخل النموذج فقط. الأفضل أن تكون داخل خدمة مركزية.

---

## 7. خدمة الوصول للفروع

ملف مقترح:

```text
app/Support/BranchAccess.php
```

أو:

```text
app/Services/BranchAccessService.php
```

المهام:

- معرفة هل المستخدم يرى كل الفروع.
- معرفة الفروع المسموحة للمستخدم.
- تطبيق فلتر الفرع على Query Builder.
- تحديد الفرع الافتراضي عند إنشاء سجل جديد.
- منع الوصول إلى فرع غير مصرح.

دوال مقترحة:

```php
public function canViewAllBranches(Admin $admin): bool
public function allowedBranchIds(Admin $admin): array
public function defaultBranchId(?Admin $admin): ?int
public function requestedBranchId(Request $request, Admin $admin): ?int
public function applyToQuery($query, Request $request, Admin $admin, string $column = 'branch_id')
public function assertCanAccess(?Admin $admin, ?int $branchId): void
```

---

## 8. صلاحيات الفروع

مفاتيح صلاحيات مقترحة:

```text
branches_view
branches_create
branches_edit
branches_delete
branches_manage
branches_view_all
branches_reports
```

### 8.1 معنى الصلاحيات

| الصلاحية | المعنى |
|---|---|
| `branches_view` | عرض قائمة الفروع |
| `branches_create` | إنشاء فرع |
| `branches_edit` | تعديل بيانات فرع |
| `branches_delete` | حذف أو تعطيل فرع |
| `branches_manage` | إدارة كاملة للفروع |
| `branches_view_all` | رؤية كل الفروع وبياناتها |
| `branches_reports` | عرض تقارير الفروع |

### 8.2 الربط مع النظام الحالي

يجب تحديث:

```text
App\Helpers\PermKeys.php
App\Support\AdminRoutePermissionMatrix.php
PermissionsController أو مصدر عرض الصلاحيات الحالي
```

بعد الإضافة يتم تشغيل:

```bash
php artisan permissions:prepare-server --role-id=1 --role-name=Administrator --attach-first-admin
php artisan permission:cache-reset
php artisan optimize:clear
```

---

## 9. المسارات والواجهات

### 9.1 المسارات المقترحة

داخل `routes/admin.php` تحت prefix:

```text
dashboard
```

إضافة:

```text
GET    dashboard/branches
GET    dashboard/branches/create
POST   dashboard/branches
GET    dashboard/branches/{branch}/edit
PUT    dashboard/branches/{branch}
DELETE dashboard/branches/{branch}
POST   dashboard/branches/{branch}/toggle-status
GET    dashboard/branches/{branch}/reports
```

أسماء routes المقترحة:

```text
branches.index
branches.create
branches.store
branches.edit
branches.update
branches.destroy
branches.toggle-status
branches.reports
```

### 9.2 Controller

ملف مقترح:

```text
app/Http/Controllers/admin/BranchController.php
```

المهام:

- عرض الفروع عبر DataTables أو جدول بسيط حسب نمط المشروع.
- إنشاء وتعديل فرع.
- تعطيل الفرع بدلا من الحذف الفعلي.
- منع تعطيل آخر فرع نشط.
- منع حذف فرع مرتبط بسجلات إلا عبر تعطيل فقط.

### 9.3 Requests

ملفات مقترحة:

```text
app/Http/Requests/admin/StoreBranchRequest.php
app/Http/Requests/admin/UpdateBranchRequest.php
```

قواعد مبدئية:

```text
name required string max:255
code nullable string max:50 unique
city nullable string max:255
address nullable string
phone nullable string max:50
email nullable email max:255
manager_id nullable exists:admins,id
status required in:active,inactive
is_main nullable boolean
notes nullable string
```

### 9.4 الواجهات

مسار مقترح:

```text
resources/views/admin/branches/
```

ملفات:

```text
index.blade.php
create.blade.php
edit.blade.php
_form.blade.php
show.blade.php
reports.blade.php
```

---

## 10. ربط الفروع بالأدمن

### 10.1 شاشة الموظفين

يتم تعديل شاشة:

```text
dashboard/admins
```

لإضافة:

- فلتر الفرع في القائمة.
- عمود الفرع.
- حقل اختيار الفرع في إنشاء/تعديل موظف.

### 10.2 StoreAdminRequest و UpdateAdminRequest

إضافة تحقق:

```text
branch_id nullable exists:branches,id
```

### 10.3 AdminController

تحديث:

```text
$postData
```

لإضافة:

```text
branch_id
```

وتحديث DataTables لعرض الفرع والفلترة به.

---

## 11. سلوك إنشاء السجلات الجديدة

عند إنشاء سجل تشغيلي جديد، إذا لم يرسل المستخدم `branch_id`:

```text
branch_id = فرع الموظف الحالي
```

إذا كان المستخدم لديه صلاحية `branches_view_all` أو `branches_manage`:

- يمكنه اختيار الفرع من القائمة.
- إذا لم يختر فرعا يتم استخدام فرعه الافتراضي أو الفرع الرئيسي.

إذا كان المستخدم موظف فرع:

- لا يظهر له اختيار الفروع.
- يتم تثبيت الفرع تلقائيا.

---

## 12. الداشبورد حسب الفروع

### 12.1 المرحلة الأولى

إضافة فلتر فرع أعلى الداشبورد:

```text
كل الفروع
الفرع الرئيسي
فرع الرياض
فرع جدة
...
```

يظهر فلتر "كل الفروع" فقط لمن لديه:

```text
branches_view_all
```

### 12.2 المؤشرات التي تبدأ بها الفلترة

أول مجموعة:

- طلبات الاستقدام.
- طلبات التأجير.
- عقود الاستقدام.
- عقود التأجير.
- نقل الكفالة.
- العملاء الجدد.

ثاني مجموعة:

- الفواتير.
- الشكاوى.
- التذاكر.
- أداء الموظفين.

### 12.3 طريقة التنفيذ

داخل `DashboardController` لا نكرر منطق الفرع في كل استعلام بشكل عشوائي.

نستخدم خدمة:

```php
$branchAccess->applyToQuery($query, $request, auth('admin')->user());
```

مع الانتباه أن بعض الجداول قد لا تحتوي `branch_id` في المراحل الأولى.

---

## 13. سياسة عرض البيانات

### 13.1 Administrator / Super Admin

يرى:

- كل الفروع.
- كل البيانات.
- فلتر الفروع.
- تقارير المقارنة بين الفروع.

### 13.2 مدير الفرع

يرى:

- بيانات فرعه.
- موظفي فرعه.
- تقارير فرعه.

لا يرى:

- بيانات الفروع الأخرى.
- إعدادات الفروع الأخرى.

### 13.3 موظف الفرع

يرى:

- سجلات فرعه فقط.
- بياناته ومهامه.

لا يرى:

- فلتر كل الفروع.
- تقارير المقارنة.

---

## 14. خطة الترحيل للبيانات القديمة

### 14.1 إنشاء الفرع الرئيسي

بعد تشغيل migration:

```text
إنشاء فرع رئيسي باسم: الفرع الرئيسي
is_main = true
status = active
```

### 14.2 ربط الموظفين الحاليين

الخيار الآمن:

```text
كل admins الحاليين يتم ربطهم بالفرع الرئيسي مبدئيا.
```

ثم تتم مراجعتهم يدويا.

### 14.3 ربط السجلات القديمة

يوجد خياران:

#### الخيار أ: ترك السجلات القديمة بدون فرع

المميزات:

- لا ننسب بيانات قديمة لفرع بشكل خاطئ.

العيوب:

- التقارير حسب الفروع لن تكون مكتملة تاريخيا.

#### الخيار ب: ربط كل القديم بالفرع الرئيسي

المميزات:

- التقارير لا تظهر بيانات "غير محددة".
- أسهل للمحاسبة والمتابعة.

العيوب:

- قد لا يكون تاريخيا دقيقا إذا كانت هناك فروع قديمة.

التوصية:

```text
استخدام الخيار ب إذا لم تكن هناك بيانات فروع قديمة موثوقة.
استخدام الخيار أ إذا كانت الشركة تريد مراجعة تاريخية دقيقة.
```

---

## 15. مراحل التنفيذ المقترحة

### المرحلة 0: اعتماد القرار

قبل البرمجة يجب حسم:

- هل الموظف يتبع فرعا واحدا فقط؟
- هل السجلات القديمة تربط بالفرع الرئيسي أم تترك بدون فرع؟
- من هم المستخدمون الذين يرون كل الفروع؟
- هل يوجد فرع رئيسي واحد فقط؟
- هل المطلوب لاحقا ربط الفواتير محاسبيا بمراكز تكلفة الفروع؟

### المرحلة 1: الأساس

العمل:

- إنشاء جدول `branches`.
- إنشاء Model.
- إنشاء Seeder للفرع الرئيسي.
- إضافة صلاحيات الفروع.
- تحديث مصفوفة الصلاحيات.

اختبار القبول:

- يمكن تشغيل migrations بدون خطأ.
- يتم إنشاء الفرع الرئيسي.
- تظهر صلاحيات الفروع في شاشة الصلاحيات.
- Administrator يحصل على الصلاحيات.

### المرحلة 2: CRUD الفروع

العمل:

- إنشاء `BranchController`.
- إنشاء Requests.
- إنشاء views.
- إضافة routes.
- إضافة رابط في القائمة الجانبية حسب الصلاحية.

اختبار القبول:

- يمكن عرض الفروع.
- يمكن إنشاء فرع.
- يمكن تعديل فرع.
- يمكن تعطيل فرع.
- لا يمكن حذف فرع مرتبط بسجلات مهمة.

### المرحلة 3: ربط الموظفين

العمل:

- إضافة `branch_id` إلى `admins`.
- تعديل نموذج Admin.
- تعديل شاشة الموظفين.
- تعديل Store/Update requests.
- إضافة فلتر فرع في قائمة الموظفين.

اختبار القبول:

- يمكن تعيين فرع لموظف.
- يظهر الفرع في جدول الموظفين.
- يمكن فلترة الموظفين حسب الفرع.
- لا يتأثر تسجيل الدخول.

### المرحلة 4: فلتر الداشبورد

العمل:

- إنشاء `BranchAccessService`.
- إضافة فلتر الفروع في الداشبورد.
- تطبيق الفلترة على أهم الإحصائيات فقط.

اختبار القبول:

- الأدمن العام يرى كل الفروع.
- موظف الفرع يرى فرعه فقط.
- إحصائيات الفرع تختلف عند تغيير الفلتر.
- الداشبورد بدون فلتر يعمل كما كان للأدمن العام.

### المرحلة 5: ربط السجلات الجديدة

العمل:

- إضافة `branch_id` تدريجيا إلى الجداول التشغيلية.
- تعبئة الفرع تلقائيا عند الإنشاء.
- إضافة اختيار الفرع فقط لمن لديه صلاحية رؤية كل الفروع.

البداية المقترحة:

```text
orders
contracts
rental_contracts
invoices
```

اختبار القبول:

- أي طلب جديد يأخذ فرع الموظف.
- أي عقد جديد يأخذ فرع الموظف أو الفرع المختار.
- لا يمكن لموظف فرع إنشاء سجل باسم فرع آخر.

### المرحلة 6: عزل القوائم والتقارير

العمل:

- تطبيق فلترة الفروع على القوائم الأساسية.
- تطبيقها على DataTables و Ajax.
- تطبيقها على التصدير إن وجد.

اختبار القبول:

- موظف الفرع لا يرى سجلات فرع آخر.
- الأدمن العام يرى الجميع.
- التصدير يحترم نفس الفلتر.
- الروابط المباشرة لسجل من فرع آخر تمنع الوصول.

### المرحلة 7: تقارير الفروع

العمل:

- صفحة تقرير لكل فرع.
- مقارنة بين الفروع.
- أداء الموظفين حسب الفرع.
- الطلبات والعقود والإيرادات حسب الفرع.

اختبار القبول:

- تقرير الفرع يعرض مؤشرات واضحة.
- تقرير المقارنة يظهر لكل الفروع النشطة.
- النتائج مطابقة للقوائم المفصلة.

---

## 16. الملفات المتوقع تعديلها

### ملفات جديدة

```text
app/Models/Branch.php
app/Http/Controllers/admin/BranchController.php
app/Http/Requests/admin/StoreBranchRequest.php
app/Http/Requests/admin/UpdateBranchRequest.php
app/Services/BranchAccessService.php
database/migrations/YYYY_MM_DD_HHMMSS_create_branches_table.php
database/migrations/YYYY_MM_DD_HHMMSS_add_branch_id_to_admins_table.php
database/seeders/BranchSeeder.php
resources/views/admin/branches/index.blade.php
resources/views/admin/branches/create.blade.php
resources/views/admin/branches/edit.blade.php
resources/views/admin/branches/_form.blade.php
```

### ملفات قائمة متوقعة

```text
routes/admin.php
app/Models/auth/Admin.php
app/Http/Controllers/admin/auth/AdminController.php
app/Http/Requests/auth/profile/StoreAdminRequest.php
app/Http/Requests/auth/profile/UpdateAdminRequest.php
app/Http/Controllers/admin/DashboardController.php
app/Helpers/PermKeys.php
app/Support/AdminRoutePermissionMatrix.php
resources/views/admin/layouts/*
resources/views/admin/auth/admin/*
```

---

## 17. المخاطر وطريقة تقليلها

### خطر: اختفاء بيانات من القوائم

السبب:

تطبيق فلتر فرع مبكر أو بشكل عام.

التقليل:

- لا تستخدم Global Scope في البداية.
- طبق الفلترة شاشة شاشة.
- ابدأ بالداشبورد ثم القوائم المهمة.

### خطر: تقارير خاطئة

السبب:

السجلات القديمة بدون فرع أو منسوبة خطأ.

التقليل:

- تحديد سياسة واضحة للبيانات القديمة.
- إضافة خيار "غير محدد" مؤقتا.
- مقارنة النتائج قبل وبعد الفلترة.

### خطر: صلاحيات غير ظاهرة

السبب:

عدم تحديث نظام الصلاحيات والكاش.

التقليل:

```bash
php artisan permissions:prepare-server --role-id=1 --role-name=Administrator --attach-first-admin
php artisan permission:cache-reset
php artisan optimize:clear
```

### خطر: كسر DataTables

السبب:

إضافة أعمدة أو فلاتر بدون تعديل Ajax response.

التقليل:

- تعديل الأعمدة في Controller والواجهة معا.
- اختبار Ajax لكل شاشة.

### خطر: مستخدم يصل لرابط مباشر من فرع آخر

السبب:

الفلتر موجود في القائمة فقط وليس في show/edit/update.

التقليل:

- استخدام `assertCanAccess` في show/edit/update/destroy.
- إضافة اختبارات يدوية للروابط المباشرة.

---

## 18. قواعد التنفيذ الآمن

- كل migration يجب أن يتحقق من وجود العمود قبل إضافته إذا كان المشروع يحتوي migrations كثيرة ومتفرعة.
- كل `branch_id` جديد يكون `nullable` في البداية.
- لا يتم حذف سجلات الفروع فعليا في المرحلة الأولى، فقط تعطيل.
- لا يتم منع الدخول أو إنشاء السجلات بسبب الفرع قبل إكمال ربط الموظفين.
- أي فلترة للفرع يجب أن تحترم صلاحية `branches_view_all`.
- يجب اختبار الأدمن العام وموظف الفرع على الأقل.

---

## 19. أوامر مقترحة بعد التنفيذ

محليا:

```bash
php artisan migrate
php artisan permissions:prepare-server --role-id=1 --role-name=Administrator --attach-first-admin
php artisan permission:cache-reset
php artisan optimize:clear
php artisan route:list
```

على الإنتاج:

```bash
php artisan migrate --force
php artisan permissions:prepare-server --role-id=1 --role-name=Administrator --attach-first-admin
php artisan permission:cache-reset
php artisan optimize:clear
php artisan config:cache
```

---

## 20. قائمة اختبار شاملة

### الفروع

- إنشاء فرع جديد.
- تعديل فرع.
- تعطيل فرع.
- منع إنشاء كود مكرر.
- منع اختيار مدير غير موجود.
- ظهور الفرع الرئيسي.

### الموظفين

- إنشاء موظف بفرع.
- تعديل فرع موظف.
- عرض فرع الموظف في القائمة.
- فلترة الموظفين حسب الفرع.
- تسجيل دخول الموظف بعد ربطه بالفرع.

### الصلاحيات

- مستخدم بدون `branches_view` لا يرى شاشة الفروع.
- مستخدم لديه `branches_view` يرى القائمة فقط.
- مستخدم لديه `branches_manage` يدير الفروع.
- مستخدم لديه `branches_view_all` يرى كل الفروع.

### الداشبورد

- الأدمن العام يرى "كل الفروع".
- موظف الفرع لا يرى "كل الفروع".
- تغيير الفلتر يغير الإحصائيات.
- لا تظهر أخطاء عند عدم وجود `branch_id` في بعض الجداول.

### السجلات التشغيلية

- الطلب الجديد يأخذ فرع الموظف.
- العقد الجديد يأخذ فرع الموظف.
- الفاتورة الجديدة تأخذ فرع الموظف عند تفعيل المرحلة.
- لا يمكن لموظف فرع تعديل سجل فرع آخر.

### التراجع

- تعطيل فلترة الفروع لا يكسر الشاشات.
- يمكن جعل `branch_id` فارغا بدون فشل.
- الأدمن العام يستطيع رؤية البيانات كلها في أي وقت.

---

## 21. قرارات مطلوبة قبل بدء البرمجة

يجب اعتماد هذه النقاط قبل التنفيذ:

```text
1. هل كل موظف يتبع فرعا واحدا فقط؟
2. هل نربط البيانات القديمة بالفرع الرئيسي؟
3. ما أسماء الفروع الحالية؟
4. من هو مدير كل فرع؟
5. من هم المستخدمون الذين يرون كل الفروع؟
6. هل الفروع مرتبطة محاسبيا بمراكز تكلفة الآن أم لاحقا؟
7. هل نريد تقارير مقارنة الفروع من المرحلة الأولى أم مرحلة لاحقة؟
```

---

## 22. الترتيب العملي المقترح

الترتيب الأفضل للتنفيذ:

```text
1. اعتماد القرارات المطلوبة.
2. تنفيذ جدول الفروع والصلاحيات.
3. تنفيذ شاشة CRUD للفروع.
4. ربط الموظفين بالفروع.
5. إضافة فلتر الداشبورد.
6. ربط الطلبات والعقود الجديدة بالفروع.
7. عزل القوائم المهمة حسب الفرع.
8. بناء تقارير الفروع.
9. مراجعة شاملة قبل تفعيل العزل الكامل.
```

هذا الترتيب يقلل المخاطر لأنه يسمح بتشغيل كل مرحلة واختبارها قبل الانتقال للتي بعدها.
