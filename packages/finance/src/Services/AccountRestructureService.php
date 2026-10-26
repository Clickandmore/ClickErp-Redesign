<?php

namespace Nami\Finance\Services;

use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\OperationTypeEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\AccountRestructureLog;
use Nami\Finance\Models\FiscalYearClosure;
use Nami\Finance\Models\FnYear;

/**
 * إعادة هيكلة الدليل المحاسبي: حذف حساب، نقله تحت أب آخر، أو دمجه في حساب آخر.
 *
 * - الحذف: لحساب بلا فروع ولا قيود ولا أي ارتباط (عميل، مورد، بنك، إعداد…).
 * - النقل: يغيّر الأب فقط؛ يُعاد ترقيم الحساب وفروعه، والقيود تبقى على نفس الحساب.
 * - الدمج: كل سطور القيود وكل الارتباطات تنتقل من الحساب المصدر إلى الهدف ثم يُحذف المصدر.
 *
 * كل عملية تُسجَّل في fn_account_restructure_logs.
 */
class AccountRestructureService
{
    /**
     * كل عمود في النظام يحمل رقم حساب من الدليل (أغلبها بلا مفتاح أجنبي).
     * أي جدول جديد يحمل حساباً يجب إضافته هنا، وإلا سيمنع الحذفُ الآمن عن غير علم
     * ويبقى العمود يشير لحساب مدموج محذوف.
     */
    public const REFERENCES = [
        'fn_constraint_details' => ['account_id' => 'سطور القيود'],
        'fn_bonds' => ['debit_account_id' => 'السندات (مدين)', 'credit_account_id' => 'السندات (دائن)'],
        'fn_bonds_taxs' => ['tax_account_id' => 'ضرائب السندات'],
        'fn_account_transfers' => ['debit_account_id' => 'التحويلات بين الحسابات (مدين)', 'credit_account_id' => 'التحويلات بين الحسابات (دائن)'],
        'fn_double_account_details' => ['account_id' => 'الحسابات المزدوجة'],
        'fn_boxes' => ['account_id' => 'الصناديق'],
        'banks' => ['account_id' => 'البنوك'],
        'bank_accounts' => ['account_id' => 'الحسابات البنكية'],
        'fn_clients' => ['account_id' => 'العملاء (المالية)'],
        'fn_suppliers' => ['account_id' => 'الموردون (المالية)'],
        'users' => ['account_id' => 'عملاء الموقع'],
        'recruitment_offices' => ['account_id' => 'الوكلاء الخارجيون'],
        'company_profiles' => ['account_id' => 'ملفات الشركات'],
        'admins' => ['commission_payable_account_id' => 'حساب عمولات الموظفين'],
        'branch_finance_accounts' => ['account_id' => 'حسابات الفروع'],
        'fn_tax_settings' => ['account_id' => 'إعدادات الضرائب'],
        'fn_items' => ['sale_account_id' => 'الأصناف (حساب البيع)', 'stock_account_id' => 'الأصناف (حساب المخزون)'],
        'fn_item_taxs' => ['tax_account_id' => 'ضرائب الأصناف'],
        'fn_principles' => ['account_id' => 'الأصول الثابتة', 'parent_account_id' => 'الأصول الثابتة (الحساب الرئيسي)', 'creditor_account_id' => 'الأصول الثابتة (الحساب الدائن)'],
        'fn_depreciation_principles' => ['account_id' => 'إهلاك الأصول'],
        'fn_principle_perished' => ['expense_account_id' => 'إتلاف الأصول (مصروف)', 'perished_account_id' => 'إتلاف الأصول'],
        'fn_principle_sales' => ['debit_account_id' => 'بيع الأصول', 'tax_account_id' => 'بيع الأصول (ضريبة)'],
        'fn_procurement_documents' => ['supplier_account_id' => 'مستندات المشتريات (المورد)', 'tax_account_id' => 'مستندات المشتريات (ضريبة)'],
        'fn_procurement_lines' => ['account_id' => 'بنود المشتريات'],
        'fn_procurement_payments' => ['account_id' => 'دفعات المشتريات'],
        'contracts' => ['company_finance_account_id' => 'العقود (حساب الشركة)'],
        'contract_return_settlement_lines' => ['debit_account_id' => 'مستندات الاسترداد (مدين)', 'credit_account_id' => 'مستندات الاسترداد (دائن)'],
        'sponsorship_transfers' => ['receipt_account_id' => 'نقل الخدمات (حساب القبض)', 'refund_account_id' => 'نقل الخدمات (حساب الاسترداد)', 'old_company_finance_account_id' => 'نقل الخدمات (حساب الشركة السابق)'],
        'commission_batches' => ['expense_account_id' => 'دفعات العمولات (مصروف)', 'payable_account_id' => 'دفعات العمولات (مستحق)'],
        'musaned_transfers' => ['bank_account_id' => 'تحويلات مساند (البنك)', 'intermediate_account_id' => 'تحويلات مساند (الوسيط)', 'commission_account_id' => 'تحويلات مساند (العمولة)', 'expense_tax_account_id' => 'تحويلات مساند (ضريبة)'],
        'musaned_transfer_items' => ['source_account_id' => 'بنود تحويلات مساند'],
        'rental_contract_invoices' => ['receipt_account_id' => 'فواتير عقود الإيجار'],
        'rental_contract_extensions' => ['receipt_account_id' => 'تمديدات عقود الإيجار'],
        'rental_financial_adjustments' => ['account_id' => 'تسويات الإيجار المالية'],
        'shelter_cost_entries' => ['office_account_id' => 'دفتر الإيواء (المكتب)', 'asset_account_id' => 'دفتر الإيواء (الأصل)'],
        'shelter_agent_cost_recoveries' => ['office_account_id' => 'استرداد تكلفة الوكيل (المكتب)', 'asset_account_id' => 'استرداد تكلفة الوكيل (الأصل)'],
        'shelter_living_expenses' => ['expense_account_id' => 'مصروفات الإعاشة (مصروف)', 'payment_account_id' => 'مصروفات الإعاشة (الدفع)'],
        'hr_employee_custodies' => ['account_id' => 'عهد الموظفين'],
    ];

    private array $columnExists = [];

    public function __construct(protected AccountService $accounts)
    {
    }

    // ── الاستعلام ────────────────────────────────────────────────────────────

    /**
     * ارتباطات الحساب غير الصفرية: [['table','column','label','count']]، والإعدادات، والفروع.
     */
    public function usage(Account $account): array
    {
        $references = [];
        foreach ($this->references() as [$table, $column, $label]) {
            $count = DB::table($table)->where($column, $account->id)->count();
            if ($count > 0) {
                $references[] = compact('table', 'column', 'label', 'count');
            }
        }

        return [
            'references' => $references,
            'settings' => $this->settingTitlesFor($account),
            'children' => Account::query()->where('parent_id', $account->id)->count(),
        ];
    }

    public function constraintLinesCount(Account $account): int
    {
        return DB::table('fn_constraint_details')->where('account_id', $account->id)->count();
    }

    public function deleteBlockers(Account $account, ?array $usage = null): array
    {
        $usage ??= $this->usage($account);
        $blockers = $this->systemAccountBlockers($account);

        if ($usage['children'] > 0) {
            $blockers[] = 'للحساب ' . $usage['children'] . ' حساب فرعي؛ انقلها أو احذفها أولاً.';
        }
        foreach ($usage['references'] as $reference) {
            $blockers[] = 'مرتبط بـ ' . $reference['label'] . ' (' . $reference['count'] . ').';
        }
        if ($usage['settings']) {
            $blockers[] = 'مستخدم في إعدادات الحسابات: ' . implode('، ', $usage['settings']) . '.';
        }
        if ($blockers && $usage['references']) {
            $blockers[] = 'لنقل القيود والارتباطات إلى حساب آخر استخدم «دمج».';
        }

        return $blockers;
    }

    public function moveBlockers(Account $account, ?Account $parent): array
    {
        $blockers = $this->systemAccountBlockers($account, true);

        if ((int) $account->parent_id === (int) $parent?->id) {
            $blockers[] = 'الحساب موجود بالفعل تحت هذا الحساب الرئيسي.';
        }
        if ($parent) {
            if ((int) $parent->id === (int) $account->id || in_array((int) $parent->id, $this->descendantIds($account), true)) {
                $blockers[] = 'لا يمكن نقل الحساب تحت نفسه أو تحت أحد فروعه.';
            }
            if ((int) $parent->operation_type === OperationTypeEnum::Special->value) {
                $blockers[] = 'الحساب الرئيسي المختار حساب خاص لا يقبل حسابات فرعية.';
            }
            if ($this->constraintLinesCount($parent) > 0) {
                $blockers[] = 'الحساب الرئيسي المختار عليه قيود؛ الحساب الذي عليه قيود لا يصبح حساباً رئيسياً.';
            }
        }

        return $blockers;
    }

    /** موانع خاصة بالحساب المصدر وحده (تُعرض في نموذج الدمج قبل اختيار الهدف). */
    public function mergeSourceBlockers(Account $source): array
    {
        $blockers = $this->systemAccountBlockers($source);

        if (Account::query()->where('parent_id', $source->id)->exists()) {
            $blockers[] = 'الحساب المصدر له حسابات فرعية؛ الدمج متاح بين حسابين فرعيين فقط.';
        }
        if ($closed = $this->closedPeriodLines($source)) {
            $blockers[] = 'على الحساب المصدر ' . $closed . ' سطر قيد داخل فترة مالية مقفلة؛ لا يمكن نقلها.';
        }

        return $blockers;
    }

    public function mergeBlockers(Account $source, Account $target): array
    {
        $blockers = $this->mergeSourceBlockers($source);

        if ((int) $source->id === (int) $target->id) {
            $blockers[] = 'لا يمكن دمج الحساب في نفسه.';
        }
        if (Account::query()->where('parent_id', $target->id)->exists()) {
            $blockers[] = 'الحساب الهدف حساب رئيسي له فروع؛ اختر حساباً فرعياً تُسجَّل عليه القيود.';
        }

        return $blockers;
    }

    /** فروق لا تمنع الدمج لكن يجب أن يراها المستخدم قبل التأكيد. */
    public function mergeWarnings(Account $source, Account $target): array
    {
        $warnings = [];
        if ($source->status !== $target->status) {
            $warnings[] = 'طبيعة الحسابين مختلفة (' . ($source->status?->lang() ?? '-') . ' / ' . ($target->status?->lang() ?? '-') . ').';
        }
        if ($source->type !== $target->type) {
            $warnings[] = 'نوع الحسابين مختلف (' . ($source->type?->lang() ?? '-') . ' / ' . ($target->type?->lang() ?? '-') . ').';
        }

        return $warnings;
    }

    /** الآباء المتاحون للنقل: صالحون كآباء، ليسوا الحساب أو فروعه، ولا قيود عليهم. */
    public function moveTargetsFor(Account $account): Collection
    {
        $excluded = array_merge([(int) $account->id, (int) $account->parent_id], $this->descendantIds($account));
        $withLines = DB::table('fn_constraint_details')->distinct()->pluck('account_id')->map(fn ($id) => (int) $id)->flip();

        return $this->accounts->getValidParents()
            ->reject(fn (Account $parent) => in_array((int) $parent->id, $excluded, true) || $withLines->has((int) $parent->id))
            ->sortBy('code')
            ->values();
    }

    /** أهداف الدمج: كل حساب فرعي آخر (بلا فروع). */
    public function mergeTargetsFor(Account $account): Collection
    {
        return Account::query()
            ->whereKeyNot($account->id)
            ->whereDoesntHave('children')
            ->orderBy('code')
            ->get(['id', 'name', 'code', 'status', 'type']);
    }

    // ── التنفيذ ──────────────────────────────────────────────────────────────

    public function delete(int $accountId, ?string $reason = null): void
    {
        DB::transaction(function () use ($accountId, $reason) {
            $account = Account::query()->lockForUpdate()->findOrFail($accountId);
            $this->assertNoBlockers($this->deleteBlockers($account), 'account');

            $snapshot = $account->getAttributes();
            $this->detachBranches($account);
            $account->delete();

            $this->log(AccountRestructureLog::ACTION_DELETE, $snapshot, $reason);
        });
    }

    public function move(int $accountId, ?int $parentId, ?string $reason = null): Account
    {
        return DB::transaction(function () use ($accountId, $parentId, $reason) {
            $account = Account::query()->lockForUpdate()->findOrFail($accountId);
            $parent = $parentId ? Account::query()->lockForUpdate()->findOrFail($parentId) : null;
            $this->assertNoBlockers($this->moveBlockers($account, $parent), 'parent_id');

            $snapshot = $account->getAttributes();
            $oldCodes = $this->subtreeCodes($account);
            $this->accounts->moveAccount($account, $parent);
            $account->refresh();

            $this->log(AccountRestructureLog::ACTION_MOVE, $snapshot, $reason, [
                'old_parent_id' => $snapshot['parent_id'],
                'new_parent_id' => $parent?->id,
            ], [
                'old_codes' => $oldCodes,
                'new_codes' => $this->subtreeCodes($account),
            ]);

            return $account;
        });
    }

    /**
     * @return array{target: Account, moved: array<string,int>}
     */
    public function merge(int $sourceId, int $targetId, string $reason): array
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'سبب الدمج مطلوب.']);
        }

        return DB::transaction(function () use ($sourceId, $targetId, $reason) {
            // ترتيب ثابت للقفل يمنع التعارض بين دمجين متعاكسين.
            $locked = Account::query()->whereKey([$sourceId, $targetId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $source = $locked->get($sourceId) ?? throw ValidationException::withMessages(['account' => 'الحساب المصدر غير موجود.']);
            $target = $locked->get($targetId) ?? throw ValidationException::withMessages(['target_account_id' => 'الحساب الهدف غير موجود.']);
            $this->assertNoBlockers($this->mergeBlockers($source, $target), 'target_account_id');

            $snapshot = $source->getAttributes();
            $moved = [];
            $movedIds = [];

            try {
                foreach ($this->references() as [$table, $column, $label]) {
                    $query = DB::table($table)->where($column, $source->id);
                    $ids = $this->hasColumn($table, 'id') ? (clone $query)->pluck('id')->all() : [];
                    $count = $query->update([$column => $target->id]);
                    if ($count > 0) {
                        $moved[$label] = ($moved[$label] ?? 0) + $count;
                        $movedIds[$table . '.' . $column] = $ids;
                    }
                }
            } catch (QueryException $e) {
                throw ValidationException::withMessages([
                    'target_account_id' => 'تعذر الدمج لتعارض في البيانات (الحساب الهدف مرتبط بالفعل بنفس السجل): ' . $e->getMessage(),
                ]);
            }

            $settings = $this->settingTitlesFor($source);
            if ($settings) {
                DB::table('fn_confirm_settings')->whereIn('title', $settings)->where('value', (string) $source->id)
                    ->update(['value' => (string) $target->id]);
                $moved['إعدادات الحسابات'] = count($settings);
            }

            $branchIds = $this->branchIdsOf($source);
            $this->detachBranches($source);
            $source->delete();

            $this->log(AccountRestructureLog::ACTION_MERGE, $snapshot, $reason, ['target_account_id' => $target->id], [
                'target' => ['id' => $target->id, 'code' => $target->code, 'name' => $target->name],
                'moved' => $moved,
                'moved_ids' => $movedIds,
                'settings' => $settings,
                'branch_ids' => $branchIds,
            ]);

            return ['target' => $target, 'moved' => $moved];
        });
    }

    /** موانع التراجع عن دمج مسجل (فارغة = ممكن). */
    public function undoMergeBlockers(AccountRestructureLog $log): array
    {
        if ($log->action !== AccountRestructureLog::ACTION_MERGE) {
            return ['السجل ليس عملية دمج.'];
        }
        if ($log->reversed_at) {
            return ['تم التراجع عن هذا الدمج سابقاً.'];
        }

        $blockers = [];
        $snapshot = (array) $log->snapshot;
        $details = (array) $log->details;
        if (!isset($snapshot['id'], $snapshot['code']) || !isset($details['moved_ids'])) {
            return ['السجل لا يحتوي بيانات كافية للتراجع (دمج قبل إضافة ميزة التراجع).'];
        }

        if (!Account::query()->whereKey($log->target_account_id)->exists()) {
            $blockers[] = 'الحساب الهدف #' . $log->target_account_id . ' لم يعد موجوداً (ربما دُمج أو حُذف بعدها)؛ تراجع عن العملية اللاحقة أولاً.';
        }
        if (Account::query()->whereKey($snapshot['id'])->exists()) {
            $blockers[] = 'يوجد حساب بنفس رقم الحساب المدموج #' . $snapshot['id'] . '.';
        }
        if ($taken = Account::query()->where('code', $snapshot['code'])->first(['id', 'name'])) {
            $blockers[] = 'الكود ' . $snapshot['code'] . ' أصبح مستخدماً للحساب «' . $taken->name . '»؛ انقل ذلك الحساب أو غيّر كوده أولاً.';
        }
        if (!empty($snapshot['parent_id']) && !Account::query()->whereKey($snapshot['parent_id'])->exists()) {
            $blockers[] = 'الحساب الرئيسي الأصلي #' . $snapshot['parent_id'] . ' لم يعد موجوداً.';
        }
        foreach ((array) ($details['moved'] ?? []) as $label => $count) {
            if ($count > 0 && $label !== 'إعدادات الحسابات' && !$this->labelHasSavedIds($label, $details['moved_ids'])) {
                $blockers[] = 'لم تُحفظ أرقام صفوف «' . $label . '»؛ لا يمكن إرجاعها آلياً.';
            }
        }
        $lineIds = (array) ($details['moved_ids']['fn_constraint_details.account_id'] ?? []);
        if ($lineIds && ($closed = $this->closedLinesCount(fn ($query) => $query->whereIn('d.id', $lineIds)))) {
            $blockers[] = $closed . ' من سطور القيود المنقولة أصبحت داخل فترة مالية مقفلة؛ لا يمكن إرجاعها.';
        }

        return $blockers;
    }

    /**
     * التراجع عن دمج: يعيد إنشاء الحساب المصدر بنفس رقمه وكوده، ويعيد إليه الصفوف المنقولة
     * التي ما زالت تشير إلى الهدف فقط (أي حركة جديدة على الهدف بعد الدمج تبقى عليه).
     * @return array{account: Account, restored: array<string,int>, skipped: array<string,int>}
     */
    public function undoMerge(int $logId, string $reason): array
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'سبب التراجع مطلوب.']);
        }

        return DB::transaction(function () use ($logId, $reason) {
            $log = AccountRestructureLog::query()->lockForUpdate()->findOrFail($logId);
            $this->assertNoBlockers($this->undoMergeBlockers($log), 'log');

            $snapshot = (array) $log->snapshot;
            $details = (array) $log->details;
            $target = Account::query()->lockForUpdate()->findOrFail($log->target_account_id);
            $sourceId = (int) $snapshot['id'];

            $columns = Schema::getColumnListing('fn_accounts');
            DB::table('fn_accounts')->insert(array_intersect_key($snapshot, array_flip($columns)));

            $labels = [];
            foreach ($this->references() as [$table, $column, $label]) {
                $labels[$table . '.' . $column] = $label;
            }

            $restored = [];
            $skipped = [];
            foreach ((array) $details['moved_ids'] as $key => $ids) {
                [$table, $column] = explode('.', $key, 2);
                if (!$ids || !$this->hasColumn($table, $column)) {
                    continue;
                }
                $label = $labels[$key] ?? $key;
                $count = 0;
                foreach (array_chunk($ids, 1000) as $chunk) {
                    $count += DB::table($table)->whereIn('id', $chunk)->where($column, $target->id)->update([$column => $sourceId]);
                }
                $restored[$label] = ($restored[$label] ?? 0) + $count;
                if ($count < count($ids)) {
                    $skipped[$label] = ($skipped[$label] ?? 0) + count($ids) - $count;
                }
            }

            if ($settings = (array) ($details['settings'] ?? [])) {
                $restored['إعدادات الحسابات'] = DB::table('fn_confirm_settings')->whereIn('title', $settings)
                    ->where('value', (string) $target->id)->update(['value' => (string) $sourceId]);
            }

            if (Schema::hasTable('fn_account_branch')) {
                foreach (DB::table('branches')->whereIn('id', (array) ($details['branch_ids'] ?? []))->pluck('id') as $branchId) {
                    DB::table('fn_account_branch')->insert([
                        'account_id' => $sourceId, 'branch_id' => $branchId, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            $log->update(['reversed_at' => now(), 'reversed_by_id' => auth('admin')->id(), 'reversal_reason' => trim($reason)]);
            $this->log(AccountRestructureLog::ACTION_MERGE_UNDO, $snapshot, $reason, ['target_account_id' => $target->id], [
                'merge_log_id' => $log->id,
                'restored' => $restored,
                'skipped' => $skipped,
            ]);

            return ['account' => Account::query()->findOrFail($sourceId), 'restored' => $restored, 'skipped' => $skipped];
        });
    }

    private function labelHasSavedIds(string $label, array $movedIds): bool
    {
        foreach ($this->references() as [$table, $column, $referenceLabel]) {
            if ($referenceLabel === $label && !empty($movedIds[$table . '.' . $column])) {
                return true;
            }
        }

        return false;
    }

    private function branchIdsOf(Account $account): array
    {
        return Schema::hasTable('fn_account_branch')
            ? DB::table('fn_account_branch')->where('account_id', $account->id)->pluck('branch_id')->map(fn ($id) => (int) $id)->all()
            : [];
    }

    // ── مساعدات ──────────────────────────────────────────────────────────────

    /** [[table, column, label]] للأعمدة الموجودة فعلاً في قاعدة البيانات الحالية. */
    private function references(): array
    {
        $references = [];
        foreach (self::REFERENCES as $table => $columns) {
            foreach ($columns as $column => $label) {
                if ($this->hasColumn($table, $column)) {
                    $references[] = [$table, $column, $label];
                }
            }
        }

        return $references;
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->columnExists[$table . '.' . $column] ??= Schema::hasTable($table) && Schema::hasColumn($table, $column);
    }

    /** إعدادات الحسابات (fn_confirm_settings) التي قيمتها هذا الحساب. يُستبعد ما ليس حساباً بالاسم. */
    private function settingTitlesFor(Account $account): array
    {
        if (!$this->hasColumn('fn_confirm_settings', 'title')) {
            return [];
        }

        return DB::table('fn_confirm_settings')
            ->where('value', (string) $account->id)
            ->where('title', 'like', '%account%')
            ->pluck('title')
            ->all();
    }

    /**
     * أكواد يبحث عنها النظام بالكود مباشرة (لا بالإعدادات). نقلها يغيّر كودها ودمجها/حذفها
     * يُفقدها، فتتعطل الشاشات المعتمدة عليها. أي كود ثابت جديد في الكود يُضاف هنا.
     * ملاحظة: operation_type لا يصلح للحماية؛ قيمته الافتراضية 1 على كل الحسابات تقريباً.
     */
    public const PROTECTED_CODES = [
        '144',        // CommissionMarketerAccountService
        '156', '122', // Account::MUSANED_PARENT_CODES
        '221',        // الوكلاء الخارجيون: OfficeFinanceAccountService / AccountService / AuditBudgetService
        '527', '5450', // ShelterLivingExpenseAccountingService
        '4216',       // RentalContractFinanceService
        '5417', '52149', // CommissionAccrualService
        '5911', '5921', '5922', '5923', '5924', '5925', '5926', '5931', // RentalMaidFinanceService
        '1592', '1593', '1594', '1595', '1596', '1597', '1598', '1599',
        '1230000000', // DataImportExecutionService
    ];

    /** أعلى مستويين هما هيكل الدليل (الأصول، الخصوم، … وفروعها الأولى). */
    public const STRUCTURAL_MAX_LEVEL = 2;

    public static function protectedCodes(): array
    {
        $anchors = array_column((array) config('branch_finance.anchors', []), 'code');

        return array_values(array_unique(array_map('strval', array_merge(self::PROTECTED_CODES, $anchors))));
    }

    /** حماية سريعة على صف واحد (لإظهار الأزرار)؛ الخادم يعيد الفحص كاملاً مع الفروع. */
    public static function isProtected(Account $account): bool
    {
        return (int) $account->level <= self::STRUCTURAL_MAX_LEVEL
            || in_array((string) $account->code, self::protectedCodes(), true);
    }

    public function systemAccountBlockers(Account $account, bool $withDescendants = false): array
    {
        if ((int) $account->level <= self::STRUCTURAL_MAX_LEVEL) {
            return ['الحساب من هيكل الدليل الأساسي (المستوى ' . $account->level . ') ولا يُحذف أو يُنقل أو يُدمج.'];
        }

        $ids = array_merge([(int) $account->id], $withDescendants ? $this->descendantIds($account) : []);
        $codes = Account::query()->whereKey($ids)->whereIn('code', self::protectedCodes())->pluck('code')->map(fn ($code) => (string) $code)->all();
        if ($codes) {
            return ['الحساب ' . ($withDescendants && $codes !== [(string) $account->code] ? 'أو أحد فروعه ' : '')
                . 'يستخدمه النظام بكوده (' . implode('، ', $codes) . ')؛ تغييره يعطل شاشات تعتمد عليه.'];
        }

        return [];
    }

    private function closedPeriodLines(Account $account): int
    {
        return $this->closedLinesCount(fn ($query) => $query->where('d.account_id', $account->id));
    }

    /** عدد سطور القيود (المحددة بـ $scope على الاسم المستعار d) الواقعة داخل فترة مالية مقفلة. */
    private function closedLinesCount(\Closure $scope): int
    {
        if (!Schema::hasTable('fn_fiscal_year_closures')) {
            return 0;
        }

        $total = 0;
        foreach (FiscalYearClosure::query()->whereNull('reversed_at')->distinct()->pluck('closing_date') as $closingDate) {
            $year = FnYear::query()->whereDate('from_date', '<=', $closingDate)->whereDate('to_date', '>=', $closingDate)->first();
            $total += DB::table('fn_constraint_details as d')
                ->join('fn_constraints as c', 'c.id', '=', 'd.constraint_id')
                ->where($scope)
                ->whereDate('c.date', '<=', $closingDate)
                ->when($year, fn ($query) => $query->whereDate('c.date', '>=', $year->from_date))
                ->count();
        }

        return $total;
    }

    private function descendantIds(Account $account): array
    {
        $ids = [];
        $frontier = [(int) $account->id];
        while ($frontier) {
            $frontier = Account::query()->whereIn('parent_id', $frontier)->pluck('id')->map(fn ($id) => (int) $id)->all();
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    private function subtreeCodes(Account $account): array
    {
        return Account::query()->whereKey(array_merge([(int) $account->id], $this->descendantIds($account)))
            ->orderBy('code')->pluck('code', 'id')->all();
    }

    private function detachBranches(Account $account): void
    {
        if (Schema::hasTable('fn_account_branch')) {
            DB::table('fn_account_branch')->where('account_id', $account->id)->delete();
        }
    }

    private function assertNoBlockers(array $blockers, string $field): void
    {
        if ($blockers) {
            throw ValidationException::withMessages([$field => $blockers]);
        }
    }

    private function log(string $action, array $snapshot, ?string $reason, array $columns = [], array $details = []): void
    {
        if (!Schema::hasTable('fn_account_restructure_logs')) {
            throw ValidationException::withMessages(['account' => 'يلزم تشغيل تحديث قاعدة البيانات (fn_account_restructure_logs) قبل تعديل الدليل.']);
        }

        AccountRestructureLog::query()->create($columns + [
            'action' => $action,
            'account_id' => $snapshot['id'],
            'account_code' => (string) $snapshot['code'],
            'account_name' => $snapshot['name'],
            'reason' => $reason !== null && trim($reason) !== '' ? trim($reason) : null,
            'snapshot' => $snapshot,
            'details' => $details ?: null,
            'created_by_id' => auth('admin')->id(),
        ]);
    }
}
