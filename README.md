# ClickERP / Platinum ERP

هذا الملف مخصص لأوامر التشغيل على الاستضافة، خصوصا تحديث المشروع وتجهيز الصلاحيات والشجرة المالية الخاصة بالرابط:

```text
https://www.platinumeastern.com/finance/accounts
```

## 1. قبل أي تحديث على الاستضافة

ادخل إلى مسار المشروع:

```bash
cd /home/platinum/public_html
```

راجع حالة الملفات قبل السحب:

```bash
git status
git submodule status --recursive
```

إذا ظهرت ملفات معدلة على السيرفر مثل `.htaccess` أو `composer.lock` أو ملفات داخل `packages/*`، لا تنفذ `git pull` قبل حفظها أو معرفة سبب التعديل.

## 2. تحديث الكود والحزم

تحديث المشروع الرئيسي:

```bash
git pull --ff-only origin main
```

تحديث الحزم الفرعية:

```bash
git submodule sync --recursive
git submodule update --init --recursive
```

الحزم التالية هي `git submodules` وليست مجلدات عادية:

```text
packages/access-control
packages/crm
packages/finance
packages/hr
```

محليا يمكن استخدام السكربت الآمن قبل تحديث الحزم:

```bash
./scripts/safe-submodule-update.sh
./scripts/safe-submodule-update.sh --remote
```

## 3. Composer والكاش

بعد أي تحديث للكود أو الحزم:

```bash
composer install --no-dev --optimize-autoloader
composer dump-autoload -o
php artisan optimize:clear
```

في الإنتاج يجب أن تكون الإعدادات:

```env
APP_ENV=production
APP_DEBUG=false
```

ثم:

```bash
php artisan config:clear
php artisan config:cache
```

## 4. Migrations المطلوبة

تشغيل migrations العامة:

```bash
php artisan migrate --force
```

إذا كانت جداول Laratrust مثل `permission_role` غير موجودة:

```bash
php artisan migrate --path=database/migrations/permissions --force
```

جداول المالية تأتي من `packages/finance/database/migrations` لأن `FinanceServiceProvider` يحملها تلقائيا. إذا احتجت تشغيلها صراحة:

```bash
php artisan migrate --path=packages/finance/database/migrations --force
```

للتحقق:

```bash
php artisan migrate:status
```

## 5. الصلاحيات بعد النشر

الأمر المفضل لتجهيز صلاحيات السيرفر وإعطائها للدور الرئيسي:

```bash
php artisan permissions:prepare-server --role-id=1 --role-name=Administrator --attach-first-admin
php artisan permission:cache-reset
```

أوامر الصلاحيات اليدوية عند الحاجة:

```bash
php artisan access-control:sync-permissions --registry-only --no-assign-roles
php artisan access-control:sync-permissions --include-main
php artisan assign:all-permissions-to-admin
php artisan admin:assign-permissions-to-first
php artisan permission:cache-reset
```

صلاحيات عقود الاستقدام فقط:

```bash
php artisan db:seed --class=RecruitmentContractsPermissionsSeeder --force
php artisan db:seed --class=AssignRecruitmentContractsPermissionsSeeder --force
php artisan db:seed --class=AddRecContractsUpdatePermissionSeeder --force
```

## 6. الشجرة المالية والحسابات

صفحة الحسابات:

```text
/finance/accounts
```

أهم الجداول:

```text
fn_accounts
fn_constraints
fn_constraint_details
fn_bonds
fn_years
fn_cost_centers
```

### تجهيز جداول المالية

```bash
php artisan migrate --path=packages/finance/database/migrations --force
composer dump-autoload -o
php artisan optimize:clear
```

### إنشاء الشجرة المالية من الصفر

تحذير: هذا الأمر يمسح جدول `fn_accounts` ثم يعيد بناء الشجرة الأساسية. لا تستخدمه على قاعدة إنتاج بها قيود أو حسابات فعلية إلا بعد backup.

```bash
php artisan db:seed --class="Nami\\Finance\\Database\\Seeders\\AccountSeeder" --force
```

### إضافة حسابات الاستقدام والتأجير بدون مسح الشجرة

هذا الأمر يستخدم `update/create` للحسابات الخاصة بالاستقدام والتأجير، ويحتاج أن تكون الشجرة الأساسية موجودة أولا:

```bash
php artisan db:seed --class="Nami\\Finance\\Database\\Seeders\\RecruitmentRentalAccountsSeeder" --force
```

### تشغيل Seeders المالية كاملة

تحذير: `FinanceDatabaseSeeder` يشغل `AccountSeeder`، لذلك سيعيد بناء `fn_accounts`.

```bash
php artisan db:seed --class="Nami\\Finance\\Database\\Seeders\\FinanceDatabaseSeeder" --force
```

استخدمه فقط في تركيب جديد أو بعد أخذ نسخة احتياطية.

### التحقق من مسارات الحسابات

```bash
php artisan route:list | grep finance.accounts
php artisan route:list | grep accounting-guide-management
```

روابط مهمة:

```text
/finance/accounts
/finance/accounting-guide-management
/finance/accounts-balances
/finance/account-statement
```

## 7. أوامر آمنة بعد أي تعديل

```bash
composer dump-autoload -o
php artisan optimize:clear
php artisan config:cache
php artisan route:clear
php artisan view:cache
```

إذا ظهرت أخطاء class not found بعد pull:

```bash
composer dump-autoload -o
php artisan optimize:clear
```

## 8. أوامر لا تشغلها على الإنتاج بدون Backup

هذه الأوامر قد تمسح أو تغير بيانات أساسية:

```bash
php artisan db:seed
php artisan db:seed --class=LaratrustSeeder --force
php artisan db:seed --class="Nami\\Finance\\Database\\Seeders\\AccountSeeder" --force
php artisan db:seed --class="Nami\\Finance\\Database\\Seeders\\FinanceDatabaseSeeder" --force
php artisan migrate:fresh
php artisan migrate:refresh
```

قبل أي أمر خطر:

```bash
mysqldump -u DB_USER -p DB_DATABASE > backup-before-change.sql
```

استبدل `DB_USER` و`DB_DATABASE` بقيم الاستضافة الفعلية من ملف `.env`.
