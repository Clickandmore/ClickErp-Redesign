# أوامر اختبار ورفع إصلاح الضريبة

تاريخ التقرير: 2026-09-06. تُنفذ الخطوات لكل شركة وقاعدة بيانات بصورة مستقلة.

**لا تنفّذ هذا الملف دفعة واحدة.** نفّذ كل مرحلة وتحقق من نتيجتها قبل الانتقال إلى التالية. لا تنفّذ ترحيلًا عامًا؛ توجد تغييرات مشتريات وموردين وضمان في مساحة العمل لم تُعتمد ضمن إصلاح الضريبة.

## 1. فحص الكود قبل الرفع

من مجلد المشروع:

```bash
cd /Applications/MAMP/htdocs/ClickERP
git status --short
git diff --check
git diff --stat
```

محليًا استُخدم PHP 8.3.30. على الخادم اضبط المسار إلى إصدار PHP المتوافق مع التطبيق:

```bash
TAX_PHP=/Applications/MAMP/bin/php/php8.3.30/bin/php
"$TAX_PHP" -v
```

شغّل الاختبارات الأربعة، ويتوقف الأمر عند أول إخفاق:

```bash
for tax_test in TaxAccountResolverTest InvoiceCalculationServiceTest RentalContractFinanceServiceTest RentalClosureReconcilerTest; do
    "$TAX_PHP" vendor/bin/phpunit "tests/Unit/${tax_test}.php" || break
done
```

النتيجة المعتمدة الحالية: 22 + 3 + 14 + 6 = **45 اختبارًا و145 تحققًا**. يجب رؤية نجاح المجموعات الأربع، وليس الاكتفاء بنجاح آخر أمر. لا حاجة إلى `composer update` لهذه التعديلات.

## 2. نسخة احتياطية لكل قاعدة

مثال لأدوات MAMP. على الخادم عدّل مسارات الأدوات والمضيف والمنفذ واسم المستخدم وقاعدة الشركة. اسم القاعدة في المثال **عنصر يجب استبداله**؛ لا تنسخ معرّفات حسابات شركة إلى أخرى.

```bash
TAX_MYSQL=/Applications/MAMP/Library/bin/mysql80/bin/mysql
TAX_DUMP=/Applications/MAMP/Library/bin/mysql80/bin/mysqldump
TAX_DB_HOST=127.0.0.1
TAX_DB_PORT=8889
TAX_DB_USER=YOUR_DB_USER
TAX_SOURCE_DB=YOUR_COMPANY_DATABASE
TAX_BACKUP_DIR="$HOME/tax-backups"
mkdir -p "$TAX_BACKUP_DIR"
TAX_BACKUP_FILE="$TAX_BACKUP_DIR/${TAX_SOURCE_DB}_$(date +%Y%m%d_%H%M%S).sql"

"$TAX_DUMP" -h "$TAX_DB_HOST" -P "$TAX_DB_PORT" -u "$TAX_DB_USER" -p \
    --single-transaction --routines --triggers --events --no-tablespaces \
    "$TAX_SOURCE_DB" > "$TAX_BACKUP_FILE"
```

تأكد من نجاح الخروج وعدم ظهور خطأ. الملف الجزئي أو الفارغ ليس نسخة احتياطية. احتفظ به خارج مجلد الموقع. استعادته إلى قاعدة الاختبار في الخطوة التالية هي التحقق العملي منه. `--single-transaction` يحافظ على اتساق جداول InnoDB؛ إذا كانت الشركة تستخدم جداول غير معاملية، خذ النسخة أثناء توقف الكتابة.

## 3. نسخة اختبار مستقلة

أنشئ قاعدة جديدة فارغة؛ استبدل `tax_predeploy_check` باسم جديد إن كانت موجودة. لا تستخدم اسم قاعدة الشركة هنا:

```bash
"$TAX_MYSQL" -h "$TAX_DB_HOST" -P "$TAX_DB_PORT" -u "$TAX_DB_USER" -p \
    -e 'CREATE DATABASE tax_predeploy_check CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'
```

**فقط بعد نجاح الإنشاء** استعد النسخة:

```bash
"$TAX_MYSQL" -h "$TAX_DB_HOST" -P "$TAX_DB_PORT" -u "$TAX_DB_USER" -p \
    tax_predeploy_check < "$TAX_BACKUP_FILE"
```

استخدم نسخة تطبيق منفصلة للاختبار، واضبط اتصالها في `.env` على قاعدة الاختبار. لا تغيّر `.env` الخاص بموقع الشركة العامل. عطّل الإرسال الفعلي والمهام المجدولة والتكاملات الخارجية في نسخة الاختبار.

من مجلد **تطبيق الاختبار**، بعد ضبط الاتصال:

```bash
"$TAX_PHP" artisan config:clear
"$TAX_PHP" artisan tinker --execute='dump(DB::connection()->getDatabaseName());'
```

يجب أن يطبع اسم قاعدة الاختبار قبل أي تنفيذ للترحيل.

## 4. تدقيق واختبار الترحيل

هذه الأوامر تتطلب وجود كود الضريبة الجديد، بما فيه ملف أمر التدقيق والمحلل والترحيل، في تطبيق الاختبار:

```bash
"$TAX_PHP" artisan finance:tax-accounts-audit
```

الأمر للقراءة فقط، ولا يوجد خيار `--pin`. قبل الترحيل قد يعرض نقصًا في التعريف الافتراضي رغم وجود حساب قديم صالح. راجع حساب الإيرادات وحساب المصروفات والنسبة والقيود الفعلية لكل شركة. وجود تعارض يحتاج قرارًا واضحًا؛ الترحيل يحافظ على القديم الصالح ولا يثبت أنه الصحيح محاسبيًا.

بعد مراجعة الإعدادات، شغّل **الترحيل المحدد وحده**:

```bash
"$TAX_PHP" artisan migrate \
    --path=packages/finance/database/migrations/2026_09_06_130000_consolidate_tax_account_settings.php

"$TAX_PHP" artisan finance:tax-accounts-audit
```

إذا توقف الترحيل بسبب غموض النسبة أو حساب غير صالح، عالج الإعدادات على نسخة الاختبار وأعد المحاولة. إذا انتهى الترحيل لكن التدقيق ما زال يعرض إعدادًا ناقصًا، لا تعتمد النسخة قبل ضبطه. الشركة غير المضبوطة لا تُضبط بالتخمين تلقائيًا.

للتأكد من تسجيل الترحيل:

```bash
"$TAX_PHP" artisan tinker --execute='dump(DB::table("migrations")->where("migration", "2026_09_06_130000_consolidate_tax_account_settings")->exists());'
```

ثم اختبر من الواجهة على نسخة الاختبار:

- حفظ مركز تكلفة أو إعداد آخر دون تغيير اختيار الضريبة؛ يجب أن تبقى الضريبة كما هي.
- سند قبض وصرف وفاتورة استقدام وتأجير وتحويل مساند؛ راجع حساب الضريبة والنسبة والمبلغ واتزان القيد.
- مقارنة الإقرار وتحليل المبيعات والمشتريات وتسوية تأجير قديمة قبل التغيير وبعده.
- رفض اختيار تعريف صفري كافتراضي، ورفض حساب رئيسي أو حساب بطبيعة مخالفة.

لا تنفّذ عمليات الاختبار هذه على قاعدة العمل. الاختبارات الآلية ناجحة واختبار MySQL المعزول نجح على البيانات المحلية، لكن دورة التشغيل أعلاه لازمة لكل شركة مختلفة.

## 5. رفع الكود بعد نجاح الاختبار

جهّز الإصدار من ملفات الضريبة فقط، مع الاحتفاظ بالإصدار السابق ونسخة احتياطية حديثة. لا تستخدم `git add .` أو ترفع جميع ملفات مساحة العمل دون مراجعة.

ملفات إصلاح الضريبة الأساسية:

```text
app/Console/Commands/AuditTaxAccounts.php
app/Console/Commands/ReconcileRentalClosures.php
app/Http/Controllers/ingaz/InvoiceController.php
app/Http/Controllers/ingaz/MusanedTransferController.php
app/Services/ingaz/RentalClosureCorrectionService.php
app/Services/ingaz/RentalContractFinanceService.php
packages/finance/database/migrations/2026_09_06_130000_consolidate_tax_account_settings.php
packages/finance/src/Services/TaxAccountResolver.php
packages/finance/src/Services/ConfirmSettingService.php
packages/finance/src/Services/TaxSettingService.php
packages/finance/src/Http/Requests/TaxSeetingRequest.php
packages/finance/src/Models/TaxSetting.php
packages/finance/src/Http/Controllers/SalesAnalysisController.php
packages/finance/src/Http/Controllers/PurchasesAnalysisController.php
packages/finance/src/Http/Controllers/TaxDeclarationController.php
packages/finance/resources/views/finance/confirm_settings/index.blade.php
packages/finance/resources/views/finance/tax_setting/create.blade.php
packages/finance/resources/lang/ar/finance.php
packages/finance/resources/lang/en/finance.php
```

احتفظ بالاختبارات والتوثيق ضمن مستودع الإصدار كذلك. إذا كان ملف مشترك يحتوي تعديلات أخرى، راجع الفروق واختَر تغييرات الضريبة فقط.

من تطبيق الشركة على الخادم، أثناء نافذة الصيانة:

```bash
"$TAX_PHP" artisan down
```

أوقف أيضًا عمّال الخلفية والمهام التي تكتب بيانات وفق طريقة تشغيل الخادم؛ وضع الصيانة وحده لا يوقف كل كاتب خارجي. بعد ذلك خذ النسخة الاحتياطية النهائية وارفع الإصدار الجديد بالطريقة المعتادة.

بعد رفع الملفات وقبل إعادة استقبال العمليات:

```bash
"$TAX_PHP" artisan config:clear
"$TAX_PHP" artisan tinker --execute='dump(DB::connection()->getDatabaseName());'
```

تأكد أن الاسم هو قاعدة الشركة المستهدفة، ثم:

```bash
"$TAX_PHP" artisan migrate --force \
    --path=packages/finance/database/migrations/2026_09_06_130000_consolidate_tax_account_settings.php

"$TAX_PHP" artisan finance:tax-accounts-audit
"$TAX_PHP" artisan optimize:clear
```

لا تنتقل للخطوة التالية إذا أخفق الترحيل أو التدقيق. بعد نجاحهما والتحقق من ثبات الحسابات والنسب، أعد تشغيل العمّال والمهام وفق إعداد الخادم، ثم:

```bash
"$TAX_PHP" artisan up
```

## 6. عند حدوث مشكلة

أبقِ الموقع في الصيانة. لا تستخدم `migrate:rollback` باعتباره استرجاعًا لإعدادات الضريبة: `down()` لهذا الترحيل لا يعيد القيم السابقة.

الرجوع يكون بإعادة الإصدار السابق واستعادة النسخة الاحتياطية المتوافقة بواسطة المسؤول عن النشر، قبل استئناف الكتابة. إذا استؤنفت العمليات بالفعل، لا تستعد نسخة كاملة فوقها دون معالجة العمليات التي أُضيفت منذ النسخة الاحتياطية.
