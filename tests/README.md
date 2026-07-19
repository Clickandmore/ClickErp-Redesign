# الاختبارات — ClickERP

## التشغيل

```bash
php artisan test:db-provision   # مرة واحدة، ثم بعد أي مايجريشن جديد
vendor/bin/phpunit              # أو: vendor/bin/phpunit tests/Feature/Branch/
```

## قاعدة بيانات الاختبار

الاختبارات تعمل على قاعدة MySQL منفصلة اسمها `rafdain_testing` (محدّدة في `phpunit.xml`)،
منفصلة تماماً عن قاعدة التطوير `rafdain_test`.

**لماذا لا نستخدم sqlite أو `migrate:fresh`؟**

- `php artisan migrate` من الصفر يفشل: `app/` و `packages/hr/` كلاهما يعرّف كلاس
  `CreateComplaintsTable`، فالمُهاجر يتوقف بخطأ قاتل.
- السكيمة تعتمد على MySQL بشكل يصعب تشغيله على sqlite.

لذلك `php artisan test:db-provision` ينسخ **بنية** الجداول من قاعدة التطوير
(`CREATE TABLE ... LIKE`، بدون بيانات)، ثم ينسخ جداول مرجعية صغيرة بصفوفها
(`migrations`, `permissions`, `roles`, `role_has_permissions`, `settings`, …)،
ثم يشغّل المايجريشن المعلّق فقط. هذا يعني أن سكيمة الاختبار قد تسبق قاعدة التطوير
إذا كانت الأخيرة متأخرة.

الاختبارات تستخدم `DatabaseTransactions` (لا `RefreshDatabase`): كل اختبار يعمل داخل
معاملة تُلغى بعده، فالقاعدة تبقى نظيفة بدون إعادة تشغيل 370 مايجريشن.

## الـ Factories

| Factory | ملاحظات |
|---|---|
| `Branch::factory()` | `->main()` `->inactive()` |
| `Admin::factory()` | `->forBranch($branch)` `->global()` `->superAdmin()` `->suspended()` |

`AdminFactory` موجود في `database/factories/auth/` ليطابق نطاق `App\Models\auth\Admin`.

**مهم:** لا تعتمد على بيانات موجودة مسبقاً (`Admin::where(...)->firstOrFail()`).
كل اختبار ينشئ ما يحتاجه — وإلا انكسر على أي قاعدة أخرى.

## اختبارات الفروع

| ملف | يغطي |
|---|---|
| `Feature/Branch/BranchAccessServiceTest.php` | فلترة القراءة: من يرى أي صفوف |
| `Feature/Branch/AssignsBranchTest.php` | ختم `branch_id` تلقائياً عند الإنشاء |
| `Feature/Branch/BranchCrudTest.php` | شاشات الفروع، التحقق، حد عدد الفروع |
| `Feature/Branch/BranchAdminAssignmentTest.php` | ربط الموظفين ومدير الفرع |
| `Feature/Branch/BranchModeTest.php` | **الوضعان: بفروع / بدون فروع** |

### الوضعان (`BranchModeTest`)

كل الميزة معلّقة على مفتاح واحد: `SystemModuleManager::isEnabled('branches')`، مصدره صف
`branches` في جدول `system_module_settings`. هذا الملف يشغّل **نفس السيناريو مرتين** —
مرة والمفتاح مفعّل ومرة مطفأ — فالوضعان مثبّتان أحدهما مقابل الآخر لا كلٌ على حدة.

| السلوك | مفعّل | مطفأ |
|---|---|---|
| شاشات الفروع | تفتح | تحويل إلى `admin.no-permissions` (وJSON ← 403) |
| اسم الفرع في الواجهة | يظهر | لا يظهر |
| `branch_id` عند الإنشاء | يُختم تلقائياً من فرع الموظف | لا يُختم |
| القوائم | مفلترة على فرع الموظف | كل السجلات |
| سجل من فرع آخر | 403 | يُفتح عادي |
| إنشاء لفرع آخر | 403 | مسموح |
| `?branch_id=` في الرابط | يفلتر (للإدارة العامة فقط) | يُتجاهل |

موظف بلا `branch_id` = إدارة عامة: يرى كل الفروع ويقدر يفلتر على فرع محدد.
السجلات القديمة بـ `branch_id = null` تبقى ظاهرة للجميع (سلوك مقصود في `BranchAccessService`).

**الوضع الافتراضي مطفأ** — `config/superadmin_modules.php` يضع `default_enabled => false`.

هذه الاختبارات تحققت بالطفرات (mutation testing): تعطيل الفلترة يُسقط 4 اختبارات،
تعطيل الختم التلقائي يُسقط 1، وإزالة حارس الـ403 يُسقط 1.

`tests/Concerns/InteractsWithBranches.php` يوفّر `enableBranchesModule()`,
`setBranchLimit()`, `branchAdmin()`, `globalAdmin()`.

`SystemModuleManager` يخزّن إعداداته في الكاش، لذا أي اختبار يغيّرها لازم يمر عبر هذا
الـ trait (ينظّف الكاش) وإلا ورث الاختبار التالي الإجابة القديمة.

## اختبارات شاملة للمشروع

- `Feature/Models/ModelSchemaIntegrityTest.php` — كل موديل يشير لجدول موجود، وكل علاقة
  تشير لكلاس موجود.
- `Feature/RouteIntegrityTest.php` — كل مسار له أكشن موجود، لا أسماء مسارات مكررة،
  كل `route('...')` في Blade مسجّل، وكل قالب Blade يُصرَّف.

### خطوط الأساس (baselines)

بعض هذه الفحوص تصطدم بديون قديمة كبيرة (50 مسار ميت بعد حذف الفرونت، 74 اسم مسار مكرر).
حذف التأكيد يفقد الإشارة، وتركه أحمر يجعل السويت عديم الفائدة. لذلك تُسجَّل المشاكل
المعروفة في `tests/baselines/*.json`، ويفشل الاختبار فقط عند ظهور مشكلة **جديدة**.

```bash
BASELINE_UPDATE=1 vendor/bin/phpunit    # بعد إصلاح مشاكل، لتقليص خط الأساس
```

إذا اختفت مشاكل مسجّلة، يطبع الاختبار تنبيهاً (بدون فشل) يذكّرك بتحديث خط الأساس.
الهدف أن تصغر هذه الملفات مع الوقت.
