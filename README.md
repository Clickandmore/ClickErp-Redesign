# ClickERP / Platinum ERP

هذا الملف هو فهرس أوامر التشغيل على الاستضافة بعد كل تحديث. التفاصيل مفصولة حتى يسهل أخذ أوامر القسم المطلوب فقط بدون خلط الصلاحيات مع المالية أو الكرون جوب.

## الملفات التشغيلية

```text
docs/PERMISSIONS.md  أوامر الصلاحيات وتجهيز شاشة /dash/perms/1
docs/FINANCE.md      أوامر المالية والشجرة المالية وحسابات التأجير
docs/CRON_JOBS.md    أوامر cron job والمهام المجدولة
docs/CV_EXTRACTION.md أوامر تجهيز استخراج بيانات السيرة الذاتية
```

## قبل أي تحديث على الاستضافة

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

## تحديث الكود والحزم

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

## Composer والكاش بعد أي تحديث

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

## Migrations العامة

```bash
php artisan migrate --force
php artisan migrate:status
```

إذا كانت جداول Laratrust مثل `permission_role` غير موجودة:

```bash
php artisan migrate --path=database/migrations/permissions --force
```

جداول المالية تأتي من `packages/finance/database/migrations` لأن `FinanceServiceProvider` يحملها تلقائيا. إذا احتجت تشغيلها صراحة راجع:

```text
docs/FINANCE.md
```

## بعد كل تحديث

إذا كان التحديث يخص الصلاحيات:

```text
docs/PERMISSIONS.md
```

إذا كان التحديث يخص المالية أو الحسابات:

```text
docs/FINANCE.md
```

إذا كان التحديث يخص المهام المجدولة أو أوامر الخلفية:

```text
docs/CRON_JOBS.md
```

إذا كانت المشكلة في استخراج بيانات السيرة الذاتية أو ظهور رسالة `shell_exec`:

```text
docs/CV_EXTRACTION.md
```

## أوامر آمنة بعد أي تعديل

```bash
composer dump-autoload -o
php artisan optimize:clear
php artisan config:cache
php artisan route:clear
php artisan view:cache
```

إذا ظهرت أخطاء `class not found` بعد `pull`:

```bash
composer dump-autoload -o
php artisan optimize:clear
```

## أوامر لا تشغلها على الإنتاج بدون Backup

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
