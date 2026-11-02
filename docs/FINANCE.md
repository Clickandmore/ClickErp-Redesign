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

## 10. دليل ترتيب الشجرة الحالية على شجرة البرنامج (قراءة فقط)

يولّد ملف إكسل توجيهياً للمحاسب يقارن `fn_accounts` ببذرتي `AccountSeeder` و`RecruitmentRentalAccountsSeeder`:
ورقة لكل إجراء (إعادة تسمية، إنشاء، نقل، دمج، حذف، قرار المحاسب، بلا تغيير). الأمر لا يعدّل أي بيانات.

```bash
php artisan finance:chart-merge-guide                       # finance_tree/chart-merge-guide-<تاريخ>.xlsx
php artisan finance:chart-merge-guide --output=/tmp/guide.xlsx
```

القرارات الخاصة بالحسابات التي لا يكفي اسمها للمطابقة مكتوبة في ثابت `OVERRIDES` داخل
`app/Console/Commands/ChartMergeGuideCommand.php`؛ عدّلها هناك ثم أعد التوليد.

## 11. دفتر الإيواء المستقل، قيد الترحيل، وإشعار الدائن للإرجاع

المرجع: `docs/finance/قيود_نظام_كليك_رحلة_الاستقدام_v3b.pdf` (القيود 9 و10 و11 و15–18).

### دفتر الإيواء (`App\Services\ingaz\ShelterCostLedgerService`)

لكل عاملة رصيد على «مصروفات مدفوعة مقدماً - إيواء (الجنسية)» بمركز تكلفة «إيواء عاملة #»، يُتتبع في
`shelter_cost_entries` (موجب يزيد الأصل، سالب يُفرج عنه، فريد على `source_type/source_id/kind`).

| الحركة | متى | مدين | دائن |
|---|---|---|---|
| S1 `capitalize_office_cost` | موافقة الإرجاع داخل الضمان (الوضع الافتراضي) | أصل الإيواء | مردودات تكلفة (الجنسية) |
| S2 `living_expense` | مصروف الإعاشة حين `shelter_living_expense_capitalize = on` | أصل الإيواء | بنك/صندوق/عهدة |
| S3 `release_to_trial` / `return_from_trial` | بدء نقل الخدمات / فشل التجربة | إيواء التجربة / أصل الإيواء | أصل الإيواء / إيواء التجربة |
| 18 `release_to_deportation` | إنهاء الترحيل | تكلفة الوكيل المتبقية على المكتب/الخسائر، والإعاشة على الخسائر افتراضياً | أصل الإيواء |
| S4 `release_to_sale` | فوترة عقد استقدام لعاملة في الإيواء | تكاليف مبيعات (الجنسية) | أصل الإيواء |
| `write_off` | إخراج يدوي من الإيواء مع رصيد و`write_off_reason` | حساب التصفية | أصل الإيواء |
| `return_to_customer` | حذف من الإيواء لعاملة دخلت مباشرة على عقدها ورجعت لعميلها (البيع والمورد قائمان على العقد الأصلي). يُرفض إن وُجد استرداد تكلفة وكيل غير معكوس | مردودات التكلفة (نفس حساب قيد الاستلام)، وحساب التصفية لأي إعاشة مرسملة | أصل الإيواء |

- الإخراج اليدوي (`ShelterContract.back`) يُرفض (422) إذا كان رصيد العاملة > 0 بلا سبب تصفية.
- حسابات الجنسيات تُحل عبر `App\Support\NationalityAccountResolver`: المفتاح `nationality_account_{id}_{type}` أولاً،
  ثم الاسم بعد تطبيع الهمزة («ايواء» = «إيواء»، «مردودات» تُلتقط بنمط «مردود%»).
- الإعدادات في صفحة إعدادات الحسابات، قسم «حسابات الإيواء والترحيل» (`App\Support\ShelterAccountingSettings`).

### الإرجاع داخل الضمان (`RecruitmentContractWarrantyReturnService`)

الموافقة تُصدر ثلاثة مستندات تُحفظ على `contract_return_requests`:
`credit_note_id` (إشعار دائن بكامل الفاتورة: مدين مردودات مبيعات استقدام + الضريبة / دائن العميل، ويُرسل لزاتكا)،
`return_constraint_id` (خصم مدة البقاء: مدين العميل / دائن إيراد استرداد مبيعات)، `shelter_constraint_id` (S1).
في وضع «عند الفاتورة» تُعكس فاتورة المورد الخارجي بدل S1. إعادة المعالجة لا تكرر شيئاً.

إشعار الدائن (للإرجاع وللاسترداد) يُدين «مردودات مبيعات استقدام (الجنسية)» عندما يكون الإيراد قد أُثبت
(قيد إيراد وصول موجود أو وضع «عند الفاتورة»)، ويُدين الالتزام قبل ذلك. حساب غرامة الاسترداد من
`recruitment_refund_penalty_account` إن ضُبط.

### الترحيل (`DeportationCompletionService`)

الإنهاء من شاشة الإيواء أو شاشة رحلات الترحيل يمر بخدمة واحدة: القيد 18 (يُحفظ في
`deportation_procedures.constraint_id`)، `maids.status = deported` وغير متاحة، العقد «مرتجع» مع سجل حالة.
إلغاء إجراء بدأ خطأً: `POST ShelterContract/deportation/{id}/cancel` مع `reason` (قبل اكتمال الرحلة).
إكمال رحلة ترحيل من شاشة السفر العامة مرفوض.

### الحذف

القيود الآلية لا تُحذف من شاشة القيود (`App\Support\ConstraintReferenceGuard`)، مسار حذف الفاتورة أُزيل،
حذف عقد الإيواء = الإخراج، تحويل مساند لا يُحذف إن بُني عليه استرداد، ومصروف الإعاشة يُعكس
(`ShelterLivingExpenseAccountingService::reverse`) ولا يُحذف.

### تصحيح القيود السابقة

```bash
php artisan finance:shelter-ledger-reconcile            # عرض فقط
php artisan finance:shelter-ledger-reconcile --apply    # بناء الدفتر من القيود المثبتة فقط
php artisan finance:shelter-agent-cost-audit             # مراجعة تكلفة الوكيل والإعاشة
# بعد مراجعة المحاسب، لإنشاء قيود الترحيل الناقصة:
php artisan finance:shelter-ledger-reconcile --apply --post-missing-deportations
```

يبني الدفتر من القيد 9 القديم وتخصيصات الإعاشة المطابقة لقيود أصل الإيواء وقيود نقل الخدمات، ثم يعرض الأرصدة المفتوحة.
إنشاء القيد 18 للترحيل المكتمل بلا قيد يحتاج `--post-missing-deportations` مع `--apply` بعد المراجعة. القيود القديمة نفسها لا تُعدَّل.

الترقية: تطبيق الهجرات ثم `php artisan optimize:clear`. أمر `test:db-provision` لبيئة الاختبار فقط ويعيد إنشاء قاعدتها.
الاختبارات: `ShelterCostLedgerTest`, `ConstraintDeletionGuardTest`, `ShelterAccountingSettingsTest`, `RecruitmentRevenueRecognitionTest`.

## 12. استرداد تكلفة الوكيل قبل الترحيل

تفاصيل الدورة الجديدة، معالجة السابق، إعدادات الإعاشة والصلاحيات، وخطوات المحاسب في [دليل استرداد تكلفة الوكيل](finance/shelter-agent-cost-recovery.md). الاسترداد يخص تكلفة المكتب الأصلية فقط؛ الإعاشة عند الترحيل على حساب الخسائر افتراضياً باختيار مستقل.

## 13. إرجاع مراحل عقد الاستقدام ضمن السياق المالي

العقود مرتبطة بفواتير وقيود تُنشأ في مراحل بعينها، لذلك لا يُسمح بالقفز الحر بين المراحل.
`App\Services\ingaz\RecruitmentContractStageRollbackService` هي المصدر الوحيد للإرجاع، وتنقل العقد
**خطوة واحدة فقط** إلى الوراء: مكتمل ← الوصول ← تذكرة ← تفييز ← تفويض ← مساند ← جديدة.
زر «إرجاع إلى …» في قائمة العقود (وزر العقد المكتمل في عمود تحديث الحالة) يستدعي نفس الخدمة،
ونافذة «تحديث الحالة» ترفض للعقد المكتمل أي هدف غير المرحلة السابقة مباشرة.

يُرفض الإرجاع، ولا يُغيَّر شيء، عند وجود أي مما يلي:

| العائق | متى يُفحص |
|---|---|
| استرداد مالي، إشعار دائن، نقل خدمات غير فاشل، طلب إرجاع داخل الضمان قيد المعالجة، ترحيل مكتمل | كل إرجاع |
| ضمان منتهٍ فعلاً بتاريخ وصول حقيقي (المهمة اليومية ستعيده إلى مكتمل) | مكتمل ← الوصول |
| قيد إيراد الوصول، قيد تكلفة المكتب الخارجي غير المعكوس، رحلة مكتملة | أي إرجاع يتخطى مرحلة الوصول |
| تحويل مساند مرتبط بالعقد | أي إرجاع يتخطى مرحلة مساند |

عند العودة إلى ما قبل الوصول تُصفَّر حقول الضمان (`not_applicable`)، وعند العودة إلى الوصول يُعاد حساب
الضمان من تاريخ الوصول الفعلي. تواريخ الضمان الصفرية الموروثة من الاستيراد (`0000-00-00`) لا تُعامل
كتاريخ وصول. كل إرجاع يُسجَّل في `contract_status_logs` مع `previous_status`.
العقود المستردة والمرتجعة لا تُرجع من هنا (المرتجع يُستعاد عبر «التراجع عن الإرجاع»).

الاختبارات: `RecruitmentContractStageRollbackTest`.

## 14. حذف ونقل ودمج حسابات الدليل المحاسبي

أزرار في شاشة «إدارة الدليل المحاسبي» (`/finance/accounting-guide-management`)، والمنطق كله في
`Nami\Finance\Services\AccountRestructureService`. كل عملية تُسجَّل في `fn_account_restructure_logs`
(لقطة الحساب، السبب، المنفّذ، وما تأثر به).

| العملية | ما يحدث | يُرفض عند |
|---|---|---|
| حذف | حذف الحساب وروابط فروعه | فروع، أو قيود، أو أي ارتباط (عميل، مورد، وكيل، بنك، صندوق، إعداد حسابات…) |
| نقل | تغيير الحساب الرئيسي وإعادة ترقيم الحساب وكل فروعه ومستوياتها؛ القيود تبقى عليه | النقل تحت نفسه أو فروعه، أو تحت حساب عليه قيود، أو حساب «خاص» |
| دمج | نقل كل سطور القيود وكل الارتباطات وإعدادات الحسابات من المصدر إلى الهدف، ثم حذف المصدر | مصدر أو هدف له فروع، أو سطور للمصدر داخل فترة مقفلة (إقفال سنة غير معكوس) |

محمي من الثلاثة: المستويان 1 و2 (هيكل الدليل)، والحسابات التي يبحث عنها الكود بكودها
(`AccountRestructureService::PROTECTED_CODES` ومراسي `config/branch_finance.php`). النقل يُرفض كذلك إن كان
أحد الفروع محمياً لأن كوده سيتغير. `operation_type` لا يصلح للحماية: قيمته الافتراضية 1 على كل الحسابات تقريباً.

قائمة الأعمدة التي تحمل رقم حساب في `AccountRestructureService::REFERENCES`. **أي جدول جديد يحمل حساباً
يُضاف إليها**، وإلا لن يُنقل عند الدمج ولن يمنع الحذف. الإعدادات في `fn_confirm_settings` تُنقل فقط إن احتوى
اسمها على `account` (قيم رقمية أخرى مثل نسب الضريبة قد تساوي رقم حساب صدفة).

الصلاحيات حصرية ولا تكفي `finance_module` لها: `finance_accounting_guide_management_delete`
(وتحمي أيضاً `finance.accounts.destroy`)، `..._move`، `..._merge`. الدمج يتطلب سبباً وكتابة كود الحساب المصدر للتأكيد.

### التراجع عن الدمج

زر «سجل التعديلات» في نفس الشاشة يعرض آخر 100 عملية، وبجانب كل دمج لم يُعكس زر «تراجع»
(صلاحية `..._merge` نفسها). التراجع يعيد إنشاء الحساب المصدر **بنفس رقمه وكوده وأبيه** من لقطة السجل،
ويعيد إليه الصفوف المحفوظة أرقامها في `details.moved_ids` **التي ما زالت تشير إلى الهدف فقط**؛ أي قيد أو ربط
جديد سُجّل على الهدف بعد الدمج يبقى عليه، والصفوف التي تغيّرت تُذكر في رسالة النتيجة. تعود كذلك إعدادات
الحسابات وروابط الفروع، ويُعلَّم سجل الدمج `reversed_at` ويُضاف سجل `merge_undo`.

يُرفض التراجع إن: حُذف الهدف أو دُمج بعدها (تراجع عن العملية اللاحقة أولاً)، أو أصبح الكود مستخدماً لحساب آخر،
أو حُذف الحساب الرئيسي الأصلي، أو صارت سطور القيود المنقولة داخل فترة مقفلة، أو سبق التراجع.

الاختبارات: `tests/Feature/Finance/AccountRestructureTest.php`.

## 15. ربط «تقرير حركة صندوق اليومية» برقم الحساب

بنود التقرير (`Nami\Finance\Services\DailyCashReferenceService`) مكتوبة بأكواد الحسابات في
`packages/finance/config/daily_cash_reference.php`، وهي أكواد شجرة الرافدين. الكود يتغير مع كل نقل أو إعادة ترقيم،
فيُربط كل بند برقم الحساب في `fn_daily_cash_reference_lines` لكل شركة على حدة:

```bash
php artisan migrate                                   # ينشئ الجدول فارغاً
php artisan finance:link-daily-cash-reference --dry-run
php artisan finance:link-daily-cash-reference
```

- **ما دام الجدول فارغاً** يعمل التقرير من ملف الإعدادات بالكود كما كان؛ الشركات التي لا تشغّل الأمر لا يتغير عندها شيء.
- **بعد الربط** يقرأ التقرير البنود من الجدول، ويعرض الكود والاسم الحاليين لكل حساب. البند الذي حُذف حسابه يظهر «غير مربوط».
- **الشجرة رُتّبت قبل الربط؟** شغّل الأمر مع `--as-of="Y-m-d H:i:s"` بوقت قبل الترتيب: يحدد الحساب الذي كان يحمل
  كل كود في ذلك الوقت من `fn_account_restructure_logs` (النقل يتبع الحساب، الدمج يتبع الهدف، الحذف يترك البند غير مربوط).
- **الإدارة من الشاشة:** المالية ← التقارير ← «بنود تقرير صندوق اليومية» (`finance.daily-cash-reference-lines.index`،
  وزر «إدارة البنود» في التقرير): زر «ربط البنود» إن لم تُربط، ثم تغيير حساب البند أو تسميته، والإضافة والحذف والترتيب.
  الصلاحيات `finance_daily_cash_reference_lines_{view,create,edit,delete}` (أو `finance_module`).
- الربط مرة واحدة؛ إعادته تحتاج `--force`. الجدول ضمن `AccountRestructureService::REFERENCES`، فالدمج ينقل البنود والحذف يُمنع.
- **عند ترتيب شجرة شركة:** اربط البنود قبل الترتيب (بلا `--as-of`)، أو بعده بـ `--as-of`. لا تعدّل أكواد ملف الإعدادات.

الاختبارات: `tests/Feature/CashMovementReportTest.php`.

## 16. ترتيب الدليل بخطة محفوظة وطول الكود لكل مستوى

**الإعدادات:** المالية ← الإعدادات ← «إعدادات الدليل المحاسبي» (صلاحية حصرية `finance_chart_settings_manage`، ولا تكفي `finance_module`).
عدد خانات الكود لكل مستوى، وعرض «النوع» حسب التسلسل. الفراغ هو الافتراضي: كود الأب × 100 ونوع الحساب كما كان، فلا يتغير شيء على الشركات الأخرى.
العرض حسب التسلسل يغيّر شاشة إدارة الدليل وتصديرها فقط: المستوى 1 و3 اسمهما، والمستوى 2 و4 وما بعده اسم الأب. نوع الحساب الفعلي لا يتغير.

**تنفيذ خطة** (`packages/finance/database/chart_plans/<plan>.php`):

```bash
php artisan migrate
php artisan finance:chart-restructure --plan=rafidain            # معاينة: تنفّذ كل شيء ثم تلغيه
# نسخة احتياطية mysqldump وإيقاف الإدخال، ثم:
php artisan finance:chart-restructure --plan=rafidain --execute
php artisan optimize:clear && php artisan queue:restart
```

- الخطة تذكر الحسابات بكودها **الأصلي**، والأكواد الجديدة تُحسب وقت التنفيذ. الحساب غير المذكور يبقى مع أبيه، وفروع حساب مدموج غير مذكورة تتبع هدف الدمج مع تنبيه.
- معاملة واحدة تُلغى إن تغيّر مجموع القيود أو رصيد أي حساب، أو تكرر كود، أو تجاوز كود طول مستواه. الخطة نفسها لا تُنفَّذ مرتين (`fn_chart_restructure_runs`) إلا بـ `--force`.
- التقرير في `storage/app/chart-restructure/<وقت>/`: `report.xlsx` فيه خريطة كل الأكواد، و`report.html`.
- **الأكواد القديمة التي يبحث عنها البرنامج** (144، 221، 4216، 52149، 5450، 5911…) تُربط في `fn_account_code_aliases` برقم الحساب. أي بحث جديد بكود ثابت في الكود يجب أن يمر عبر `AccountCodeAliases::current()`، ويُضاف كوده إلى `AccountRestructureService::PROTECTED_CODES`.
- ترقيم الحسابات الجديدة يمر عبر `ChartSettings::childCodeStart()`؛ أي مكان جديد ينشئ حساباً يستخدم `AccountService::getChildCode()`.
- التراجع عن خطة منفذة يكون باستعادة النسخة الاحتياطية.

**خطة تحديث على دليل مرتب** (مثل `rafidain-update-1`): `keep_codes => true` يبقي كود كل حساب لا يتحرك، والمنقول يأخذ أول كود حر
تحت أبيه بطول مستواه المحفوظ، ولا يُعطى كود أخلاه حساب في التنفيذ نفسه لحساب آخر. مفاتيح إضافية:
`requires` (خطط يجب أن تكون نُفذت)، `strict_names` (اختلاف اسم أي حساب عن `expected_names` يوقف الخطة)، `codes` (كود مطلوب)،
`type_labels` (النوع المعروض لحساب بعينه، في العمود `fn_accounts.type_label`، ويتقدم على العرض حسب التسلسل)،
و`settings` (ضبط إعداد حسابات على حساب من الخطة). الأكواد في خطة التحديث هي أكواد الدليل بعد الخطة السابقة.

الاختبارات: `tests/Feature/Finance/ChartRestructureTest.php`.

## قيود المشتريات والوكلاء الخارجيون (إعدادات مستقلة)

قسم جديد في صفحة الإعدادات:

```text
https://www.platinumeastern.com/finance/confirm-settings#recruitment-supplier-cycle-section
```

**قاعدة القسم:** كل قيمة افتراضية تساوي السلوك الحالي للنظام حرفياً. الإعداد الذي لم
يُحفظ قط لا يغيّر أي قيد ولا أي شاشة، ولا يسري أي تغيير على القيود التي أُنشئت قبله.

| المفتاح | الخيارات (الأول هو الافتراضي) | يتحكم في |
|---|---|---|
| `recruitment_supplier_cost_recognition` | `on_arrival` · `on_dispatch` · `on_contract` | لحظة إثبات فاتورة الوكيل الخارجي |
| `recruitment_supplier_cost_source` | `dispatch_manual` · `office_price_list` | مصدر مبلغ التكلفة قبل الإرسالية |
| `recruitment_supplier_usd_rate` | رقم (3.75) | سعر الصرف، بدل تثبيته في الكود |
| `recruitment_cost_center_scope` | `contract` · `nationality_then_contract` | مستوى الدولة في شجرة مراكز التكلفة |
| `recruitment_supplier_line_cost_center` | `off` · `on` | مركز تكلفة العقد على سطر حساب الوكيل |
| `recruitment_supplier_payment_mode` | `general_bond` · `contract_linked` | شاشة دفعات الوكيل المربوطة بالعقد |
| `musaned_collection_mode` | `intermediate_two_step` · `direct_receipt` | مسار قيد التحصيل من منصة مساند |
| `recruitment_contract_finance_screen` | `off` · `on` | شاشة «الملف المالي للعقد» |

### التثبيت على المستند

القيمة تُحفظ على المستند لحظة إنشاء القيد، لا تُقرأ من الإعدادات وقت العرض، على نمط
`invoices.revenue_recognition_mode` القائم:

```text
contracts.supplier_cost_recognition_mode
contracts.supplier_cost_skip_reason
contracts.external_office_cost_constraint_id
contracts.external_office_cost_reversal_constraint_id
contracts.external_office_purchase_id
musaned_transfers.collection_mode
```

حقول الربط على العقد لازمة لوضع `on_contract` لأن القيد يُنشأ قبل وجود فاتورة. وعند
إنشاء الفاتورة تُنسخ الروابط إليها (`mirrorContractSupplierCostLinks`)، فتبقى كل
المسارات القائمة — العكس والاسترداد واسترداد تكلفة الإيواء — تعمل على الفاتورة كما كانت.

### تركيبات مرفوضة عند الحفظ

- `on_contract` مع `dispatch_manual`: لا يوجد مبلغ لحظة إنشاء العقد.
- `recruitment_supplier_usd_rate` بقيمة صفر أو سالبة أو غير رقمية.

### وضع `on_contract` لا يعطّل إنشاء العقد

`RecruitmentTripAccountingService::recordContractCostEntries()` لا يرمي استثناءً بحال.
نقص المكتب أو السعر أو السنة المالية يُسجَّل في `contracts.supplier_cost_skip_reason`،
ويبقى المسار الحالي (الإرسالية أو الوصول) ينشئ القيد لاحقاً. المراقِب
`App\Observers\ContractSupplierCostObserver` يحاول مرة عند الإنشاء ومرة عند أول تغيّر
في `maid_id` (لأن المكتب يأتي من العاملة).

قبل تفعيل `office_price_list` يجب تعبئة `recruitment_offices.cost_of_dollar`؛ وقت كتابة
هذا المستند كانت معبّأة في 6 مكاتب من 99.

### شاشتان جديدتان

```text
/admin/musaned-transfers/invoices-report     تقرير فواتير المبيعات بالصافي بعد عمولة مساند
/admin/recruitment-supplier-payments         دفعات الوكلاء الخارجيين وأرصدتهم
```

التقرير قراءة فقط ويغذّي شاشة تحويلات مساند بالتحديد الجماعي، فلا يوجد مسار ثانٍ يُنشئ
قيد تحصيل. وشاشة الدفعات تعرض الأرصدة دائماً، أما التسجيل فمرهون بإعداد
`recruitment_supplier_payment_mode`.

### مراكز تكلفة عقود الاستقدام

`App\Support\RecruitmentCostCenters` هو المصدر الوحيد لها، بعد أن كان المنطق مكرراً في
`InvoiceController` و`MusanedTransferController` و`RecruitmentTripAccountingService`.
البحث عن مركز قائم يسبق الإنشاء ويغطي الشكلين، فتبديل مستوى الشجرة لا ينشئ مركزاً
ثانياً لعقد له مركز، ولا يُعاد ترتيب أي مركز قائم.

الاختبارات: [tests/Feature/Finance/RecruitmentSupplierCycleTest.php](../tests/Feature/Finance/RecruitmentSupplierCycleTest.php)

### شاشة «الملف المالي للعقد»

```text
/admin/recruitment-contract-finance            قائمة العقود بحالتها المالية الثلاثية
/admin/recruitment-contract-finance/{contract} ملف العقد: الفاتورة · تحصيل مساند · سداد الوكيل
```

مرهونة بإعداد `recruitment_contract_finance_screen` (الافتراضي `off`).

**هي لوحة حالة ومُطلِق إجراءات فوق المستندات القائمة، لا نموذج مستند موازٍ.** لا تحمل أي
منطق محاسبي: نموذج تحصيل مساند يُرسل إلى `musaned-transfers.store` القائم، ونموذج الدفعة
إلى `recruitment-supplier-payments.store`، وزر الفاتورة يفتح `invoices.create` بالعقد
محددًا. فلا يوجد مصدر حقيقة ثانٍ ولا منطق مكرر يتباعد عن الأصل. وكلا المسارين يقبلان
`return_to_contract` فيعودان إلى الملف.

قرار تصميمي مقصود: **لا يوجد زر واحد يرحّل الثلاثة معًا.** الفاتورة تُصدر الآن، وتحصيل
مساند يُسجَّل حين تحوّل مساند فعلًا (وصافيه لا يُعرف قبلها)، وسداد الوكيل بجدوله على دفعات.
زر واحد يرحّل الثلاثة كان سينشئ مستندات تدّعي وقائع لم تحدث — وهو عيب وضع `on_contract`
نفسه منقولًا إلى مكان جديد.

**تحذير مثبَّت في الشاشة:** تحصيل مساند منها يُنشئ تحويلًا لهذا العقد وحده (قرار المستخدم).
مساند تحوّل مبلغًا واحدًا يغطي فواتير كثيرة، فتحتاج المطابقة البنكية تجميع هذه التحويلات
يدويًا مقابل سطر البنك الواحد. البديل — ضم العقد إلى تحويل جماعي — متاح من شاشة
تحويلات مساند التي تقبل `contract_ids[]` في الرابط.

رصيد الوكيل مستخرج في [app/Support/RecruitmentAgentBalance.php](../app/Support/RecruitmentAgentBalance.php)
ليشترك فيه متحكم الدفعات ومتحكم الملف المالي، فلا ينشأ حسابان للرصيد نفسه يتباعدان.

ملاحظة على القوالب: Laravel 8 هنا **لا يدعم `@style`** (يُطبع كنص خام). `@class` و`@selected`
و`@checked` مدعومة. وقوالب هذه الدورة تحرس `$errors` بـ`isset` لتبقى قابلة للرندر خارج دورة
الطلب في الاختبارات.

### الدمج مع «تسلسل الفواتير ومساند والمورد» (`invoice_supplier_workflow`)

الإعداد الثامن من عمل Mahmoud Amer (commit 690f2f53)، افتراضيه `legacy` = الدورة القديمة
حرفيًا. عند `updated`: عمولة مساند تُقيَّد عند إصدار الفاتورة بنسبة الجنسية (افتراضي 2.4% من
الإجمالي شامل الضريبة، مطابقًا لبند «التكاليف البنكية» في فاتورة مساند)، وفاتورة مورد كاملة أو
جزئية من شاشة الفاتورة، وتكلفة مورد لكل سيرة. ما يضبط تعايشه مع الإعدادات السبعة:

| الحالة | السلوك |
| --- | --- |
| بيان قيد تكلفة المورد | `legacy` يبقى «إثبات تكلفة إرسالية المكتب الخارجي»، و`updated` «إثبات تكلفة فاتورة المورد». المطابقة ضد التكرار تقبل الصيغ الثلاث (ومعها «إغلاق طلب» الأقدم). |
| مصدر المبلغ في `on_contract` | في `updated` تسبق تكلفة السيرة سعر المكتب، بنفس ترتيب شاشة الفاتورة. |
| فاتورة مورد بعد قيد `on_contract` | تُرفض إن خالف مبلغها القيد المثبت، بدل أن يبقى القيد على مبلغ والإرسالية على آخر. |
| استرداد قبل أي تحويل مساند | عمولة الإصدار تُعكس بقيد مقابل، رابطه `invoices.musaned_commission_reversal_constraint_id`. بعد التحويل لا تُعكس لأن مساند حسمتها. |
| استرداد قبل الوصول وإيراد `on_arrival` | تُعكس تكلفة المورد إذا أُثبتت مبكرًا بإعداد صريح (فاتورة مورد كاملة/جزئية، أو `on_contract` على العقد، أو `on_dispatch`). الدورة الافتراضية لا تعكسها كما كانت. |

الإثبات الجزئي لا يقيّد الباقي تلقائيًا (بحسب مواصفة التحديث نفسها). بعد نشر هذا التعديل:
`php artisan migrate --path=database/migrations/2026_10_09_140000_add_musaned_commission_reversal_to_invoices.php`.

### قيدان بدل ثلاثة عند الإصدار (`musaned_commission_posting`)

مع التسلسل الجديد تصدر الفاتورة افتراضيًا بثلاثة قيود: الفاتورة، وسند القبض، وقيد مستقل
للتكاليف البنكية لمساند (`separate`). القيمة `with_receipt` تضيف سطري العمولة وضريبتها إلى قيد
سند القبض وتُنقص سطر مساند المدين بالمبلغ نفسه، فتبقى قيمة القيد وتوازنه، ويصير
`invoices.musaned_commission_constraint_id` هو قيد السند.

| ما يبقى كما هو | لماذا |
| --- | --- |
| كشف حساب العميل وسند القبض | قيد الفاتورة وسند القبض لم يُدمجا |
| الإقرار الضريبي | يقرأ مجاميع حسابي الضريبة |
| تحويل مساند | يرى العمولة مسجلة فلا يكررها |

- الوضع يُثبَّت على الفاتورة في `invoices.musaned_commission_posting`، ولا يعمل الدمج قبل وجود
  هذا العمود.
- الاسترداد قبل التحويل يعكس سطور العمولة وحدها من قيد السند.
- `BondService::updateBond` يرفض تعديل سند يحمل قيده العمولة، لأن التعديل يعيد توليد القيد من
  السند وحده. السند يُعدَّل من الفاتورة، ومسار تعديل الفاتورة يعيد بناء القيدين.

بعد النشر: `php artisan migrate --path=database/migrations/2026_10_09_150000_add_musaned_commission_posting_to_invoices.php`.
