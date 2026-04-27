# أوامر المالية

هذا الملف مخصص للشجرة المالية والحسابات وإعدادات حسابات التأجير.

روابط مهمة:

```text
https://www.platinumeastern.com/finance/accounts
https://www.platinumeastern.com/finance/accounting-guide-management
https://www.platinumeastern.com/finance/accounts-balances
https://www.platinumeastern.com/finance/account-statement
https://www.platinumeastern.com/finance/confirm-settings
```

## 1. الدخول لمسار المشروع

```bash
cd /home/platinum/public_html
```

## 2. تجهيز جداول المالية

جداول المالية تأتي من `packages/finance/database/migrations`.

```bash
php artisan migrate --path=packages/finance/database/migrations --force
composer dump-autoload -o
php artisan optimize:clear
```

للتحقق:

```bash
php artisan migrate:status
```

أهم الجداول:

```text
fn_accounts
fn_constraints
fn_constraint_details
fn_bonds
fn_years
fn_cost_centers
fn_confirm_settings
```

## 3. إنشاء الشجرة المالية من الصفر

تحذير: هذا الأمر يمسح جدول `fn_accounts` ثم يعيد بناء الشجرة الأساسية. لا تستخدمه على قاعدة إنتاج بها قيود أو حسابات فعلية إلا بعد أخذ نسخة احتياطية.

```bash
php artisan db:seed --class="Nami\\Finance\\Database\\Seeders\\AccountSeeder" --force
```

## 4. إضافة حسابات الاستقدام والتأجير بدون مسح الشجرة

هذا الأمر يستخدم `update/create` للحسابات الخاصة بالاستقدام والتأجير، ويحتاج أن تكون الشجرة الأساسية موجودة أولا:

```bash
php artisan db:seed --class="Nami\\Finance\\Database\\Seeders\\RecruitmentRentalAccountsSeeder" --force
```

## 5. إعدادات النظام المحاسبي لحسابات التأجير

صفحة الإعدادات:

```text
https://www.platinumeastern.com/finance/confirm-settings
```

قبل ضبط الصفحة تأكد من وجود جداول المالية وحسابات التأجير:

```bash
php artisan migrate --path=packages/finance/database/migrations --force
php artisan db:seed --class="Nami\\Finance\\Database\\Seeders\\RecruitmentRentalAccountsSeeder" --force
php artisan optimize:clear
```

ربط حسابات مصروفات عاملات التأجير داخل `fn_confirm_settings` حسب أكواد الشجرة المالية:

```bash
php artisan tinker --execute='use Nami\Finance\Models\Account; use Nami\Finance\Models\ConfirmSetting; $settings = ["rental_maid_account_recruitment_cost" => 5911, "rental_maid_account_visa_fee" => 5921, "rental_maid_account_enjaz_fee" => 5922, "rental_maid_account_iqama_fee" => 5923, "rental_maid_account_work_permit_fee" => 5924, "rental_maid_account_medical_insurance" => 5925, "rental_maid_account_social_insurance" => 5926, "rental_maid_account_salary" => 5931]; foreach ($settings as $key => $code) { $account = Account::where("code", $code)->whereDoesntHave("children")->first(); if (! $account) { throw new RuntimeException("Account {$code} not found"); } ConfirmSetting::updateOrCreate(["title" => $key], ["value" => $account->id, "created_by_id" => 1]); }'
```

الأكواد المرتبطة في صفحة الإعدادات:

```text
rental_maid_account_recruitment_cost       => 5911 تكلفة استقدام عمالة تأجير
rental_maid_account_visa_fee               => 5921 رسوم تأشيرات عمل التأجير
rental_maid_account_enjaz_fee              => 5922 رسوم إنجاز التأجير
rental_maid_account_iqama_fee              => 5923 رسوم إقامات التأجير
rental_maid_account_work_permit_fee        => 5924 رسوم رخص عمل التأجير
rental_maid_account_medical_insurance      => 5925 رسوم تأمين طبي التأجير
rental_maid_account_social_insurance       => 5926 رسوم تأمينات اجتماعية التأجير
rental_maid_account_salary                 => 5931 رواتب عمالة التأجير
```

للتحقق من المسار والإعدادات:

```bash
php artisan route:list | grep finance.confirm-settings
php artisan tinker --execute='use Nami\Finance\Models\ConfirmSetting; dump(ConfirmSetting::whereIn("title", ["rental_maid_account_recruitment_cost", "rental_maid_account_visa_fee", "rental_maid_account_enjaz_fee", "rental_maid_account_iqama_fee", "rental_maid_account_work_permit_fee", "rental_maid_account_medical_insurance", "rental_maid_account_social_insurance", "rental_maid_account_salary"])->pluck("value", "title")->all());'
```

## 6. تشغيل Seeders المالية كاملة

تحذير: `FinanceDatabaseSeeder` يشغل `AccountSeeder`، لذلك سيعيد بناء `fn_accounts`.

```bash
php artisan db:seed --class="Nami\\Finance\\Database\\Seeders\\FinanceDatabaseSeeder" --force
```

استخدمه فقط في تركيب جديد أو بعد أخذ نسخة احتياطية.

## 7. التحقق من مسارات الحسابات

```bash
php artisan route:list | grep finance.accounts
php artisan route:list | grep accounting-guide-management
```

## 8. ترتيب مختصر بعد تحديث مالي

```bash
cd /home/platinum/public_html
git pull --ff-only origin main
git submodule sync --recursive
git submodule update --init --recursive
composer install --no-dev --optimize-autoloader
composer dump-autoload -o
php artisan migrate --force
php artisan migrate --path=packages/finance/database/migrations --force
php artisan db:seed --class="Nami\\Finance\\Database\\Seeders\\RecruitmentRentalAccountsSeeder" --force
php artisan optimize:clear
php artisan config:cache
```

بعدها افتح:

```text
https://www.platinumeastern.com/finance/accounts
```
