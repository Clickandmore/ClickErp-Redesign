# أوامر الصلاحيات

## الصلاحيات التي تتطلب تفعيلًا يدويًا

توجد صلاحيات اختيارية في `config/manual_permission_opt_ins.php`. ينشئها النظام ويعرضها في شاشة
`/dash/perms/{roleId}`، لكن أمر `permissions:prepare-server` لا يمنحها تلقائيًا لأي مشروع أو دور.
إذا كانت الصلاحية ممنوحة يدويًا من قبل، يحافظ الأمر عليها ولا يلغيها.

صلاحية `rec_contracts_view_shelter` تُظهر زر «عقود الإيواء» داخل شاشة عقود الاستقدام، وتسمح بعرض
عقود الحالة `shelter` من ذلك الزر فقط. يجب منحها يدويًا للدور المطلوب.

## صلاحية حذف تحويلات مساند

الصلاحية المستقلة `musaned_transfers_delete` تُظهر زر حذف تحويل مساند وتسمح بتنفيذ الحذف.
صلاحية الإدارة الشاملة `musaned_transfers_control` تسمح بالعملية أيضًا. عند النشر شغّل الترحيلات ثم أمر
`permissions:prepare-server` المعتمد أدناه حتى تظهر الصلاحية الجديدة في شاشة الأدوار.

هذا الملف مخصص لتجهيز الصلاحيات بعد النشر أو بعد أي تحديث يضيف أو يعدل صلاحية.

لخطة التصحيح المرحلية وأثر كل خطوة على المستخدمين، انظر
[PERMISSIONS_CLEANUP_PLAN.md](PERMISSIONS_CLEANUP_PLAN.md).

روابط التحقق:

```text
محلي:      http://127.0.0.1:8000/dash/perms/1
استضافة:  https://www.platinumeastern.com/dash/perms/1
```

الرقم `1` هو رقم الدور الرئيسي غالبا. إذا كان دور المدير في الاستضافة له رقم مختلف، استبدله في الرابط وفي الأمر `--role-id`.

## 1. الدخول لمسار المشروع

```bash
cd /home/platinum/public_html
```

## 2. تحديث الكود والحزم

```bash
git pull --ff-only origin main
git submodule sync --recursive
git submodule update --init --recursive
composer install --no-dev --optimize-autoloader
composer dump-autoload -o
php artisan optimize:clear
```

## 3. تشغيل migrations المطلوبة للصلاحيات

```bash
php artisan migrate --force
```

إذا كانت جداول Laratrust مثل `permission_role` غير موجودة:

```bash
php artisan migrate --path=database/migrations/permissions --force
```

تحقق من حالة migrations:

```bash
php artisan migrate:status
```

## 4. الأمر المعتمد لتجهيز كل صلاحيات السيرفر

هذا هو الأمر المفضل بعد أي تحديث صلاحيات. يقوم بمزامنة كل الصلاحيات الظاهرة في شاشة الصلاحيات، ثم يعطيها للدور الرئيسي، ويمكنه ربط أول مدير بهذا الدور.

```bash
php artisan permissions:prepare-server --role-id=1 --role-name=Administrator --attach-first-admin
php artisan permission:cache-reset
```

المهم بعد تنفيذ الأمر أن يظهر في نهاية الخرج:

```text
Final verification:
Screen total: ...
Role selected: ...
Missing selected: 0
Route required total: ...
Route required unregistered: 0
```

`Route required unregistered` هو الرقم الأهم للشاشات الجديدة: يعني أن كل صلاحية يطلبها
أي مسار إداري صارت صفا في جدول `permissions`، أي مربع اختيار ظاهر في `/dash/perms`.
`Missing selected` وحده لا يكفي لأنه يقارن الدور بالجدول فقط، لا الجدول بالمسارات.

لا تعتمد على رقم ثابت مثل `1047` أو `1013` لأن الرقم يتغير حسب آخر migrations وتعريفات الصلاحيات الموجودة على السيرفر. المعيار الصحيح هو أن يكون:

```text
Role selected = Screen total
Missing selected = 0
```

إذا كان دور المدير ليس رقمه `1`، عدل الرقم:

```bash
php artisan permissions:prepare-server --role-id=ROLE_ID --role-name=Administrator --attach-first-admin
php artisan permission:cache-reset
```

## 4.1. الفحص الشامل قبل وبعد التنفيذ

```bash
php artisan permissions:doctor
php artisan permissions:doctor --details      # القوائم الكاملة
php artisan permissions:doctor --json         # للتصدير
php artisan permissions:doctor --module=crm   # وحدة واحدة
```

الأمر يقرأ فقط ولا يعدل شيئا. يعتمد على `App\Support\PermissionCatalog` الذي يستخرج
الصلاحيات من المسارات نفسها، فلا يحتاج تحديث ملف تعريف عند إضافة شاشة جديدة.

معنى الأرقام:

```text
مسارات محجوبة عن الجميع    يجب أن يكون 0. غير ذلك يعني شاشة لا يفتحها حتى المدير.
صلاحيات مطلوبة وغير مسجلة  يجب أن يكون 0 بعد permissions:prepare-server.
صلاحيات لا يستخدمها شيء    مربعات اختيار لا تحمي شيئا، مرشحة للحذف.
مسارات إدارية بلا أي حماية  شاشات يفتحها أي مشرف مسجل دخول مهما كان دوره.
```

الأمر يرجع كود خروج غير صفري عند وجود مسارات محجوبة أو صلاحيات ناقصة، فيصلح للاستخدام
في خطوة نشر آلية. أضف `--strict` ليعتبر الشاشات غير المحمية خطأ أيضا.

## 4.2. تنظيف المربعات الميتة

```bash
php artisan permissions:prune-orphans              # عرض فقط
php artisan permissions:prune-orphans --apply      # تنفيذ
php artisan permissions:prune-orphans --restore=storage/app/permissions-pruned-YYYYmmdd-HHMMSS.json
```

يعطل بـ `is_active=0` كل صلاحية لا يطلبها أي مسار ولا تُذكر في أي واجهة، ويحفظ ملف
استرجاع في `storage/app`. لا يحذف شيئا.

يستثني تلقائيا الصلاحيات المحجوزة لشاشات إدارية غير محمية بعد، مثل CRM وبوابة الموظفين
وشاشات access-control، لأن تعطيلها اليوم يعني أن حماية تلك الشاشات لاحقا ستقفلها على كل
الأدوار. لتجاوز هذا الاستثناء استخدم `--include-earmarked`.

تحذيران مهمان:

```text
موديل Permission يطبق global scope على is_active، فالصف المعطل يختفي عن كل استعلامات
النظام. أول حفظ لأي دور من /dash/perms بعد التعطيل يزيل الصلاحيات المعطلة من
role_has_permissions نهائيا، لأن استعلام الحفظ يستبعد المعطل.

أمر permissions:prepare-server لا يعيد تفعيل ما عُطل عن قصد. الاسترجاع بـ --restore فقط.
```

## 5. التحقق بعد التنفيذ

افتح شاشة صلاحيات الدور:

```text
https://www.platinumeastern.com/dash/perms/1
```

## إعداد تجاوز قفل تعديل السيرة المرتبطة بمعاملات

لم يعد `maids_transaction_edit_override` صلاحية مرتبطة بالأدوار، ولا يجب أن تظهر في شاشة
`/dash/perms/{roleId}`. أصبح التحكم إعدادًا عامًا من:

```text
/admin/settings/cv-options
```

يتطلب الوصول إلى الصفحة صلاحية `settings_cv_options`. عند تفعيل خيار «السماح بتعديل السيرة
المرتبطة بمعاملات» يمكن لكل مستخدم يملك صلاحية تعديل السير تجاوز قفل الحقول المرتبطة بطلبات أو
عقود نشطة أو فواتير أو قيود محاسبية أو إرساليات مكاتب خارجية. يفضّل تعطيل الخيار بعد انتهاء الحاجة إليه.

ثم تأكد من التالي:

```text
الصلاحيات الجديدة ظاهرة في الشاشة.
الدور Administrator لديه الصلاحيات المطلوبة.
لا توجد صفحة تظهر 403 لمستخدم المدير.
```

أوامر تحقق مساعدة:

```bash
php artisan route:list | grep dash.perms
php artisan permission:cache-reset
php artisan optimize:clear
```

لا تفصل صلاحيات عقود الاستقدام أو التأجير أو أي قسم آخر بأوامر مستقلة. بعد أي تحديث استخدم الأمر الشامل فقط:

```bash
php artisan permissions:prepare-server --role-id=1 --role-name=Administrator --attach-first-admin
php artisan permission:cache-reset
```

إذا كان إجمالي الاستضافة أقل من المحلي، مثل المحلي يعرض رقما أعلى والاستضافة تعرض رقما أقل، فهذا يعني غالبا أن الاستضافة لم تشغل كل migrations أو لم تحدث الحزم الفرعية. نفذ بالترتيب:

```bash
git status
git submodule status --recursive
git pull --ff-only origin main
git submodule sync --recursive
git submodule update --init --recursive
composer install --no-dev --optimize-autoloader
composer dump-autoload -o
php artisan migrate --force
php artisan migrate:status
php artisan permissions:prepare-server --role-id=1 --role-name=Administrator --attach-first-admin
php artisan permission:cache-reset
php artisan optimize:clear
```

بعدها راجع آخر خرج للأمر. إذا كان `Missing selected` ليس `0`، لا تعتمد على الشاشة قبل معالجة الأسماء الناقصة التي يطبعها الأمر.

## 6. ترتيب مختصر بعد أي تحديث صلاحية

### صلاحيات إدارة لغات السير الذاتية

صفحة `/admin/languages` تستخدم صلاحيات مستقلة حتى يمكن فصل العرض والإضافة والتعديل والحذف
والإظهار/الإخفاء:

```text
maid_languages_view
maid_languages_create
maid_languages_edit
maid_languages_delete
maid_languages_toggle_visibility
```

يحافظ النظام على حقل `code` ثابت لكل لغة لأنه القيمة المخزنة في `maids.languages` والمرسلة
إلى المشاريع المرتبطة عبر الـAPI. تعديل عنوان اللغة لا يغير هذا الرمز.

```bash
cd /home/platinum/public_html
git pull --ff-only origin main
git submodule sync --recursive
git submodule update --init --recursive
composer install --no-dev --optimize-autoloader
composer dump-autoload -o
php artisan migrate --force
php artisan permissions:prepare-server --role-id=1 --role-name=Administrator --attach-first-admin
php artisan permission:cache-reset
php artisan optimize:clear
php artisan config:cache
```

بعدها افتح:

```text
https://www.platinumeastern.com/dash/perms/1
```
