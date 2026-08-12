# خطة تصحيح وضع الصلاحيات

خطة تنفيذ مرحلية. كل مرحلة مستقلة، لها أمر تحقق، ولها طريقة تراجع. لا تبدأ مرحلة قبل أن
تخرج بوابة المرحلة السابقة نظيفة.

المرجع التشغيلي للأوامر هو [PERMISSIONS.md](PERMISSIONS.md). هذا الملف يشرح الترتيب والسبب.

## الوضع الحالي

الأرقام من `php artisan permissions:doctor` على بيئة التطوير:

```text
صفوف الصلاحيات في الجدول        1201
ظاهرة في الشاشة وقابلة للمنح    1143
معطلة عن قصد                     58
صلاحيات يطلبها الكود فعليا       875
مسارات محجوبة عن الجميع            0
صلاحيات مطلوبة وغير مسجلة          0
مربعات ميتة لا تحمي شيئا          148
صلاحيات تُفحص في الواجهات فقط     120
مسارات إدارية بلا أي حماية        250
```

توزيع الـ 250 مسارا المفتوحة: 140 CRM، 99 بوابة الموظفين، 7 موارد HR، 4 متفرقة
(منها 3 شاشات access-control).

شغّل الأمر نفسه على الاستضافة أولا. الأرقام ستختلف، وهي خط الأساس الذي تقارن به لاحقا.

## المرحلة 0 — قبل أي شيء

```bash
mysqldump -u USER -p DB > backup-perms-$(date +%F).sql
php artisan permissions:doctor --json > storage/app/perms-baseline.json
mysql -u USER -p DB -e "SELECT COUNT(*) FROM permissions WHERE is_active = 0;"
```

الاستعلام الأخير مهم. إذا كان الناتج أكبر من صفر على الاستضافة، اعرف لماذا عُطلت تلك
الصفوف قبل المتابعة، لأن سلوك المزامنة تجاهها تغير في هذا التحديث.

## المرحلة 1 — نشر الكود وحده

انشر بدون تشغيل أي أمر صلاحيات، وتحقق أن شيئا لم يتغير.

```bash
git pull --ff-only origin main
composer dump-autoload -o
php artisan optimize:clear
php artisan permissions:doctor
```

**المتوقع: لا فرق عن خط الأساس إطلاقا.** لا صلاحية جديدة، لا صلاحية اختفت، لا مشرف فقد
وصولا. سبب ذلك أن كل ما أُضيف إما يقرأ فقط أو لا يعمل إلا باستدعاء صريح:

```text
PermissionCatalog            قراءة فقط، لا يكتب في قاعدة البيانات
permissions:doctor           قراءة فقط
permissions:prune-orphans    لا يفعل شيئا بدون --apply
محرك حماية CRM والموظفين      مضاف لكنه غير مربوط بأي مسار، فلا يُستدعى
```

التغيير الوحيد في سلوك كود مشترك هو أن `PermissionRegistryService` لم يعد يعيد تفعيل صف
عُطل عن قصد أثناء المزامنة. أثره صفر ما دام الاستعلام في المرحلة 0 أخرج صفرا.

**التراجع:** `git revert` للـ commit. لا أثر في قاعدة البيانات يحتاج تنظيفا.

## المرحلة 2 — إغلاق الثغرات المعروفة

```bash
php artisan permissions:prepare-server --role-id=1 --role-name=Administrator
php artisan permission:cache-reset
php artisan permissions:doctor
```

**البوابة:**

```text
Route required unregistered: 0
مسارات محجوبة عن الجميع: 0
صلاحيات مطلوبة وغير مسجلة: 0
```

**ما يحدث فعليا:** تُنشأ نحو 66 صلاحية جديدة أغلبها `finance_*` و `hr_*` كانت تُطلب في
الكود ولا وجود لها، فينزلق الفحص إلى `finance_module` أو `hr_module` وحدها. أي أن من يملك
الوحدة كان يرى الأرباح والخسائر والإقرار الضريبي وإقفال السنة المالية بلا تفصيل.

**لا أحد يفقد وصولا في هذه المرحلة.** كل بوابة جديدة تقبل صلاحية الوحدة كبديل، فمن كان
يدخل بـ `finance_module` يظل يدخل. الجديد أنك صرت تستطيع منح موظف حق الاطلاع على الفواتير
دون الإقرار الضريبي.

**التغير المرئي:** عدد الصلاحيات في `/dash/perms` يزيد بنحو 66، وتظهر أسماء جديدة تحت
قسمي النظام المحاسبي والموارد البشرية.

**التراجع:** استعادة النسخة الاحتياطية. الصلاحيات الجديدة غير ضارة إن بقيت.

## المرحلة 3 — تنظيف المربعات الميتة

خذ لقطة لربط الأدوار قبل التنفيذ، لأن الصف المعطل يسقط من الأدوار فورا:

```bash
mysqldump -u USER -p DB role_has_permissions > backup-role-perms-$(date +%F).sql
php artisan permissions:prune-orphans                 # عرض فقط، راجع القائمة
php artisan permissions:prune-orphans --apply
php artisan permission:cache-reset
```

**ما يُعطل:** صلاحيات لا يطلبها أي مسار ولا تُذكر في أي واجهة. على بيئة التطوير كانت 58
صلاحية: `cv_*` القديمة المستبدلة بـ `maids_*`، و `finance_*_print_*` المستبدلة بـ `_export`،
و `finance_home_*` المستبدلة بـ `finance_dashboard_*`، و `relegions_view` وهو خطأ إملائي
مكرر عن `religions`.

**ما لا يُعطل:** الأمر يستثني تلقائيا كل صلاحية تخص شاشة إدارية غير محمية بعد، أي
`crm_*` و `employee_*` وشاشات access-control. هذه تبدو ميتة اليوم فقط لأن شاشاتها لا تفحص
شيئا، وتعطيلها الآن يعني أن المرحلة 4 ستقفلها على كل الأدوار.

**التراجع:**

```bash
php artisan permissions:prune-orphans --restore=storage/app/permissions-pruned-TIMESTAMP.json
```

استرجع **قبل** أن يحفظ أحد أي دور من `/dash/perms`. بعد أول حفظ تكون الصفوف المعطلة قد
حُذفت من `role_has_permissions` نهائيا، وعندها يلزم استرجاع `backup-role-perms`.

## المرحلة 4 — إغلاق الـ 250 شاشة المفتوحة

هذه المرحلة الوحيدة التي تغير وصول المستخدمين فعلا، ولذلك تنفذ على خطوتين منفصلتين
بينهما فترة مراقبة.

### 4-أ: امنح أولا

قبل ربط أي middleware، امنح كل دور صلاحيات ما يستخدمه اليوم. الأدوار الحالية غير
`Administrator` و `super_admin` لا تملك ولا صلاحية `crm_*` أو `employee_*` واحدة، فالإغلاق
المباشر يقطعها كلها.

الترتيب المقترح: ابدأ بأدوار يعرف أصحابها العمل عليها، مثل قسم التسويق وخدمة العملاء
لشاشات CRM، وكل الأدوار لبوابة الموظفين لأنها شخصية (إجازات، سلف، تقارير يومية).

نفذ المنح من `/dash/perms/{roleId}` واحفظ، ثم تحقق أن العدد ارتفع لكل دور.

### 4-ب: أغلق

الربط سطران:

```text
packages/crm/routes/crm.php       => أضف "package.route.permission:crm" لمجموعة ['web','admin']
packages/hr/routes/employee.php   => أضف "package.route.permission:employee" لمجموعة "admin"
```

المحرك جاهز في [EnsurePackageRoutePermission](../app/Http/Middleware/EnsurePackageRoutePermission.php)
ويطابق التسمية الحالية: 126 من 140 مسار CRM و 106 من 106 مسار موظفين لها صلاحية مسجلة
بالفعل، والباقي تنشئه المرحلة 2.

ثم:

```bash
php artisan permissions:prepare-server --role-id=1 --role-name=Administrator
php artisan permission:cache-reset
php artisan optimize:clear
php artisan permissions:doctor --strict
```

**البوابة:** `مسارات إدارية بلا أي حماية` تنزل من 250 إلى نحو 4.

**فائدة جانبية:** يعالج هذا فشلا قائما في
`tests/Feature/Security/UngatedAdminRoutesTest::every_export_route_requires_a_permission`
على مسارات `crm.print-price-offers.*`.

**التراجع:** احذف الـ middleware من ملفي المسارات و `php artisan optimize:clear`. الرجوع
فوري ولا يمس قاعدة البيانات.

### 4-ج: المتبقي

بعد CRM وبوابة الموظفين تبقى 7 موارد HR و 3 شاشات access-control و
`admin.no-permissions`. الأخيرة يجب أن تظل مفتوحة بحكم وظيفتها. عالج الباقي بإدخالها في
[AdminRoutePermissionMatrix](../app/Support/AdminRoutePermissionMatrix.php) أو في middleware
الحزمة، ثم حدّث خط الأساس:

```bash
BASELINE_UPDATE=1 vendor/bin/phpunit tests/Feature/Admin/PermissionCatalogTest.php
```

## المرحلة 5 — ترتيب الشاشة نفسها

بعد المراحل السابقة تصبح كل الصلاحيات المعروضة صلاحيات حقيقية. يبقى تحسين العرض:

1. **الـ 120 صلاحية التي تُفحص في الواجهات فقط.** هذه لا يطلبها أي مسار لكنها مستخدمة في
   `@can` داخل القوالب لإخفاء أزرار. مشروعة، لكن يجب توثيقها كي لا تظهر لاحقا كأنها ميتة.
   راجعها بـ `php artisan permissions:doctor --details`.

2. **الأسماء المكررة بمعنى واحد.** مثل `office_*` و `offices_*` و `external_agents_*` التي
   تُعرض كلها تحت "إدارة الوكلاء الخارجيين". اختر اسما واحدا واحذف الباقي بعد نقل الأدوار.

3. **الصفوف بلا `section_name` أو `group_name`.** تسقط في قسم "أخرى" وتُرتب بالاستدلال في
   [PermissionsController](../app/Http/Controllers/Permissions/PermissionsController.php).
   إسنادها صراحة في قاعدة البيانات أدق من الاعتماد على الاستدلال.

4. **ترتيب الأقسام والمجموعات** مكتوب يدويا في `sortOrganizedPermissions`. رتّبه ليطابق
   القائمة الجانبية فعليا حتى يبحث المستخدم عن الصلاحية حيث يرى الشاشة.

## المرحلة 6 — الحوكمة الدائمة

كي لا يعود الوضع للتراجع:

```bash
vendor/bin/phpunit tests/Feature/Admin/PermissionCatalogTest.php   # في CI
php artisan permissions:doctor                                     # بوابة نشر
```

الاختبار يسقط عند إضافة أي شاشة إدارية جديدة بلا حماية، وعند أي صلاحية يطلبها الكود ولا
يستطيع المسجل إنشاءها. وأمر `doctor` يرجع كود خروج غير صفري عند وجود مسار محجوب أو صلاحية
ناقصة، فيصلح كخطوة في سكربت النشر.

القاعدة العملية عند إضافة شاشة: أضف المسار، أضف مدخله في المصفوفة، شغّل
`permissions:prepare-server`، وسيظهر المربع في `/dash/perms` تلقائيا. لا ملف تعريف تحدّثه
يدويا.

## ملخص الأثر

| المرحلة | يتغير للمستخدمين | خطر | تراجع |
|---|---|---|---|
| 1 نشر الكود | لا شيء | لا يوجد | revert |
| 2 المزامنة | تظهر ~66 صلاحية جديدة، لا أحد يفقد وصولا | منخفض | نسخة احتياطية |
| 3 التنظيف | تختفي صلاحيات ميتة من الشاشة | منخفض | ملف الاسترجاع |
| 4 الإغلاق | الأدوار بلا صلاحيات CRM أو الموظفين تُمنع | **مرتفع** | حذف الـ middleware |
| 5 الترتيب | تحسين عرض فقط | منخفض | — |
