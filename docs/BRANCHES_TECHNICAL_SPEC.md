# مواصفة تقنية — استكمال عزل الفروع

**الحالة:** جاهزة للمراجعة مع قسم التطوير · **التاريخ:** 5 سبتمبر 2026
**المرجع الوظيفي:** [BRANCHES_ROLLOUT_2026_09.md](BRANCHES_ROLLOUT_2026_09.md)

كل رقم في هذه الوثيقة مُولَّد من `php artisan branches:audit` على `rafdain_test`، وليس تقديراً.
لإعادة توليد أي جدول هنا: `php artisan branches:audit --section=<القسم> --details --limit=0`.

---

> **حالة التنفيذ (5 سبتمبر 2026):** المهام 1 و4 و(جزء من) 7 **مُنفَّذة**.
> راجع §13 لحالة كل بند. المتبقي: إسناد البيانات (§5 — يحتاج قاعدة الإنتاج)،
> ربط القبض بالصندوق (§6 — يحتاج قراراً محاسبياً)، و78 جدولاً فارغاً (§8).

## 1. الملخّص التنفيذي

عزل الفروع مطبَّق على طبقة Eloquent (43 موديلاً)، لكن **113 استعلاماً خاماً يتجاوزه**،
منها **80 في حزمة المالية**. النتيجة العملية: شاشات القيود والسندات معزولة، بينما ميزان
المراجعة وكشف الحساب والموازنة ما زالت تجمع كل الفروع.

| البند | العدد | الحالة |
|---|---|---|
| جداول تحمل `branch_id` | 71 من 366 | — |
| موديلات معزولة تلقائياً | 43 | ✅ |
| موديلات بلا عزل بقرار موثّق | 8 | ✅ مقصود |
| **استعلامات خام تتجاوز العزل** | **113** | ❌ **المهمة الأساسية** |
| استعلامات خام تحتاج مراجعة يدوية | 49 | ⚠️ |
| شاشات موظفين بلا فلترة | 6 | ❌ |
| موديلات بلا ختم فرع عند الكتابة | 23 | ❌ |
| جداول تشغيلية بلا `branch_id` | 78 | ⚠️ |
| صفوف `branch_id = NULL` | 213,908 | ❌ يسبق النشر |

---

## 2. البنية المطبَّقة — العقد الذي يُبنى عليه

### 2.1 الملفات

| الملف | المسؤولية |
|---|---|
| [app/Support/BranchQueryContext.php](../app/Support/BranchQueryContext.php) | يقرر **متى** يعمل العزل، ويحلّ فرع المستخدم الحالي |
| [app/Models/Scopes/BranchScope.php](../app/Models/Scopes/BranchScope.php) | يطبّق شرط الفرع على استعلام Eloquent |
| [app/Models/Concerns/BranchScoped.php](../app/Models/Concerns/BranchScoped.php) | التريت والمخارج الصريحة |
| [app/Models/Concerns/AssignsBranch.php](../app/Models/Concerns/AssignsBranch.php) | ختم الفرع عند الكتابة |
| [app/Services/BranchAccessService.php](../app/Services/BranchAccessService.php) | الفلترة اليدوية وفحص الوصول (singleton) |

### 2.2 قاعدة التفعيل

```
العزل يعمل  ⟺  موديول branches مفعّل
              ∧ المستخدم مسجّل دخول بحارس admin
              ∧ admin.branch_id IS NOT NULL
              ∧ ¬ يملك branches_view_all
              ∧ ¬ داخل withoutBranchScope()
              ∧ ¬ أمر artisan (خارج PHPUnit)
```

أي شرط يسقط ⇒ لا فلترة إطلاقاً. الشركات التي لا تستعمل الفروع لا تتأثر.

### 2.3 دلالة `branch_id IS NULL`

| نوع الموديل | من يرى السجل بلا فرع |
|---|---|
| مستند (عقد، فاتورة، قيد، سند) | الفرع الرئيسي وحده |
| بيانات مشتركة (`branchScopeSharesUnassigned() === true`) | كل الفروع |

الموديل الوحيد المشترك اليوم: `Maid` — العاملة بلا فرع مخزون مشترك.

### 2.4 المخارج الصريحة

```php
Contract::withoutBranchScope()->sum('value');        // تقرير تجميعي
Contract::forBranch($branchId)->get();               // فرع محدد صراحةً
Contract::acrossBranches()->max('code');             // ترقيم على مستوى الشركة
BranchQueryContext::withoutBranchScope(fn () => ...); // كتلة كاملة
```

---

## 3. الموديلات المستثناة بقرار موثّق

مُعرَّفة في `BranchesAuditCommand::DELIBERATELY_UNSCOPED` فلا تُحسب ثغرات.

| الموديل | السبب |
|---|---|
| `auth\Admin`, `Nami\Admin` | حارس `admin` يقرأ من `admins`؛ عزله يكسر تسجيل الدخول والجلسة |
| `ingaz\User` | منطق فروع خاص مكتمل في `UserController` + `clients_view_all_branches` |
| `Finance\Account` | `code` فريد على مستوى الشجرة، و**10 مواضع** تولّده من `max('code')` |
| `Finance\CostCenter`, `Client`, `Supplier`, `Box` | بيانات أساسية مشتركة |

> **حادثة مرجعية:** عزل `Account` تسبّب فعلياً في
> `Duplicate entry '12219' for key 'fn_accounts_code_unique'` أثناء تجهيز فرع الرياض.
> عزل البيانات الأساسية على مستوى الاستعلام **ممنوع**؛ الفلترة المطلوبة تكون على مستوى الشاشة.

---

## 4. المهمة 1 — عزل التقارير المالية (أولوية قصوى)

### 4.1 المشكلة

الـ global scope يعمل على Eloquent فقط. هذه الخدمات تقرأ بـ `DB::table(...)` و SQL نصي:

| الملف | مواضع | ما يعرضه للمستخدم |
|---|---|---|
| `packages/finance/src/Services/ConstraintDetailsService.php` | 10 | حركة الحسابات |
| `packages/finance/src/Services/TrailBalanceService.php` | 9 | **ميزان المراجعة** |
| `packages/finance/src/Services/AuditBudgetService.php` | 8 | الموازنة التقديرية |
| `packages/finance/src/Services/BankAccountStatementService.php` | 7 | كشف حساب البنك |
| `packages/finance/src/Services/AccountCostCenterService.php` | 5 | حركة مركز التكلفة |
| `packages/finance/src/Services/ConstraintService.php` | 5 | أرصدة القيود |
| `packages/finance/src/Http/Controllers/TaxDeclarationController.php` | 4 | الإقرار الضريبي |
| `packages/finance/src/Services/AccountStatementService.php` | 3 | كشف الحساب |
| `app/Services/ingaz/RentalContractLedgerService.php` | 2 | دفتر أستاذ عقد التأجير |
| `app/Http/Controllers/ingaz/RentContractController.php` | 2 | رصيد العميل |
| `app/Http/Controllers/ingaz/InvoiceController.php` | 1 | المُرحَّل من الفاتورة |

**المجموع: 56 موضعاً.** القائمة الكاملة بالأسطر:

```bash
php artisan branches:audit --section=raw --details --limit=0
```

> `AccountService.php` (12 موضعاً) **مستثنى** — توليد أكواد، يجب أن يبقى على مستوى الشركة.

### 4.2 الحل المقترح — `BranchReportFilter`

كل هذه الاستعلامات تربط `fn_constraint_details` بـ `fn_constraints`، فيكفي شرط واحد على أيّهما.
تُقترح فئة واحدة تخدم الشكلين (Query Builder و SQL نصي):

```php
<?php

namespace App\Support;

/**
 * شرط الفرع للاستعلامات التي لا تمرّ بـ Eloquent.
 *
 * الـ global scope لا يرى DB::table ولا SQL النصي، وهما ما تُبنى عليه
 * تقارير المالية. هذه الفئة تعطي نفس دلالة BranchScope بالضبط:
 * فرع المستخدم، مع السجلات بلا فرع لموظف الفرع الرئيسي.
 */
class BranchReportFilter
{
    /** Query Builder: BranchReportFilter::apply($query, 'fn_constraint_details'); */
    public static function apply($query, string $table, string $column = 'branch_id')
    {
        $branchId = BranchQueryContext::activeBranchId();

        if ($branchId === null) {
            return $query;
        }

        $qualified = $table . '.' . $column;

        if (BranchQueryContext::activeBranchIsMain()) {
            return $query->where(function ($inner) use ($qualified, $branchId) {
                $inner->where($qualified, $branchId)->orWhereNull($qualified);
            });
        }

        return $query->where($qualified, $branchId);
    }

    /**
     * SQL نصي: [$sql, $bindings] للحقن في WHERE داخل استعلامات CTE.
     * يُرجع شرطاً محايداً عند غياب العزل حتى لا يحتاج المستدعي تفريعاً.
     */
    public static function sqlCondition(string $alias, string $column = 'branch_id'): array
    {
        $branchId = BranchQueryContext::activeBranchId();

        if ($branchId === null) {
            return ['1 = 1', []];
        }

        $qualified = $alias . '.' . $column;

        return BranchQueryContext::activeBranchIsMain()
            ? ["({$qualified} = ? OR {$qualified} IS NULL)", [$branchId]]
            : ["{$qualified} = ?", [$branchId]];
    }
}
```

### 4.3 نمط التطبيق

**أ — Query Builder** (`AccountStatementService`، `ConstraintService`، `BankAccountStatementService`، …):

```php
 $accountStatment = DB::table('fn_constraint_details')
     ->join('fn_constraints', 'fn_constraints.id', '=', 'fn_constraint_details.constraint_id')
     ->whereNull('fn_constraint_details.deleted_at')
+    ->tap(fn ($q) => BranchReportFilter::apply($q, 'fn_constraint_details'))
     ->get();
```

**ب — SQL نصي** (`TrailBalanceService` الأسطر 107، 149، 304، 364، 399):

```php
+[$branchSql, $branchBindings] = BranchReportFilter::sqlCondition('cd');
+
 $sql = "
     ... INNER JOIN fn_constraint_details cd ON cd.account_id = account_tree.account_id
         INNER JOIN fn_constraints c ON c.id = cd.constraint_id
     WHERE cd.date < ?
       AND cd.deleted_at IS NULL
+      AND {$branchSql}
 ";
+$bindings = [...$bindings, ...$branchBindings];   // ⚠️ الترتيب يجب أن يطابق موضع الشرط
```

> **تحذير:** `TrailBalanceService` يبني `$bindings` يدوياً بترتيب المواضع.
> إدراج شرط جديد يوجب إدراج الـ binding في **نفس الموضع**، وإلا انزاحت كل القيم.
> يُنصح بتغطية هذا الملف باختبار قبل التعديل.

**ج — الوسم بمركز التكلفة** — بديل غير مطلوب الآن: `fn_constraint_details.cost_center_id`
موجود ومربوط بالفرع عبر `branches.cost_center_id`، لكن `branch_id` أدقّ لأنه إلزامي على المستند.

### 4.4 معايير القبول

- [ ] موظف فرع «أ» يفتح ميزان المراجعة ⇒ لا يظهر أي مبلغ من فرع «ب».
- [ ] الإدارة العامة تفتح نفس التقرير ⇒ الأرقام مطابقة لما قبل التغيير بالضبط.
- [ ] تعطيل موديول `branches` ⇒ الأرقام مطابقة لما قبل التغيير بالضبط.
- [ ] موظف الفرع الرئيسي يرى القيود القديمة (`branch_id IS NULL`).
- [ ] `php artisan branches:audit --section=raw` ⇒ صفر في حزمة المالية.

### 4.5 الاختبارات المطلوبة

ملف جديد `tests/Feature/Branch/FinanceReportBranchScopeTest.php`:

1. قيدان بمبلغين مختلفين في فرعين ⇒ ميزان مراجعة كل فرع يُظهر مبلغه فقط.
2. الإدارة العامة ⇒ المجموع الكلي.
3. الموديول معطّل ⇒ المجموع الكلي (اختبار عدم الانحدار).
4. قيد بلا فرع ⇒ يظهر للفرع الرئيسي ولا يظهر لغيره.
5. نفس الأربعة لكشف الحساب وكشف البنك.

---

## 5. المهمة 2 — إسناد البيانات القديمة

**يسبق أي نشر على الإنتاج.** السجل بلا فرع مرئي للفرع الرئيسي وحده؛ أي سجل يخصّ فرعاً آخر
ويبقى `NULL` **يختفي عن موظفي ذلك الفرع**.

```bash
mysqldump ... > backup.sql          # إلزامي
php artisan branches:backfill --dry-run
php artisan branches:backfill
php artisan branches:provision-finance --backfill
```

من إجمالي 213,908 صفاً بلا فرع: نحو 32,500 تشغيلي، والباقي (~181,000) صفوف مالية
أضيف لها العمود حديثاً ويعالجها `provision-finance --backfill`.

`--dry-run` لا يحسب التتابع (`complaints` يظهر صفراً لأن `users` لم يُسنَد بعد)؛ التنفيذ متتابع.

### معايير القبول
- [ ] `branches:audit --section=data` ⇒ الصفوف المتبقية بلا فرع مبرَّرة سطراً سطراً.
- [ ] عيّنة يدوية: 10 عقود من كل فرع تظهر لموظفي فرعها.

---

## 6. المهمة 3 — ربط القبض بصندوق الفرع

اليوم `receiptAccounts()` تُرجع كل الصناديق والبنوك، فلا شيء يمنع صرّاف الرياض من القبض
في صندوق الفرع الرئيسي.

**الملفات:** `SponsorshipTransferPaymentService::receiptAccounts()` وما يماثلها في
`RentalContractFinanceService` و`FinanceBondGenerationService`.

**المطلوب:**
1. قصر القائمة على حسابات الفرع من `branch_finance_accounts` + الحسابات المشتركة.
2. تفعيل `config/branch_finance.php → enforce_single_branch_entries` ورفض القيد الذي يخلط
   حسابات فرعين، في `ConstraintService` عند الحفظ.
3. حساب «جاري بين الفروع» للعمليات العابرة (قرار محاسبي — يحتاج مراجعة المدير المالي).

> البند 3 **قرار عمل وليس تقنياً**؛ يجب حسمه قبل تفعيل البند 2 وإلا تعطّلت عمليات مشروعة.

---

## 7. المهمة 4 — 6 شاشات + 23 موديلاً بلا ختم

### 7.1 الشاشات

| الملف | الموديلات |
|---|---|
| `commissions/CommissionMarketerController.php` | `CommissionBatch`, `CommissionBalanceTransaction` |
| `commissions/CommissionPaymentController.php` | `CommissionBatch` |
| `commissions/CommissionSettingsController.php` | `CommissionMarketerOverride` |
| `packages/crm/.../ClientController.php` | `ClientAttachment` |
| `packages/crm/.../AttachmentCategoryController.php` | `ClientAttachment` |
| `packages/crm/.../PollClientController.php` | `ClientAttachment` |

**الحل:** إضافة `AssignsBranch` + `BranchScoped` لموديلات العمولات الخمسة و`ClientAttachment`
يغلق الست دفعة واحدة — بلا تعديل في الكنترولرات.

### 7.2 الموديلات بلا ختم (23)

`branches:audit --section=models --details`. أغلبها موديلات مالية أضيف لجدولها `branch_id`
ولم يُربط بالتريت: `ConstraintFile`, `ConstraintLog`, `SaleItem`, `PurchaseItem`,
`Stock`, `StockMovement`, `Principle`, `PrincipleSale`, `DepreciationPrinciple`,
`Inventory`, `SalesOrder`, `PurchaseOrder`, `ClientAttachment`.

**الحل:** `use AssignsBranch, BranchScoped;` — سطر واحد لكل موديل.
`Maid` و`RecruitmentRequestMaid` تُراجع يدوياً (منطق فروع خاص).

---

## 8. المهمة 5 — 78 جدولاً بلا `branch_id`

| المنظومة | العدد | ملاحظة |
|---|---|---|
| أخرى | 25 | مراجعة فردية |
| الموارد البشرية | 23 | كلها فارغة عدا `hr_employee` (283 صف) — أرخص وقت الآن |
| العقود والطلبات | 8 | جداول فرعية |
| CRM | 7 | كلها فارغة |
| السير الذاتية | 6 | — |
| السفر والإيواء | 6 | — |
| المالية | 3 | فارغة عملياً |

**النمط:** migration واحدة على غرار
`2026_09_01_000001_add_branch_id_to_finance_tables` — عمود nullable + فهرس + مفتاح أجنبي
`nullOnDelete`، مع `Schema::hasTable`/`hasColumn` على كل خطوة، و`down()` عاملة.

---

## 9. قواعد للمطورين — حتى لا تعود الفجوة

| افعل | لا تفعل |
|---|---|
| `use AssignsBranch, BranchScoped;` لأي موديل جدوله يحمل `branch_id` | لا تعزل جدولاً فيه عمود فريد يُولَّد من `max()` |
| `BranchReportFilter::apply()` في أي `DB::table` على جدول معزول | لا تعتمد على الـ global scope مع `DB::table` — لا يراه |
| `withoutBranchScope()` صراحةً في التقارير التجميعية والترقيم | لا تعتمد على الفلترة اليدوية وحدها في شاشة جديدة |
| `acrossBranches()` عند توليد رقم مستند | لا تُضف عزلاً إلى `Admin` ولا `User` |
| أضف حالة الفرع للاختبار عند إضافة شاشة | لا تفترض أن دور `Administrator` مقيّد بفرع — يملك `branches_view_all` |

### حارس CI

```bash
php artisan branches:audit --json     # exit 1 عند وجود ثغرة
```

يُنصح بتثبيت خط أساس على غرار `tests/baselines/*.json` ليفشل على **الجديد** فقط.

---

## 10. المخاطر والقرارات المفتوحة

| # | البند | الأثر | القرار المطلوب |
|---|---|---|---|
| 1 | `TrailBalanceService` يبني bindings يدوياً بالترتيب | انزياح القيم ⇒ **أرقام مالية خاطئة صامتة** | تغطية باختبار قبل التعديل |
| 2 | حساب «جاري بين الفروع» | يعطّل عمليات مشروعة إن فُعّل المنع بدونه | قرار المدير المالي |
| 3 | ترقيم السندات: متصل أم دفتر لكل فرع؟ | مطبَّق حالياً: **متصل** (حفاظاً على السلوك) | قرار عمل |
| 4 | فلترة شجرة الحسابات على مستوى الشاشة | موظف الفرع يرى أسماء حسابات فروع أخرى | أولوية منخفضة — لا يكشف أرصدة |
| 5 | `packages/*` submodules في الإنتاج | تعديلات المالية تحتاج PR منفصلاً في مستودع الحزمة | تنسيق مع مسؤول الحزم |
| 6 | 49 استعلاماً «يحتاج مراجعة يدوية» | قد يخفي تسريبات | مراجعة في نفس sprint المهمة 1 |

---

## 11. الترتيب المقترح

| # | المهمة | التبعية | الحجم التقريبي |
|---|---|---|---|
| 1 | إسناد البيانات (§5) | — | نصف يوم + نسخة احتياطية |
| 2 | `BranchReportFilter` + تغطية اختبارية | — | يوم |
| 3 | تطبيقه على 56 موضعاً (§4) | 2 | 2–3 أيام |
| 4 | الشاشات والموديلات (§7) | — | يوم |
| 5 | ربط القبض بالصندوق (§6) | قرار #2 | 2 أيام |
| 6 | الجداول المتبقية (§8) | — | يوم |
| 7 | حارس CI (§9) | 3 | ساعتان |

المهمتان 1 و2 مستقلتان ويمكن تنفيذهما بالتوازي.

---

## 12. وضع الاختبارات الحالي

| | نظيف | بعد العمل |
|---|---|---|
| الاختبارات | 593 | 604 |
| مجموع الفشل | 12 | 9 |
| اختبارات الفروع | 108 | 119 (كلها تنجح) |
| فشل جديد | — | **0** |

الفشل التسعة موجود قبل هذا العمل وخارج نطاق الفروع (تخزين R2، روابط فواتير التأجير،
`crm.print-price-offers` بلا صلاحية).


---

## 13. حالة التنفيذ — 5 سبتمبر 2026

| المهمة | الحالة | الدليل |
|---|---|---|
| §4 عزل التقارير المالية | ✅ **مُنفَّذة** | `branches:audit --section=raw` ⇒ صفر تسريب مؤكد |
| §6 الشاشات الست | ✅ **مُنفَّذة** | `--section=controllers` ⇒ صفر |
| §7 الموديلات بلا ختم (23) | ✅ **مُنفَّذة** | `--section=models` ⇒ صفر |
| §9 حارس CI | ✅ **مُنفَّذ** | `branches:audit` ⇒ exit 0 |
| §5 إسناد البيانات | ⏳ **بانتظار التنفيذ** | يحتاج قاعدة الإنتاج + نسخة احتياطية |
| §6 ربط القبض بالصندوق | ⏳ **بانتظار قرار محاسبي** | حساب «جاري بين الفروع» |
| §8 الـ 78 جدولاً | ⏳ **مؤجّلة** | كلها فارغة عملياً؛ ليست تسريباً |

### ما نُفِّذ بالتفصيل

**`app/Support/BranchReportFilter.php`** — الفئة المشتركة كما وردت في §4.2 حرفياً،
مع دالة ثالثة `active()` للتفريعات الصريحة. طُبِّقت على 20 خدمة/متحكم.

**نقطتان دقيقتان ظهرتا أثناء التنفيذ ولم تكونا في المواصفة:**

1. **`AuditBudgetService`** — الشرط وُضع **داخل الـ `ON`** لا في `WHERE`. الأساس هناك
   `fn_accounts` مربوطاً بـ `LEFT JOIN` إلى تفاصيل القيود؛ شرط في `WHERE` كان سيحوّله إلى
   `INNER JOIN` وتختفي الحسابات التي لا حركة لها.
2. **الاستعلامات المرتبطة بمستند محدّد لا تُعزل.** `RentalContractFinanceService`
   و`RentalClosureCorrectionService` تحسبان مجموع سطور قيد بمعرّفه؛ عزلها كان سيُصفّر
   قيمة فاتورة قديمة بلا فرع لموظف فرع غير رئيسي. القاعدة موثّقة في
   `RAW_QUERY_EXCEPTIONS` مع سبب لكل ملف.

**أربع خدمات ظنّناها مسرِّبة وليست كذلك** — `PurchaseService`، `PurchaseItemService`،
`BillSalesService`، `BillSalesItemService`: أساسها `$this->model` (Eloquent معزول)،
والـ join ثانوي.

### التغطية الاختبارية المضافة

| الملف | يثبت |
|---|---|
| `FinanceReportBranchScopeTest` | ميزان المراجعة وكشف الحساب: 8 حالات بمبالغ محدّدة، منها الرصيد الافتتاحي (فحص انزياح الـ bindings) |
| `BranchesDisabledRegressionTest` | 6 حالات — **كل** موديل معزول (61) لا يضيف شرط فرع عند التعطيل، ويضيفه عند التفعيل |

`BranchesDisabledRegressionTest` يقرأ الموديلات من الشيفرة لا من قائمة يدوية، فأي موديل
يُضاف لاحقاً يدخل الضمان تلقائياً. ويحوي تحكّماً إيجابياً يمنع نجاحه لسبب خاطئ.

### حالة الاختبارات

| | خط أساس نظيف | بعد التنفيذ |
|---|---|---|
| الاختبارات | 593 | **618** |
| اختبارات الفروع | 108 | **133** |
| مجموع الفشل | 12 | **9** |
| فشل جديد | — | **0** |

الفشل التسعة مطابق تماماً لمجموعة خط الأساس ناقص الثلاثة التي أُصلحت.
