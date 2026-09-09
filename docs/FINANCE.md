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

### مزامنة حسابات إنجاز ومساند والتأجير بدون تغيير أرصدة

هذا الأمر آمن للاستخدام على بيئة بها حسابات فعلية: لا يكتب في `fn_constraints` ولا في `fn_constraint_details` ولا ينشئ قيوداً محاسبية، لذلك لا يغير رصيد أي حساب مباشرة. إذا كان الحساب أو الإعداد موجوداً مسبقاً فإنه يتحقق منه فقط ويترك الاختيار الحالي كما هو، ولا يربط الإعداد بالحساب الافتراضي إلا عندما يكون الإعداد فارغاً أو يشير إلى حساب غير موجود.

```bash
php artisan tinker --execute='
$adminId = \App\Models\auth\Admin::query()->value("id") ?: 1;

$accounts = [
    ["code"=>1,"name"=>"الأصول","status"=>"debtor","level"=>1,"type"=>1,"parent_code"=>null],
    ["code"=>2,"name"=>"الخصوم","status"=>"creditor","level"=>1,"type"=>1,"parent_code"=>null],
    ["code"=>4,"name"=>"الإيرادات","status"=>"creditor","level"=>1,"type"=>43,"parent_code"=>null],
    ["code"=>5,"name"=>"المصروفات","status"=>"debtor","level"=>1,"type"=>44,"parent_code"=>null],
    ["code"=>15,"name"=>"الأصول المتداولة قصيرة الأجل","status"=>"debtor","level"=>2,"type"=>107,"parent_code"=>1],
    ["code"=>22,"name"=>"الخصوم قصيرة الأجل","status"=>"creditor","level"=>2,"type"=>108,"parent_code"=>2],
    ["code"=>42,"name"=>"إيراد الخدمات","status"=>"creditor","level"=>2,"type"=>1,"parent_code"=>4],
    ["code"=>51,"name"=>"تكاليف مبيعات الاستقدام","status"=>"debtor","level"=>2,"type"=>1,"parent_code"=>5],
    ["code"=>52,"name"=>"مصروفات تشغيلية مباشرة","status"=>"debtor","level"=>2,"type"=>1,"parent_code"=>5],
    ["code"=>58,"name"=>"مصروفات تشغيلية اخرى","status"=>"debtor","level"=>2,"type"=>1,"parent_code"=>5],
    ["code"=>59,"name"=>"تكاليف مبيعات التأجير","status"=>"debtor","level"=>2,"type"=>1,"parent_code"=>5],
    ["code"=>122,"name"=>"البنوك","status"=>"debtor","level"=>3,"type"=>14,"parent_code"=>15],
    ["code"=>156,"name"=>"ارصدة مدينة اخرى","status"=>"debtor","level"=>3,"type"=>1,"parent_code"=>15],
    ["code"=>222,"name"=>"ارصدة دائنة اخرى","status"=>"creditor","level"=>3,"type"=>1,"parent_code"=>22],
    ["code"=>424,"name"=>"إيراد خدمات العقود الرجالية","status"=>"creditor","level"=>3,"type"=>1,"parent_code"=>42],
    ["code"=>511,"name"=>"تكاليف المبيعات","status"=>"debtor","level"=>3,"type"=>8,"parent_code"=>51],
    ["code"=>591,"name"=>"تكلفة استقدام التأجير - الموردين","status"=>"debtor","level"=>3,"type"=>1,"parent_code"=>59],
    ["code"=>592,"name"=>"رسوم حكومية - التأجير","status"=>"debtor","level"=>3,"type"=>1,"parent_code"=>59],
    ["code"=>593,"name"=>"رواتب تشغيلية - عمالة التأجير","status"=>"debtor","level"=>3,"type"=>1,"parent_code"=>59],
    ["code"=>5416,"name"=>"عمولات مساند","status"=>"debtor","level"=>3,"type"=>1,"parent_code"=>52],
    ["code"=>5450,"name"=>"مصروفات ايواء المتوسط","status"=>"debtor","level"=>3,"type"=>1,"parent_code"=>58],
    ["code"=>5911,"name"=>"تكلفة استقدام عمالة تأجير","status"=>"debtor","level"=>4,"type"=>1,"parent_code"=>591],
    ["code"=>5921,"name"=>"رسوم تأشيرات عمل التأجير","status"=>"debtor","level"=>4,"type"=>1,"parent_code"=>592],
    ["code"=>5922,"name"=>"رسوم إنجاز التأجير","status"=>"debtor","level"=>4,"type"=>1,"parent_code"=>592],
    ["code"=>5923,"name"=>"رسوم إقامات التأجير","status"=>"debtor","level"=>4,"type"=>1,"parent_code"=>592],
    ["code"=>5924,"name"=>"رسوم رخص عمل التأجير","status"=>"debtor","level"=>4,"type"=>1,"parent_code"=>592],
    ["code"=>5925,"name"=>"رسوم تأمين طبي التأجير","status"=>"debtor","level"=>4,"type"=>1,"parent_code"=>592],
    ["code"=>5926,"name"=>"رسوم تأمينات اجتماعية التأجير","status"=>"debtor","level"=>4,"type"=>1,"parent_code"=>592],
    ["code"=>5931,"name"=>"رواتب عمالة التأجير","status"=>"debtor","level"=>4,"type"=>1,"parent_code"=>593],
    ["code"=>12201,"name"=>"حساب مساند","status"=>"debtor","level"=>4,"type"=>111,"parent_code"=>122],
    ["code"=>15600,"name"=>"حساب الضريبة على المصروفات","status"=>"debtor","level"=>4,"type"=>1,"parent_code"=>156],
    ["code"=>15601,"name"=>"حساب مساند وسيط","status"=>"debtor","level"=>4,"type"=>1,"parent_code"=>156],
    ["code"=>22200,"name"=>"حساب الضريبة على الايرادات","status"=>"creditor","level"=>4,"type"=>1,"parent_code"=>222],
    ["code"=>22201,"name"=>"حساب انجاز","status"=>"creditor","level"=>4,"type"=>1,"parent_code"=>222],
    ["code"=>42400,"name"=>"ايرادات تفاويض","status"=>"creditor","level"=>4,"type"=>1,"parent_code"=>424],
    ["code"=>51100,"name"=>"تكاليف تفاويض","status"=>"debtor","level"=>4,"type"=>1,"parent_code"=>511],
];

$settings = [
    "ingaz_delegation_revenue_account"=>42400,
    "ingaz_delegation_ingaz_account"=>22201,
    "ingaz_delegation_cost_account"=>51100,
    "rental_maid_account_recruitment_cost"=>5911,
    "rental_maid_account_visa_fee"=>5921,
    "rental_maid_account_enjaz_fee"=>5922,
    "rental_maid_account_iqama_fee"=>5923,
    "rental_maid_account_work_permit_fee"=>5924,
    "rental_maid_account_medical_insurance"=>5925,
    "rental_maid_account_social_insurance"=>5926,
    "rental_maid_account_salary"=>5931,
    "shelter_living_expense_account"=>5450,
    "musaned_intermediate_account"=>15601,
    "musaned_account"=>12201,
    "musaned_commission_account"=>5416,
    "expense_tax_account"=>15600,
    "revenue_tax_account"=>22200,
];

\DB::transaction(function () use ($accounts, $settings, $adminId) {
    $ids = [];

    foreach ($accounts as $a) {
        $existing = \Nami\Finance\Models\Account::query()->where("code", $a["code"])->first();

        if (! $existing) {
            $parentId = null;
            if ($a["parent_code"] !== null) {
                $parentId = $ids[$a["parent_code"]]
                    ?? \Nami\Finance\Models\Account::query()->where("code", $a["parent_code"])->value("id");
            }

            $payload = [
                "code" => $a["code"],
                "name" => $a["name"],
                "status" => $a["status"],
                "level" => $a["level"],
                "type" => $a["type"],
                "parent_id" => $parentId,
            ];

            if (! \Nami\Finance\Models\Account::query()->whereKey($a["code"])->exists()) {
                $payload["id"] = $a["code"];
            }

            $existing = \Nami\Finance\Models\Account::query()->create($payload);
        }

        $ids[$a["code"]] = $existing->id;
    }

    foreach ($settings as $title => $code) {
        $defaultAccountId = $ids[$code]
            ?? \Nami\Finance\Models\Account::query()->where("code", $code)->value("id");

        $setting = \Nami\Finance\Models\ConfirmSetting::query()->firstOrNew(["title" => $title]);
        $hasValidSelection = $setting->exists
            && $setting->value !== null
            && $setting->value !== ""
            && \Nami\Finance\Models\Account::query()->whereKey($setting->value)->exists();

        if (! $hasValidSelection) {
            $setting->value = $defaultAccountId;
            $setting->created_by_id = $adminId;
            $setting->save();
        }
    }

    foreach (["daily_constraints" => "off", "ingaz_delegation_fixed_cost" => "137"] as $title => $value) {
        $setting = \Nami\Finance\Models\ConfirmSetting::query()->firstOrNew(["title" => $title]);
        if (! $setting->exists || $setting->value === null || $setting->value === "") {
            $setting->value = $value;
            $setting->created_by_id = $adminId;
            $setting->save();
        }
    }
});

echo "Finance accounts verified and missing confirm settings synced without changing existing selections.\n";
'
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

## 9. إعداد «إثبات إيراد عقود الاستقدام»

من صفحة إعدادات الحسابات (`/finance/confirm-settings`) قسم **إثبات إيراد عقود الاستقدام**،
ويُحفظ في `fn_confirm_settings` تحت المفتاح `recruitment_revenue_recognition`
(الكلاس `App\Support\RecruitmentRevenueRecognition`).

| الخيار | القيمة | قيد الفاتورة | عند الوصول | عند الاسترداد / الإرجاع |
|---|---|---|---|---|
| عند وصول العاملة (**الافتراضي**) | `on_arrival` | مدين العميل / دائن التزامات عقود (الجنسية) + الضريبة | التزامات عقود → مبيعات (الجنسية)، وفاتورة المورد الخارجي | كما كان: إشعار دائن يعكس الالتزام قبل الوصول، أو مردود مبيعات + إيواء بعد الوصول |
| عند إنشاء الفاتورة | `on_invoice` | مدين العميل / دائن مبيعات (الجنسية) + الضريبة | لا قيد إيراد؛ فاتورة المورد الخارجي تُثبت عند الوصول كالمعتاد | إشعار دائن يعكس المبيعات والضريبة مباشرة على العميل + قيد مدين المكتب الخارجي / دائن تكلفة المبيعات |

ملاحظات تشغيلية:

- الاختيار يُحفظ على كل فاتورة وقت إنشائها في `invoices.revenue_recognition_mode`، والفواتير
  القديمة (`null`) تُعامل كـ `on_arrival`. تغيير الإعداد لا يغيّر طريقة إقفال العقود المفوترة قبله.
- قيد عكس فاتورة المورد يُحفظ في `invoices.external_office_cost_reversal_constraint_id` ولا يتكرر.
- سند القبض من مساند وقيد الغرامة وخصم مدة البقاء لا يتأثران بالإعداد.
- في الوضع الثاني يجب ربط حساب **مبيعات الجنسية** وحساب **تكلفة مبيعات الجنسية** من قسم حسابات
  الجنسيات، وإلا تُرفض الفاتورة برسالة «لا يوجد حساب مبيعات صالح لهذه الجنسية».
- الترقية: `php artisan migrate --force` ثم `php artisan optimize:clear`، وبعد ذلك
  `php artisan test:db-provision` لبيئة الاختبار.
- الاختبارات: `vendor/bin/phpunit tests/Feature/RecruitmentRevenueRecognitionTest.php`.
