# تقرير تنفيذ تصحيح دورة عقود التأجير

تاريخ التقرير: 2026-08-24  
نطاق التنفيذ: دورة إنهاء عقود التأجير، القيود التلقائية، الإشعارات الدائنة/المدينة، وربط الشجرة المالية.

## 1. النتيجة المطلوبة

بعد تطبيق التحديث تصبح القواعد كالتالي:

```text
فرق الذمة = المفوتر - المستحق فعلياً
فرق النقدية = المدفوع - المستحق فعلياً
رصيد العميل بعد الإنهاء = المستحق فعلياً - المدفوع
```

- فرق الذمة الموجب ينشئ إشعاراً دائناً.
- فرق الذمة السالب ينشئ إشعاراً مديناً.
- فرق النقدية الموجب هو مبلغ مسترد للعميل، ولا يُعاد قيده على الذمة.
- فرق النقدية السالب هو مبلغ مطلوب تحصيله من العميل.
- نوع وقيمة الإشعار يُحددان في الخادم ولا يقبلان التعديل اليدوي.
- أُلغي خيار «تصفير المبلغ المتبقي»؛ لأن التصفير السابق كان يغير رقماً دون مستند أو قيد.
- الراتب المعفى يُرحّل إلى حساب مستقل عن إيراد الخدمة الخاضعة.

التحديث موجود ضمن commit:

```text
b94d39eb feat: Add rental adjustments queue route and related functionality
```

## 2. تنبيهات قبل التنفيذ

1. يجب أخذ نسخة احتياطية كاملة من قاعدة البيانات.
2. لا تُنفذ أوامر `--apply` قبل مراجعة نتيجة `--dry-run`.
3. لا تستخدم `--allow-reviewed` للعقود المستوردة أو حالات فترة التجربة دون اعتماد الإدارة والمحاسب.
4. لا تفترض أن أرقام `id` للحسابات متطابقة بين المحلي والسيرفر؛ الربط يتم بالكود ثم يُحفظ `id` الخاص بكل بيئة.
5. فروقات ضريبة التسويات التاريخية تحتاج اعتماد المحاسب قبل قيد إعادة التصنيف.
6. إذا فشل `route:trans:cache` فلا يُعاد الموقع للعمل قبل معالجة الخطأ.

## 3. الدخول إلى المشروع وفحص الحالة

```bash
cd /home/platinum/public_html
git status --short
git remote -v
```

يجب استخدام الريموت الذي يشير إلى:

```text
Clickandmore/ClickErp.git
```

إذا كان اسمه `clickerp`:

```bash
git pull --ff-only clickerp main
```

إذا كان اسمه `origin`:

```bash
git pull --ff-only origin main
```

لا تنفذ السحب إذا أظهر `git status` ملفات معدلة غير معروفة على السيرفر.

## 4. تحديث الحزم والكاش

```bash
git submodule sync --recursive
git submodule update --init --recursive

composer install --no-dev --optimize-autoloader
composer dump-autoload -o

php artisan optimize:clear
php artisan migrate --force
```

لا يحتوي تصحيح دورة التأجير الحالي على migration جديدة خاصة به، لكن تشغيل `migrate --force` يتحقق من بقية تحديثات المشروع.

## 5. اختبار الكود

```bash
php vendor/bin/phpunit \
  --filter='RentalClosureReconcilerTest|RentalContractSettlementTest|RentalContractClosureSummaryTest|RentalContractFinanceServiceTest'
```

النتيجة المتوقعة:

```text
OK (31 tests, 123 assertions)
```

ثم:

```bash
php artisan view:cache
php artisan route:trans:cache
```

إذا ظهر خطأ متعلق بـ:

```text
App\Http\Controllers\Web\HomeController
```

يجب إيقاف النشر ومعالجة مسار `HomeController` قبل إعادة الموقع للعمل.

## 6. أثر اختلاف الشجرة المالية

اختلاف الشجرة يؤثر مباشرة في القيود التلقائية إذا لم تُربط الحسابات صراحةً. الحسابات المطلوبة:

| الإعداد | طبيعة الحساب المطلوبة | الاستخدام |
|---|---|---|
| `rental_contract_revenue_account` | دائن | إيراد خدمة التأجير الخاضعة |
| `rental_contract_exempt_salary_revenue_account` | دائن | راتب العاملة المعفى |
| `rental_contract_clients_parent_account` | مدين ومن نوع Clients | إنشاء حسابات العملاء تحته |
| `rental_contract_cash_account` | مدين | سندات القبض النقدية |
| `rental_contract_bank_account` | مدين | التحويلات البنكية ومدى |
| `rental_contract_electronic_account` | مدين | بوابات الدفع والمحافظ ونقاط البيع |
| `revenue_tax_account` | دائن | ضريبة القيمة المضافة للمخرجات |

في قاعدة الاختبار الحالية:

```text
4214 = حساب تأجير خدم، دائن
4216 = إيراد مرتب العامل المعفى ضريبياً، دائن
1281 = حساب ضريبة القيمة المضافة، دائن
123  = حساب العملاء الرئيسي، مدين ومن نوع Clients
```

هذه الأكواد يجب التحقق منها على السيرفر، ولا يُفترض أن أرقام `id` متطابقة.

## 7. فحص إعدادات حسابات التأجير — قراءة فقط

```bash
php artisan tinker --execute='
$keys = App\Services\ingaz\RentalContractFinanceService::confirmSettingTitles();
$settings = Nami\Finance\Models\ConfirmSetting::whereIn("title", $keys)->pluck("value", "title");
foreach ($keys as $key) {
    $id = $settings[$key] ?? null;
    $account = $id ? Nami\Finance\Models\Account::find($id) : null;
    dump([
        "setting" => $key,
        "account_id" => $id,
        "code" => $account?->code,
        "name" => $account?->name,
        "status" => $account?->status,
    ]);
}'
```

## 8. ربط حسابي الإيراد الخاضع والمعفى — يكتب في الإعدادات

لا ينشئ الأمر حسابات جديدة؛ يبحث عن الحسابين بالكود ويتوقف إذا لم يجدهما كحسابين نهائيين دائنين.

```bash
php artisan tinker --execute='
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;

$map = [
    "rental_contract_revenue_account" => 4214,
    "rental_contract_exempt_salary_revenue_account" => 4216,
];

foreach ($map as $setting => $code) {
    $account = Account::where("code", $code)
        ->where("status", "creditor")
        ->whereDoesntHave("children")
        ->first();

    if (! $account) {
        throw new RuntimeException("الحساب الدائن النهائي {$code} غير موجود");
    }

    ConfirmSetting::updateOrCreate(
        ["title" => $setting],
        ["value" => $account->id, "created_by_id" => 1]
    );

    dump([$setting => [
        "id" => $account->id,
        "code" => $account->code,
        "name" => $account->name,
    ]]);
}'
```

## 9. فحص حساب ضريبة المخرجات — قراءة فقط

```bash
php artisan tinker --execute='
$setting = Nami\Finance\Models\ConfirmSetting::where("title", "revenue_tax_account")
    ->latest("updated_at")
    ->first();
$account = $setting?->value ? Nami\Finance\Models\Account::find($setting->value) : null;
dump([
    "setting" => $setting?->title,
    "account_id" => $account?->id,
    "code" => $account?->code,
    "name" => $account?->name,
    "status" => $account?->status,
]);
'
```

يجب أن يعرض حساباً نهائياً طبيعته `creditor`. إذا لم يظهر حساب صحيح، يجب ربط إعداد ضريبة الإيرادات من إعدادات المالية قبل إنشاء أي قيد تأجير.

## 10. عرض مرشحي العملاء والبنوك والصناديق — قراءة فقط

```bash
php artisan tinker --execute='
$rows = Nami\Finance\Models\Account::whereIn("type", [6, 15, 17, 33])
    ->where("status", "debtor")
    ->orderBy("type")
    ->orderBy("code")
    ->get(["id", "code", "name", "type", "status", "parent_id"]);
dump($rows->toArray());
'
```

دلالات الأنواع:

```text
6  = Clients
15 = Bank
17 = Box
33 = ElectronicWallet
```

## 11. ربط العملاء وحسابات القبض — يكتب في الإعدادات

يجب استبدال القيم النصية التالية بالأكواد التي اعتمدها المحاسب على السيرفر:

```bash
php artisan tinker --execute='
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;

$codes = [
    "rental_contract_clients_parent_account" => "كود_حساب_العملاء",
    "rental_contract_cash_account" => "كود_الصندوق",
    "rental_contract_bank_account" => "كود_البنك",
    "rental_contract_electronic_account" => "كود_نقاط_البيع_او_المحفظة",
];

foreach ($codes as $setting => $code) {
    $account = Account::where("code", $code)->first();

    if (! $account) {
        throw new RuntimeException("الحساب {$code} غير موجود");
    }

    ConfirmSetting::updateOrCreate(
        ["title" => $setting],
        ["value" => $account->id, "created_by_id" => 1]
    );

    dump([$setting => $account->only(["id", "code", "name", "status"])]);
}'
```

لا يُنفذ هذا القالب قبل استبدال الأكواد واعتمادها. الحساب الرئيسي للعملاء يجب أن يكون من نوع `Clients` ومديناً، وحسابات القبض يجب أن تكون حسابات نهائية مدينة.

## 12. تقرير العقود القديمة — قراءة فقط

```bash
php artisan rental-contracts:reconcile-closures \
  --only-issues \
  --vat \
  --export=storage/app/rental-closures-before.csv
```

## 13. معاينة العقود القابلة للتصحيح — قراءة فقط

```bash
php artisan rental-contracts:reconcile-closures --contract=7756 --dry-run
php artisan rental-contracts:reconcile-closures --contract=7767 --dry-run
php artisan rental-contracts:reconcile-closures --contract=7769 --dry-run
```

إذا أصبحت هذه العقود مطابقة على البيئة المستهدفة، فلن ينشئ الأمر أي تصحيح ولا يجب إجباره.

## 14. تطبيق تصحيح العقود القديمة — يكتب قيوداً جديدة

ينفذ كل عقد منفرداً ويطلب تأكيد وجود النسخة الاحتياطية:

```bash
php artisan rental-contracts:reconcile-closures \
  --contract=7756 \
  --apply \
  --reason="إصدار إشعار الإنهاء المفقود وفق فرق الذمة الصحيح"
```

```bash
php artisan rental-contracts:reconcile-closures \
  --contract=7767 \
  --apply \
  --reason="تصحيح الإشعار الذي استخدم فرق النقدية بدلاً من فرق الذمة"
```

```bash
php artisan rental-contracts:reconcile-closures \
  --contract=7769 \
  --apply \
  --reason="إصدار إشعار الإنهاء المفقود وفق فرق الذمة الصحيح"
```

لا تستخدم:

```bash
php artisan rental-contracts:reconcile-closures --allow-reviewed
```

إلا بعد اعتماد المحاسب والإدارة للعقد المحدد.

## 15. العقد 7768

لا يُشغل عليه أمر تصحيح العقود القديمة ما دام نشطاً. بعد نشر الكود، تُفتح شاشة الإنهاء ويُحدد تاريخ 2026-08-24.

الصورة الصحيحة:

```text
قيمة العقد                         1,700.00
المدفوع                            1,700.00
المستحق عن 27 يوماً                1,530.00
الإشعار الدائن السابق              1,700.00
صافي الإشعار الدائن المطلوب          170.00
المستند التصحيحي عند الإنهاء       مدين 1,530.00
الاسترداد النقدي للعميل              170.00
الرصيد النهائي لصالح العميل          170.00
```

يجب ألا يُحذف الإشعار السابق؛ لأنه مرتبط بقيد ومرتب للهيئة. يصدر النظام إشعاراً مديناً بالمبلغ اللازم للوصول إلى الصافي الصحيح.

## 16. التحقق بعد التصحيح

```bash
php artisan rental-contracts:reconcile-closures \
  --only-issues \
  --vat \
  --export=storage/app/rental-closures-after.csv
```

```bash
php artisan rental-contracts:audit-finance --summary
```

ثم افحص عقداً محدداً:

```bash
php artisan rental-contracts:reconcile-closures --contract=7770 --vat
```

## 17. مستندات ZATCA

- مستندات الإنهاء الجديدة تُجهز من تركيبة القيد المرحل.
- سطر الفاتورة القديمة يُطابق مع القيد قبل تجهيز حمولة الهيئة.
- إشعارات `closure_correction` تظهر في شاشة العقد وقائمة الفواتير.
- لا يُرسل أي تصحيح تاريخي للهيئة قبل مراجعته من المحاسب.
- بعد اعتماد الإشعار، يُدرج من زر «إدراج الإشعار في مستندات الزكاة» في قائمة الفواتير.

## 18. فروقات الضريبة التاريخية

أمر `--vat` يعرض فروقات تقسيم الضريبة القديمة، لكنه لا يرحّل قيد إعادة التصنيف تلقائياً. لا تُسوّى هذه الفروقات عبر حساب العميل ولا عبر تعديل الفواتير أو القيود الأصلية.

المعالجة الصحيحة بعد اعتماد المحاسب:

```text
قيد إعادة تصنيف مستقل بين حساب إيراد التأجير وحساب ضريبة المخرجات
دون أي حركة على حسابات العملاء
```

يجب حفظ تقرير `rental-closures-before.csv` كمرفق للقيد واعتماد إجمالي الضريبة والفترة المحاسبية قبل الترحيل.

## 19. فحص نهائي قبل إعادة الموقع للعمل

```bash
php artisan optimize:clear
php artisan config:clear
php artisan config:cache
php artisan route:trans:cache
php artisan view:cache
```

ثم:

```bash
php artisan rental-contracts:reconcile-closures --only-issues
php artisan rental-contracts:audit-finance --summary
```

لا تُعد الخدمة للعمل إذا فشل الكاش، أو لم تكن حسابات الإيراد والراتب المعفى والضريبة والعملاء وحسابات القبض مرتبطة بالحسابات المعتمدة.

## 20. قرار تعديل قيمة الإشعار بصلاحية

يمكن السماح بفارق معتمد عن الحسبة الآلية، لكن لا يُفتح حقل قيمة الإشعار للتعديل الحر. التنفيذ المقترح يحتاج تطويراً مستقلاً، ويكون كالتالي:

1. تعرض الشاشة القيمة المحسوبة آلياً ولا تتغير.
2. مستخدم لديه صلاحية مستقلة، مثل `rental_closure_adjustment_override`، يستطيع إدخال «فرق تسوية خارجي» فقط قبل الإصدار النهائي.
3. يعرض النظام القيمة المحسوبة، فرق التسوية، والقيمة النهائية كلٌّ في حقل مستقل.
4. السبب، المرجع الخارجي، والمرفق مستندات إلزامية، مع تحديد المعالجة الضريبية للفرق.
5. يعتمد مستخدم ثانٍ لديه صلاحية `rental_closure_adjustment_approve` التسوية، ولا يجوز لمن أدخلها أن يعتمدها.
6. قبل الاعتماد يعرض النظام أثر التسوية على رصيد العميل والقيد المحاسبي وحمولة ZATCA.
7. بعد إصدار الرقم والـ UUID أو الترحيل أو الإرسال إلى الهيئة يُقفل المستند نهائياً؛ وأي تصحيح لاحق يتم بإشعار دائن أو مدين جديد مرتبط بالمستند الأصلي.
8. فرق التحويل البنكي أو مصروف التحويل الذي لا يغير المقابل الضريبي يعالج بسند مالي مستقل، وليس بتغيير قيمة الإشعار الضريبي.

## 21. مهلة مشاركة المستندات مع ZATCA

هذه القواعد تنطبق عند دخول المنشأة في المرحلة الثانية «مرحلة الربط والتكامل» حسب إشعار الهيئة:

| نوع المستند | آلية الهيئة | وقت الإرسال/المشاركة |
|---|---|---|
| الفاتورة الضريبية القياسية، غالباً B2B | Clearance | ترسل إلى الهيئة وتحصل على الموافقة قبل مشاركتها مع العميل |
| الإشعار الدائن/المدين المرتبط بفاتورة قياسية | Clearance | نفس حكم الفاتورة الأصلية: الموافقة قبل مشاركته |
| الفاتورة الضريبية المبسطة، غالباً B2C | Reporting | ترسل إلى الهيئة خلال 24 ساعة من وقت الإصدار |
| الإشعار الدائن/المدين المرتبط بفاتورة مبسطة | Reporting | نفس نوع وآلية المستند الأصلي، وخلال 24 ساعة من الإصدار |

لا تبدأ مهلة الـ 24 ساعة من وقت إدخال المسودة؛ تبدأ من إنشاء/إصدار المستند الإلكتروني. لذلك يجب إبقاء التسوية «مسودة» حتى اكتمال الموافقات، ثم إصدارها وإرسالها فوراً. ولا يكفي وجود زر إدراج يدوي وحده لضمان الالتزام بالمهلة.

## 22. ضوابط منع مخالفات الفاتورة

- يحدد النظام نوع الفاتورة من صفة العميل وبياناته الضريبية، ولا يترك النوع لاختيار المستخدم دون تحقق.
- تمنع مشاركة الفاتورة القياسية مع العميل أو طباعتها كنسخة نهائية قبل نجاح `Clearance`.
- ترسل الفاتورة المبسطة والإشعار المبسط تلقائياً فور الإصدار، مع إعادة المحاولة والتنبيه قبل انقضاء 24 ساعة.
- يحتفظ النظام بمحاولات الإرسال وردود الهيئة كدليل عند تعطل الاتصال، مع إشعار المسؤول فوراً عند استمرار الفشل.
- يمنع تعديل المستند أو حذفه أو إعادة استخدام رقمه بعد الإصدار؛ التصحيح يكون بمستند لاحق يشير إلى الأصل وسبب التصحيح.
- تستخدم سلسلة أرقام واحدة منضبطة لكل نوع/وحدة إصدار، وUUID فريداً، وآلية تمنع تكرار الإرسال أو إنشاء نسختين للعملية نفسها.
- يتحقق النظام من الرقم الضريبي للمشتري في الفاتورة القياسية، ومن QR ومتطلبات XML والختم للفواتير المبسطة بحسب المرحلة الثانية.
- تحفظ نسخة XML النهائية، والنسخة المقروءة، والـ QR، والهاش، ووقت الإصدار، ورد الهيئة، وسجل التدقيق دون قابلية للعبث.
- يضبط توقيت الخادم بدقة، ويخزن وقت الإصدار الفعلي، وتعمل مهمة مجدولة لمراقبة المستندات `pending/failed` والتنبيه والتصعيد.
- تمرر الحمولة على أدوات التحقق الرسمية قبل الإرسال؛ نجاح التحقق الفني لا يغني عن صحة المعالجة الضريبية والمحاسبية.

## 23. المراجع الرسمية

- [الدليل التفصيلي للفوترة الإلكترونية](https://zatca.gov.sa/en/E-Invoicing/Introduction/Guidelines/Documents/E-Invoicing_Detailed__Guideline.pdf)
- [متطلبات ومواصفات الفاتورة الإلكترونية للمطورين](https://zatca.gov.sa/en/E-Invoicing/SystemsDevelopers/Pages/E-Invoice-specifications.aspx)
- [أداة التحقق SDK التابعة للهيئة](https://zatca.gov.sa/en/E-Invoicing/SystemsDevelopers/ComplianceEnablementToolbox/Pages/DownloadSDK.aspx)
- [طريقة الاستعداد ومحظورات حلول الفوترة الإلكترونية](https://zatca.gov.sa/en/E-Invoicing/PreparingYourBusiness/Phase1/Pages/How-to-prepare.aspx)
- [اللائحة التنفيذية لضريبة القيمة المضافة](https://zatca.gov.sa/en/RulesRegulations/VAT/Documents/Value-Added-Tax-Approved-Implementing-Regulations-Bilingual.pdf)
- [قرار تطبيق ضوابط ومتطلبات الفوترة الإلكترونية](https://zatca.gov.sa/en/E-Invoicing/Introduction/LawsAndRegulations/Documents/20230519_E-Invoicing%20Implementation%20Resolution%20English.pdf)
