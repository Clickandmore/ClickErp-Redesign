# تقرير أداء ClickERP — أسباب البطء والحلول

تاريخ الفحص: 2026-08-04 · الفرع: `3mer` · قاعدة الفحص: `rucn_adem_dashboard2` (545 عقد، 628 سيرة، 695 عميل — قاعدة **صغيرة**)

> كل الأرقام أدناه **قياسات فعلية** من تشغيل الكود، وليست تقديرات.

---

## 1. الخلاصة التنفيذية

قياس صفحة إدارية واحدة (`RecruitmentContract/index`) بمحاكاة طلب ويب حقيقي:

| المرحلة | الزمن | عدد الاستعلامات |
|---|---:|---:|
| الكنترولر (منطق العمل الفعلي) | **104 ms** | 30 |
| **تصيير Blade** | **2,876 ms** | **1,732** |
| **الإجمالي** | **2,981 ms** | **1,762** (منها 2,172 ms داخل MySQL) |

**‏98% من زمن الصفحة لا علاقة له بمنطق العمل**، بل بطبقة العرض. أضف إليه:

- تمهيد Laravel (bootstrap): **464 ms** لكل طلب (بدون `config:cache` وبدون OPcache)
- تحميل صلاحيات المشرف: **150 ms** لكل طلب (1,134 صلاحية)
- عدادات القائمة الجانبية: **80 ms** / 23 استعلام لكل صفحة

الإجمالي الواقعي: **~3.5 ثانية على جهاز محلي بقاعدة صغيرة**. على خادم حي تكون قاعدة البيانات على مضيف منفصل — أضف زمن ذهاب وإياب للشبكة (حتى 0.5 ms) × 1,762 استعلام = **~1 ثانية إضافية**، وأكثر مع بيانات أكبر.

**السبب ليس إنترنت العميل.** البطء في الخادم + في تحميل ملفات الواجهة من مستضيفات خارجية.

---

## 2. الأسباب مرتّبة حسب الأثر

### السبب #1 — عشرة `View::composer('*')` تعمل مع كل view مُصيَّر ⚠️ الأخطر

[app/Providers/AppServiceProvider.php:78-137](../app/Providers/AppServiceProvider.php#L78-L137)

عشرة composers مسجّلة على النمط `'*'`. في Laravel، هذا النمط يُطلَق **لكل view يُصيَّر**، بما فيها كل `@include`.

الصفحة الإدارية الواحدة تُصيّر **25 view** ([index.blade.php](../resources/views/admin/layout/indexs/index.blade.php) + 12 include مباشر + 9 داخل الشريط الجانبي + …).

قياس **دورة واحدة** من الـ composers = **54.4 ms و19 استعلام**. × 25 view:

```
>>> لكل view          54.4 ms    19 استعلام
>>> × 25 view       1,360.4 ms   475 استعلام
```

وأكثرها **غير مخزّن مؤقتاً إطلاقاً**:

| الاستعلام | تكرار/صفحة | الزمن |
|---|---:|---:|
| `select * from countries` (248 صفاً) | 25× | 56 ms |
| `settings` + `settings_translations` | 51× | 34 ms |
| `skills` + `skills_translations` | 50× | 25 ms |
| `features` | 25× | 13 ms |
| `about_us` + ترجماته | 50× | 23 ms |
| `print_setting` | 25× | 8 ms |

كل هذه بيانات **ثابتة لا تتغير** — تُقرأ 25 مرة في الطلب الواحد.

---

### السبب #2 — `Schema::hasTable` / `Schema::hasColumn` بلا أي تخزين مؤقت ⚠️

القياس على هذه القاعدة (355 جدولاً): **6.18 ms لكل استدعاء `hasColumn`**.

في صفحة واحدة:

```
748× select * from information_schema.tables ...    →   482 ms
144× select column_name from information_schema.columns ... → 1,173 ms
```

**‏1.65 ثانية من أصل 2.98 ثانية = استعلامات على `information_schema`.**

المصدر: **323 استدعاءً** لـ `Schema::hasTable/hasColumn` موزّعة على 62 ملفاً. أخطرها:

- [app/Support/BranchScopedPermissions.php:11-21](../app/Support/BranchScopedPermissions.php#L11-L21) — `appliesToAdmin()` يستدعي 4 منها، ويُنادى **مع كل فحص صلاحية**
- [app/Models/Concerns/AssignsBranch.php](../app/Models/Concerns/AssignsBranch.php) — 19 استدعاءً، تعمل مع **كل حفظ لأي نموذج**
- [app/Http/Middleware/Admin.php:44](../app/Http/Middleware/Admin.php#L44) — `Schema::hasColumn('admins','last_activity_at')` كل دقيقة لكل جلسة
- [app/Http/Controllers/admin/DashboardController.php:1849](../app/Http/Controllers/admin/DashboardController.php#L1849)

كلما زاد عدد الجداول عند العميل، زادت تكلفة كل استدعاء. هذا أحد أسباب اختلاف السرعة بين العملاء.

---

### السبب #3 — فحوصات الصلاحيات في الشريط الجانبي بلا تخزين مؤقت

الشريط الجانبي ([sidebar.blade.php](../resources/views/admin/layout/inc/sidebar.blade.php)، 1,920 سطراً) فيه **72 استدعاءً لـ `@AdminGuardHasPerm`**.

كل استدعاء يمرّ عبر [functions.php:271](../app/Helpers/functions.php#L271) ← `BranchScopedPermissions::adminHasPermission()` الذي ينفّذ في كل مرة:

1. `appliesToAdmin()` → 4 استعلامات `information_schema`
2. `roleIdsForAdmin()` → استعلام `roles` جديد (**بلا حفظ في الذاكرة**)
3. `hasConfiguredBranchPermissions()` → `hasTable` + استعلام `exists`
4. استعلام `exists` نهائي على `role_branch_permissions`

القياس الفعلي في الصفحة الواحدة:

```
284×  select roles.id from roles join model_has_roles ...        199 ms
138×  select exists(... role_branch_permissions ...)              68 ms
142×  select exists(... role_branch_permission_scopes ...)        58 ms
```

بالإضافة إلى `getAllPermissions()` = **150 ms و1,134 صلاحية** لكل طلب.

**لا يوجد memoization ولا cache في `BranchScopedPermissions` إطلاقاً.**

---

### السبب #4 — عدادات الشريط الجانبي بلا تخزين مؤقت

[RecruitmentContractSidebarCountService](../app/Services/RecruitmentContractSidebarCountService.php) = **23 استعلام / 80 ms** مع كل صفحة، وكلها `COUNT(*)` على جدول العقود.

يُضاف إليها عدادات مكتوبة مباشرة داخل Blade:
- [sidebar.blade.php:306-316](../resources/views/admin/layout/inc/sidebar.blade.php#L306-L316) — عدّاد السير المعلّقة مع `whereDoesntHave`
- [sidebar.blade.php:500](../resources/views/admin/layout/inc/sidebar.blade.php#L500) — عدّاد طلبات التمديد

هذه العدادات تتضاعف تكلفتها طردياً مع حجم بيانات العميل — عميل بـ 50,000 عقد سيدفع أضعاف ما يدفعه عميل بـ 545 عقداً.

---

### السبب #5 — `AppServiceProvider::boot()` يمسح القرص ويستعلم مع كل طلب

[AppServiceProvider.php:50-73](../app/Providers/AppServiceProvider.php#L50-L73)

```php
$directories = glob($migrationsPath . '/*', GLOB_ONLYDIR);   // مسح للقرص كل طلب
$this->loadMigrationsFrom($paths);                            // لا لزوم له في طلبات الويب
...
if (Schema::hasTable('new_settings')) { ... }                 // + 4 استعلامات إعدادات كل طلب
```

ومعها زمن التمهيد الكلي **464 ms** (منه 419 ms لتحميل مزوّدي الخدمة وقراءة ملفات `config`) لأن `config:cache` غير مفعّل.

---

### السبب #6 — إعدادات النشر عند العملاء ⚠️ يفسّر "بعض العملاء"

ملف [.env.example](../.env.example) الذي يُنسخ عند تجهيز كل عميل جديد يحتوي:

```
APP_ENV=local          ← يجب أن يكون production
APP_DEBUG=true         ← كارثة أداء وأمان معاً
CACHE_DRIVER=file
QUEUE_CONNECTION=sync  ← أخطرها
```

- **`APP_DEBUG=true`**: Laravel يحتفظ بسجل كل استعلام في الذاكرة. مع 1,762 استعلاماً/صفحة هذا استهلاك ذاكرة ضخم + بطء + كشف بيانات حساسة عند الأخطاء.
- **`QUEUE_CONNECTION=sync`**: كل رسالة SMS / واتساب / إشعار Firebase / توليد PDF **تُنفَّذ داخل الطلب نفسه**. المستخدم ينتظر استجابة مزوّد الرسائل. وفي [functions.php:251](../app/Helpers/functions.php#L251) الطلب الخارجي **بلا `timeout` صريح** — أي أن تعطّل مزوّد SMS = تجمّد الصفحة 30 ثانية.
- **`APP_ENV=local`**: يعطّل إرسال الرسائل فعلياً ([functions.php:233](../app/Helpers/functions.php#L233)) ويغيّر سلوك النظام.
- لا يوجد `bootstrap/cache/config.php` ولا `routes-v7.php` → **2,949 مساراً** تُبنى من الصفر مع كل طلب.
- **OPcache معطّل** في بيئة الفحص.

**هذا بالضبط سبب اختلاف السرعة بين العملاء:** العملاء الذين نُشروا بإعدادات `.env.example` كما هي يعانون، ومن ضُبطت لهم الإعدادات لا يعانون.

---

### السبب #7 — الواجهة الأمامية: ملفات ثقيلة ومستضيفات خارجية ⚠️

[_css.blade.php](../resources/views/admin/layout/inc/_css.blade.php) و[_js.blade.php](../resources/views/admin/layout/inc/_js.blade.php):

**ملفات ضخمة تُحمَّل في كل صفحة:**

| الملف | الحجم |
|---|---:|
| `luno-style.css` | **799 KB** (حاجب للعرض في `<head>`) |
| `tinymce.min.js` | **434 KB** (يُحمَّل حتى في صفحات لا محرر فيها) |
| `plugins.js` | 282 KB |
| `select2.bundle.js` | 159 KB |
| `sweetalert2` | **مُحمَّل مرتين** (نسخة محلية 67 KB + نسخة من jsDelivr) |

**‏22 سكربتاً بلا `defer` ولا `async` إطلاقاً** — تُنفَّذ بالتسلسل، كل واحد يحجب الذي بعده.

**خمسة مستضيفات خارجية** لكل صفحة (كل واحد يحتاج DNS + TLS handshake مستقل):

```
code.jquery.com
cdn.datatables.net
cdnjs.cloudflare.com
cdn.jsdelivr.net
www.codehim.com   ← ⚠️
```

**السطر الأخطر** في [_js.blade.php](../resources/views/admin/layout/inc/_js.blade.php):

```html
<script src="https://www.codehim.com/demo/jquery-image-uploader-preview-and-delete/dist/image-uploader.min.js"></script>
```

سكربت إنتاجي يُحمَّل من **موقع تجريبي (demo)** لطرف ثالث. إن كان بطيئاً أو محجوباً أو متوقفاً عند شبكة عميل معيّن، فالمتصفح **يتوقف عنده** ولا يكمل تنفيذ باقي السكربتات. ومع وجود شاشة تحميل ([page_loader](../resources/views/admin/layout/inc/page_loader.blade.php)) تعتمد على JS للاختفاء، يرى العميل **شاشة تحميل معلّقة** ويصف الموقع بأنه "بطيء جداً" رغم سرعة إنترنته.

هذا وحده يفسّر تماماً حالة "بعض العملاء فقط، رغم سرعة الإنترنت".

---

### السبب #8 — فهارس مفقودة على أعمدة يُبحث بها باستمرار

| الجدول | أعمدة بلا فهرس |
|---|---|
| `travel_histories` | `order_id`, `travel_type`, `arrival_date`, `deleted_at` |
| `maids` | `available`, `deleted_at` |
| `orders` | `deleted_at` |
| `contracts` | `contract_date` |
| `users` | `created_at` |
| `activity_log` | `subject_id`, `causer_id`, `created_at` (7,929 صفاً، 8.9 MB — أكبر جدول) |

الأخطر: `travel_histories.order_id` — لوحة التحكم تربط هذا الجدول وتجمّعه على `order_id` **أربع مرات** ([DashboardController.php:370-373](../app/Http/Controllers/admin/DashboardController.php#L370-L373)، وأسطر 418، 431، 590). كل مرة = مسح كامل للجدول.

---

### السبب #9 — لوحة التحكم نفسها

[DashboardController::professional()](../app/Http/Controllers/admin/DashboardController.php#L74) — **1,885 سطراً**، قياسها: **431 ms / 109 استعلام** بمفردها (بقاعدة صغيرة).

- أبطأ استعلام: `recruitment_offices` بـ **52 ms** — يحتوي `withCount` متعددة + ثلاث `selectSub` متداخلة، كل واحدة تربط `maids` بـ `contracts` بـ `travel_histories`. ينمو أسّياً مع عدد السير.
- `Cache::remember` مستخدم في أجزاء، لكن **إن كان `CACHE_DRIVER=array` عند العميل فهو بلا أثر بين الطلبات** (وهو الإعداد الحالي في `.env`).
- استعلام مهدور: `select * from rental_contract_infos where rental_contract_id in (0)` (18.9 ms).
- [DashboardController.php:1036](../app/Http/Controllers/admin/DashboardController.php#L1036): `'online' => rand(2, 5)` — رقم عشوائي معروض كإحصائية.

---

### السبب #10 — `TranslateHardcodedOutput` على كل استجابة

[TranslateHardcodedOutput.php](../app/Http/Middleware/TranslateHardcodedOutput.php) مسجّل في مجموعة `web` كاملة. عند اللغة الإنجليزية ينفّذ `str_replace` بقاموس كامل على **454 KB من HTML** لكل صفحة، وعلى كل استجابة JSON بشكل تعاودي.

بالنسبة للعملاء الذين يستخدمون الواجهة الإنجليزية، هذا عبء إضافي على كل طلب.

---

### السبب #11 — ضبط MySQL

```
innodb_buffer_pool_size = 16 MB      ← افتراضي منخفض جداً
slow_query_log          = OFF        ← لا توجد رؤية للمشكلة أصلاً
long_query_time         = 10 s
```

مع 16 MB لا يبقى شيء في الذاكرة — كل استعلام يقرأ من القرص. وبإطفاء سجل الاستعلامات البطيئة لا توجد أي وسيلة لمعرفة ما يبطئ عند عميل بعينه.

---

## 3. لماذا "بعض العملاء" تحديداً؟

النظام يُنشر كنسخة منفصلة لكل عميل (يظهر في `.env` أكثر من 15 قاعدة بيانات لعملاء مختلفين). الفروق بينهم:

| العامل | الأثر |
|---|---|
| نُسخ `.env.example` كما هي (`APP_DEBUG=true`، `sync`، `local`) | بطء مضاعف + تجمّد عند إرسال الرسائل |
| لم يُشغَّل `config:cache` / `route:trans:cache` / OPcache | +400 ms لكل طلب |
| حجم البيانات (عدد العقود والسير) | العدادات و`whereDoesntHave` تنمو خطياً |
| عدد الجداول في القاعدة | تكلفة `information_schema` ترتفع |
| تفعيل وحدة الفروع `branches` | يُفعّل مسار `BranchScopedPermissions` كاملاً = مئات الاستعلامات الإضافية |
| عدد الصلاحيات والأدوار | `getAllPermissions()` يهدر وقتاً أطول |
| استضافة مشتركة / `innodb_buffer_pool` صغير | كل شيء من القرص |
| شبكة العميل ووصولها لـ `codehim.com` و`cdnjs` | تجمّد الواجهة رغم سرعة الإنترنت |

---

## 4. خطة الحلول

### المرحلة الأولى — إصلاحات فورية (ساعات، أثر ~80%)

#### 1.1 إصلاح `View::composer('*')` — الأولوية القصوى

المشكلة أن `'*'` يُطلق مع كل view. الحل: **دمج العشرة في composer واحد + تخزين مؤقت + قصر النطاق على الـ layouts فقط**.

في [AppServiceProvider.php](../app/Providers/AppServiceProvider.php)، استبدل الكتلة كاملة (الأسطر 78-137) بـ:

```php
// بيانات ثابتة — تُحسب مرة واحدة لكل طلب وتُخزَّن ساعة كاملة
$shared = function () {
    return Cache::remember('shared_view_data.v1', now()->addHour(), function () {
        return [
            'settings'        => Setting::with('translations')->first(),
            'jobTypes'        => JobType::with('translations')->get(),
            'whatsappNumber'  => getWhatsAppNumber(),
            'printSettings'   => PrintSetting::with('printDetails')->first(),
            'developSettings' => DevelopSetting::first(),
            'skills'          => Skill::all(),
            'countries'       => Country::all(),
            'features'        => Feature::all(),
            'abouts'          => AboutUs::first(),
            'fastnews'        => FastNew::with('translations')->get(),
        ];
    });
};

// View::share يُنفَّذ مرة واحدة للطلب كله — وليس لكل view
foreach ($shared() as $key => $value) {
    View::share($key, $value);
}
```

> ملاحظة: احذف `Schema::hasTable(...)` من هنا — عند قاعدة بيانات سليمة لا معنى لفحص وجود الجداول في كل طلب. إن كانت هناك حاجة للحماية أثناء التهجير، ضع `try/catch` واحداً حول الكتلة.

**الأثر المتوقع: من 475 استعلاماً إلى صفر تقريباً، وتوفير ~1,360 ms.**

يجب إبطال المفتاح `shared_view_data.v1` عند تعديل الإعدادات:
```php
Cache::forget('shared_view_data.v1');
```

#### 1.2 تخزين نتائج `Schema::hasTable/hasColumn` مؤقتاً

أنشئ `app/Support/SchemaCache.php`:

```php
<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SchemaCache
{
    private static array $memo = [];

    public static function hasTable(string $table): bool
    {
        return self::$memo["t:$table"] ??= Cache::remember(
            "schema.has_table.$table", now()->addDay(),
            fn () => Schema::hasTable($table)
        );
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return self::$memo["c:$table.$column"] ??= Cache::remember(
            "schema.has_column.$table.$column", now()->addDay(),
            fn () => Schema::hasTable($table) && Schema::hasColumn($table, $column)
        );
    }

    public static function flush(): void
    {
        self::$memo = [];
        Cache::flush(); // أو امسح المفاتيح المعنية فقط
    }
}
```

ثم استبدل `Schema::hasTable(` / `Schema::hasColumn(` بـ `SchemaCache::` في المسارات الساخنة **فقط**:

- [app/Support/BranchScopedPermissions.php](../app/Support/BranchScopedPermissions.php)
- [app/Models/Concerns/AssignsBranch.php](../app/Models/Concerns/AssignsBranch.php)
- [app/Services/BranchAccessService.php](../app/Services/BranchAccessService.php)
- [app/Http/Middleware/Admin.php](../app/Http/Middleware/Admin.php)
- [app/Http/Controllers/admin/DashboardController.php](../app/Http/Controllers/admin/DashboardController.php)
- [app/Services/RecruitmentContractSidebarCountService.php](../app/Services/RecruitmentContractSidebarCountService.php)

> **مهم:** أضف `SchemaCache::flush()` إلى خطوات النشر بعد أي `php artisan migrate`.

**الأثر المتوقع: توفير ~1,650 ms و890 استعلاماً.**

#### 1.3 حفظ نتائج فحص الصلاحيات في الذاكرة

في [BranchScopedPermissions.php](../app/Support/BranchScopedPermissions.php) أضف memoization لكل طلب:

```php
private static array $memo = [];

public static function appliesToAdmin($admin): bool
{
    $key = 'applies:' . ($admin->id ?? 0);
    return self::$memo[$key] ??= (
        $admin
        && SystemModuleManager::isEnabled('branches')
        && SchemaCache::hasTable('role_branch_permission_scopes')
        && SchemaCache::hasTable('role_branch_permissions')
        && SchemaCache::hasTable('permissions')
        && SchemaCache::hasColumn('admins', 'branch_id')
        && !empty($admin->branch_id)
        && self::roleIdsForAdmin($admin)->isNotEmpty()
    );
}

private static function roleIdsForAdmin($admin): Collection
{
    return self::$memo['roles:' . ($admin->id ?? 0)] ??= (function () use ($admin) {
        // ... المنطق الحالي كما هو
    })();
}
```

والأفضل: تحميل **كل** صلاحيات الفرع للمشرف دفعة واحدة في مصفوفة عند أول فحص، ثم فحص العضوية في الذاكرة بدل استعلام `exists` لكل صلاحية:

```php
private static function branchPermissionSet($admin): array
{
    return self::$memo['set:' . $admin->id] ??= DB::table('role_branch_permissions')
        ->join('permissions', 'permissions.id', '=', 'role_branch_permissions.permission_id')
        ->whereIn('role_branch_permissions.role_id', self::roleIdsForAdmin($admin))
        ->where('role_branch_permissions.branch_id', (int) $admin->branch_id)
        ->where('permissions.guard_name', 'admin')
        ->pluck('permissions.name')
        ->flip()
        ->all();
}

public static function adminHasPermission($admin, string $permission): ?bool
{
    if (!self::appliesToAdmin($admin)) {
        return null;
    }
    return isset(self::branchPermissionSet($admin)[$permission]);
}
```

**الأثر: من 564 استعلاماً إلى استعلامين، وتوفير ~325 ms.**

#### 1.4 تخزين عدادات الشريط الجانبي مؤقتاً

في [RecruitmentContractSidebarCountService::counts()](../app/Services/RecruitmentContractSidebarCountService.php#L12) — غلّف الجسم كاملاً:

```php
public function counts(Request $request): array
{
    $branchId = optional(auth('admin')->user())->branch_id ?: 'all';

    return Cache::remember("sidebar.contract_counts.$branchId", now()->addMinutes(3), function () use ($request) {
        // ... المنطق الحالي
    });
}
```

وانقل عدّادَي [sidebar.blade.php:306](../resources/views/admin/layout/inc/sidebar.blade.php#L306) و[:500](../resources/views/admin/layout/inc/sidebar.blade.php#L500) إلى الخدمة نفسها بدل تنفيذهما داخل Blade.

**الأثر: توفير ~80 ms و23 استعلاماً لكل صفحة.**

#### 1.5 إزالة `glob()` و`loadMigrationsFrom` من مسار الويب

في [AppServiceProvider::boot()](../app/Providers/AppServiceProvider.php#L43) — انقل السطور 49-53 داخل شرط الطرفية:

```php
if ($this->app->runningInConsole()) {
    $migrationsPath = database_path('migrations');
    $this->loadMigrationsFrom(array_merge(
        [$migrationsPath],
        glob($migrationsPath . '/*', GLOB_ONLYDIR)
    ));
    return;
}
```

وخزّن `new_settings` مؤقتاً بدل قراءتها في كل طلب.

---

### المرحلة الثانية — ضبط النشر عند كل عميل (دقائق، أثر كبير)

#### 2.1 تصحيح `.env` عند كل عميل

```env
APP_ENV=production
APP_DEBUG=false
CACHE_DRIVER=redis          # أو file إن لم يتوفر Redis — لكن ليس array أبداً
SESSION_DRIVER=redis        # أو file
QUEUE_CONNECTION=redis      # أو database — ليس sync أبداً
```

#### 2.2 تحديث `.env.example` نفسه

الملف الحالي يشحن `APP_ENV=local` و`APP_DEBUG=true` و`QUEUE_CONNECTION=sync` — وهذا مصدر المشكلة المتكرر عند كل عميل جديد. يجب أن يشحن قيماً إنتاجية آمنة.

#### 2.3 أوامر ما بعد النشر (إلزامية)

```bash
php artisan config:cache
php artisan route:trans:cache
php artisan view:cache
php artisan event:cache
composer dump-autoload -o --classmap-authoritative
php artisan queue:restart
```

مع تشغيل عامل الطابور كخدمة دائمة:
```bash
php artisan queue:work --tries=3 --timeout=90
```
(عبر Supervisor أو systemd)

#### 2.4 تفعيل OPcache على الخادم

```ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=30000
opcache.validate_timestamps=0    ; مع إعادة تحميل php-fpm عند كل نشر
opcache.jit_buffer_size=64M
```

مع 2,949 مساراً و355 جدولاً، غياب OPcache وحده يكلّف مئات الميلي ثانية لكل طلب.

#### 2.5 ضبط MySQL/MariaDB

```ini
innodb_buffer_pool_size = 2G       ; أو 60-70% من ذاكرة الخادم
slow_query_log          = ON
slow_query_log_file     = /var/log/mysql/slow.log
long_query_time         = 0.5
log_queries_not_using_indexes = ON
```

بدون `slow_query_log` لا توجد أي وسيلة لتشخيص بطء عميل بعينه.

---

### المرحلة الثالثة — الواجهة الأمامية (أثر مباشر على تجربة العميل)

#### 3.1 إزالة السكربت المستضاف على موقع تجريبي — عاجل

في [_js.blade.php](../resources/views/admin/layout/inc/_js.blade.php)، احذف:

```html
<script src="https://www.codehim.com/demo/.../image-uploader.min.js"></script>
```

نزّل الملف واستضِفه محلياً تحت `public/admin/assets/js/`. هذا سطر واحد قد يكون السبب المباشر لشكوى "الموقع بطيء جداً رغم سرعة الإنترنت".

#### 3.2 استضافة كل ملفات الطرف الثالث محلياً

انقل `jquery`, `datatables`, `font-awesome`, `sweetalert2` إلى `public/`. الفائدة: إلغاء 5 عمليات DNS + TLS لكل صفحة، وضمان عمل النظام حتى مع حجب CDN.

#### 3.3 إضافة `defer` لكل السكربتات

الـ 22 سكربتاً تُنفَّذ بالتسلسل حالياً. أضف `defer` لكل ما لا يُستدعى فوراً في الصفحة.

#### 3.4 تحميل `tinymce` عند الحاجة فقط

انقل `tinymce.min.js` (434 KB) من [_js.blade.php](../resources/views/admin/layout/inc/_js.blade.php) إلى `@push('js')` في الصفحات التي تستخدم المحرر فقط.

#### 3.5 حذف `sweetalert2` المكرر

مُحمَّل مرتين: نسخة محلية في [_js.blade.php](../resources/views/admin/layout/inc/_js.blade.php) ونسخة من jsDelivr في [index.blade.php:56](../resources/views/admin/layout/indexs/index.blade.php#L56). احذف إحداهما.

#### 3.6 تقليص `luno-style.css` (799 KB)

استخدم PurgeCSS لإزالة القواعد غير المستخدمة، أو حمّل الجزء الحرج فقط في `<head>` والباقي بشكل غير حاجب.

#### 3.7 ضغط وتخزين الأصول

على مستوى الخادم (nginx/apache):

```nginx
gzip on;
gzip_types text/css application/javascript application/json;
brotli on;

location ~* \.(css|js|woff2|png|jpg|svg)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

مع إضافة `?v=` أو `mix()` لكسر الكاش عند التحديث.

---

### المرحلة الرابعة — قاعدة البيانات ولوحة التحكم

#### 4.1 إضافة الفهارس الناقصة

```php
// database/migrations/xxxx_add_performance_indexes.php
Schema::table('travel_histories', function (Blueprint $t) {
    $t->index('order_id');
    $t->index('travel_type');
    $t->index('arrival_date');
    $t->index('deleted_at');
    $t->index(['order_id', 'id']);          // لاستعلامات MAX(id) GROUP BY order_id
});

Schema::table('maids', function (Blueprint $t) {
    $t->index('available');
    $t->index('deleted_at');
    $t->index(['office_id', 'deleted_at', 'available']);
});

Schema::table('orders', function (Blueprint $t) {
    $t->index('deleted_at');
    $t->index(['job_type_id', 'status']);
});

Schema::table('contracts', function (Blueprint $t) {
    $t->index('contract_date');
    $t->index(['type', 'status']);
    $t->index(['status', 'warranty_end_date']);
});

Schema::table('activity_log', function (Blueprint $t) {
    $t->index('created_at');
    $t->index(['subject_type', 'subject_id']);
    $t->index('causer_id');
});

Schema::table('users', fn (Blueprint $t) => $t->index('created_at'));
```

> بعد إضافة أي هجرة: `php artisan test:db-provision` ثم `SchemaCache::flush()`.

#### 4.2 لوحة التحكم — بناء الإحصائيات مسبقاً

`professional()` بـ 1,885 سطراً و109 استعلامات لا يجب أن يُحسب لحظياً. الحل:

1. أمر Artisan مجدول (كل 10-15 دقيقة عبر [AutomationSettings](../app/Support/AutomationSettings.php)) يحسب كل الإحصائيات ويكتبها في جدول `dashboard_snapshots`.
2. الكنترولر يقرأ اللقطة الأخيرة فقط.

هذا يحوّل اللوحة من 431 ms إلى أقل من 20 ms، ويزيل أثر نمو البيانات نهائياً.

كخطوة مؤقتة أسرع: تأكد أن `CACHE_DRIVER` ليس `array` — كل كتل `Cache::remember` الموجودة أصلاً في الكنترولر بلا فائدة حالياً.

#### 4.3 تقسيم `DashboardController`

الملف 1,885 سطراً في دالة واحدة تقريباً. انقل كل قسم إلى خدمة مستقلة تحت [app/Services/](../app/Services/) — يسهّل القياس والتخزين المؤقت لكل قسم على حدة.

#### 4.4 إصلاحات صغيرة

- [DashboardController.php:1036](../app/Http/Controllers/admin/DashboardController.php#L1036): استبدل `rand(2, 5)` بعدد الجلسات النشطة الحقيقي أو احذف البطاقة.
- استعلام `rental_contract_infos where rental_contract_id in (0)` — تحميل مسبق على مجموعة فارغة، يجب تخطّيه.

---

### المرحلة الخامسة — الطلبات الخارجية

#### 5.1 مهلة زمنية على كل طلب HTTP خارجي

في [functions.php:251](../app/Helpers/functions.php#L251) وكل خدمات `app/Services/WhatsApp/`, `Firebase/`, `Moyasar/`, `Zoho/`:

```php
$response = Http::withHeaders($headers)
    ->timeout(8)
    ->connectTimeout(3)
    ->retry(2, 200)
    ->post($url, $body);
```

بدون هذا، تعطّل مزوّد خارجي واحد = تجمّد صفحات النظام.

#### 5.2 نقل كل الإرسال إلى الطابور

SMS، واتساب، Firebase، توليد PDF — كلها يجب أن تكون `Job` تُرسَل بـ `dispatch()` وليس تنفيذاً مباشراً داخل الطلب.

---

### المرحلة السادسة — المراقبة

بدون قياس لن تعرف أي عميل يعاني ولماذا:

1. **Laravel Telescope** على بيئة تجريبية، أو تسجيل مخصص في الإنتاج:
   ```php
   // AppServiceProvider (production)
   DB::listen(function ($q) {
       if ($q->time > 300) {
           Log::channel('slow')->warning('Slow query', [
               'sql' => $q->sql, 'time' => $q->time, 'url' => request()->fullUrl(),
           ]);
       }
   });
   ```
2. **middleware لقياس زمن كل طلب** وتسجيل ما يتجاوز ثانيتين.
3. **تفعيل `slow_query_log`** في MySQL عند كل عميل (البند 2.5).

---

## 5. الأثر المتوقع

| المرحلة | التوفير لكل صفحة |
|---|---:|
| 1.1 إصلاح View composers | ~1,360 ms · 475 استعلام |
| 1.2 تخزين `Schema::has*` | ~1,650 ms · 890 استعلام |
| 1.3 memoization الصلاحيات | ~325 ms · 560 استعلام |
| 1.4 تخزين عدادات الشريط الجانبي | ~80 ms · 23 استعلام |
| 1.5 + 2.3 + 2.4 التمهيد والكاش و OPcache | ~400 ms |
| **الإجمالي** | **~3,800 ms · ~1,950 استعلام** |

**من ~3,500 ms و1,762 استعلاماً إلى أقل من 200 ms و~40 استعلاماً.** أي تحسّن يتجاوز **15 ضعفاً** — قبل أي تحسين في الواجهة الأمامية.

---

## 6. ترتيب التنفيذ الموصى به

| # | المهمة | الجهد | الأثر |
|---|---|---|---|
| 1 | حذف سكربت `codehim.com` واستضافته محلياً | 10 دقائق | 🔴 عالٍ جداً |
| 2 | تصحيح `.env` عند العملاء + `config:cache` + `route:trans:cache` + OPcache | 30 دقيقة | 🔴 عالٍ جداً |
| 3 | إصلاح `View::composer('*')` (البند 1.1) | ساعتان | 🔴 عالٍ جداً |
| 4 | `SchemaCache` (البند 1.2) | 3 ساعات | 🔴 عالٍ جداً |
| 5 | memoization الصلاحيات (البند 1.3) | ساعتان | 🟠 عالٍ |
| 6 | تخزين عدادات الشريط الجانبي (1.4) | ساعة | 🟠 عالٍ |
| 7 | ضبط MySQL + `slow_query_log` | ساعة | 🟠 عالٍ |
| 8 | الفهارس الناقصة (4.1) | ساعتان | 🟠 عالٍ |
| 9 | `defer` + استضافة CDN محلياً + تأجيل tinymce | 3 ساعات | 🟡 متوسط |
| 10 | مهلات HTTP + نقل الإرسال للطابور (5.1، 5.2) | 4 ساعات | 🟡 متوسط |
| 11 | لقطات لوحة التحكم المُجهَّزة مسبقاً (4.2) | يوم | 🟡 متوسط |
| 12 | تقليص `luno-style.css` | يوم | 🟢 منخفض |

**البنود 1 و2 وحدها (أقل من ساعة عمل، بلا تعديل كود تقريباً) قد تحل معظم الشكوى الحالية.**

---

## 7. ملاحظات للتحقق على الخادم الحي

الفحص أعلاه تم على بيئة محلية. للتأكيد عند العميل الذي يشتكي:

```bash
# 1. تحقق من إعدادات البيئة
php artisan tinker --execute="echo config('app.debug').' '.config('app.env').' '.config('cache.default').' '.config('queue.default');"

# 2. تحقق من الكاش
ls -la bootstrap/cache/config.php bootstrap/cache/routes-v7.php

# 3. تحقق من OPcache
php -i | grep opcache.enable

# 4. تحقق من MySQL
mysql -e "SHOW VARIABLES LIKE 'innodb_buffer_pool_size'; SHOW VARIABLES LIKE 'slow_query_log';"

# 5. عدد الجداول (يؤثر على تكلفة information_schema)
mysql -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='<db>';"
```

ومن جهة المتصفح عند العميل: افتح DevTools ← Network ← رتّب حسب Time، وانظر تحديداً إلى الطلبات الموجّهة إلى `codehim.com` و`cdnjs.cloudflare.com` و`cdn.datatables.net`.
