<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\Contract;
use App\Models\ingaz\Invoice;
use App\Models\MusanedTransfer;
use App\Models\MusanedTransferItem;
use App\Support\AutomaticJournalEntries;
use App\Support\MusanedCommissionPosting;
use App\Support\RecruitmentCostCenters;
use App\Support\RecruitmentSupplierSettings;
use App\Support\IngazDelegationTimeline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Enums\ConfirmSettingTypes;
use Nami\Finance\Enums\CostCenterTypeEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\ConstraintDetails;
use Nami\Finance\Models\CostCenter;
use Nami\Finance\Models\TaxSetting;
use Nami\Finance\Services\ConstraintService as ObjService;
use Nami\Finance\Services\YearService;

class MusanedTransferController extends Controller
{
    public const DEFAULT_COMMISSION_RATE = 2.4;

    public function recordInvoiceCommission(Invoice $invoice): ?Constraint
    {
        // Existing invoices retain their accounting sequence after switching the setting.
        if (!\App\Support\InvoiceSupplierWorkflow::enabled() && $invoice->musaned_commission_amount === null) return null;
        if (!$invoice->contract_id && !$invoice->ingaz_delegation_id) return null;

        return DB::transaction(function () use ($invoice): ?Constraint {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            if ($locked->musaned_commission_constraint_id) {
                return Constraint::query()->find((int) $locked->musaned_commission_constraint_id);
            }
            if ($locked->musanedTransferItem()->exists()) return null;
            $rate = is_numeric($locked->musaned_commission_rate)
                ? (float) $locked->musaned_commission_rate
                : self::DEFAULT_COMMISSION_RATE;
            $amount = $locked->musaned_commission_amount !== null
                ? $this->roundMoney((float) $locked->musaned_commission_amount)
                : $this->roundMoney((float) $locked->total_due * $rate / 100);

            if ($amount > $this->roundMoney((float) $locked->total_due)) {
                throw new \RuntimeException('مبلغ التكاليف البنكية لا يمكن أن يتجاوز إجمالي الفاتورة.');
            }

            $tax = $this->calculateExpenseTaxAmount($amount, $this->resolveExpenseTaxRate());
            $locked->update([
                'musaned_commission_rate' => $rate,
                'musaned_commission_amount' => $amount,
                'musaned_commission_tax' => $tax,
            ]);
            if (!AutomaticJournalEntries::enabled() || $amount <= 0) return null;
            $sourceId = $this->resolveInvoiceMusanedSourceAccountId($locked, 0);
            if (!$sourceId) {
                $sourceId = (int) $this->resolveIntermediateAccount(
                    $locked->contract?->branch_id ?: $locked->ingazDelegation?->branch_id
                )->id;
            }
            $commission = $this->resolveCommissionAccount();
            $taxAccount = $this->resolveExpenseTaxAccount();
            if ($tax > 0 && !$taxAccount) throw new \RuntimeException('يجب تحديد حساب ضريبة المصروفات لتسجيل التكاليف البنكية لمساند.');
            $date = $locked->created_at->toDateString();
            $year = app(YearService::class)->checkDateRange($date);
            if (!$year) throw new \RuntimeException('لا توجد سنة مالية نشطة لتاريخ التكاليف البنكية لمساند.');
            $statement = 'التكاليف البنكية لمساند عند إصدار الفاتورة #' . $locked->id;
            $lines = [];
            $costCenter = $locked->contract ? $this->ensureRecruitmentContractCostCenter($locked->contract)->id : null;

            // إعداد «قيدان بدل ثلاثة»: سطور العمولة داخل قيد سند القبض، ومساند بالصافي.
            // بلا عمود التثبيت لا يُعرف لاحقاً أن العمولة داخل السند، فيعكس الاسترداد السند كله.
            if (MusanedCommissionPosting::invoiceColumnExists()
                && MusanedCommissionPosting::modeForInvoice($locked) === MusanedCommissionPosting::WITH_RECEIPT) {
                $merged = $this->mergeCommissionIntoReceiptConstraint(
                    $locked,
                    $amount,
                    $tax,
                    (int) $commission->id,
                    $taxAccount ? (int) $taxAccount->id : null,
                    $costCenter,
                    $statement
                );
                if ($merged) {
                    $locked->update(array_merge(
                        ['musaned_commission_constraint_id' => $merged->id],
                        MusanedCommissionPosting::invoiceColumnExists()
                            ? [MusanedCommissionPosting::INVOICE_COLUMN => MusanedCommissionPosting::WITH_RECEIPT]
                            : []
                    ));
                    $invoice->refresh();

                    return $merged;
                }
            }

            $this->appendConstraintLine($lines, $commission->id, 'debtor', $this->roundMoney($amount - $tax), $statement, $costCenter);
            if ($tax > 0) $this->appendConstraintLine($lines, $taxAccount->id, 'debtor', $tax, $statement);
            $this->appendConstraintLine($lines, $sourceId, 'creditor', $amount, $statement);
            $service = app(ObjService::class);
            $data = [
                'code' => $service->lastCode(), 'date' => $date, 'statement' => $statement,
                'account_id' => array_column($lines, 'account_id'), 'account_type' => array_column($lines, 'account_type'),
                'debtor_value' => array_column($lines, 'debtor_value'), 'creditor_value' => array_column($lines, 'creditor_value'),
                'statement_account' => array_column($lines, 'statement'), 'cost_center_required' => 'off',
                'cost_center_id' => array_column($lines, 'cost_center_id'), 'value' => $amount,
            ];
            $constraint = $service->storeConstraint($data, $year);
            $service->storeConstraintDetails($data['account_id'], $constraint->id, $data);
            $locked->update(array_merge(
                ['musaned_commission_constraint_id' => $constraint->id],
                MusanedCommissionPosting::invoiceColumnExists()
                    ? [MusanedCommissionPosting::INVOICE_COLUMN => MusanedCommissionPosting::SEPARATE]
                    : []
            ));
            $invoice->refresh();

            return $constraint;
        });
    }

    /**
     * يضيف سطري العمولة وضريبتها إلى قيد سند القبض، وينقص سطر مساند المدين بالمبلغ نفسه،
     * فتبقى قيمة القيد وتوازنه كما هما. يعود بقيد السند، أو null إن لم يوجد سند بقيد
     * (فيُسجَّل قيد مستقل كما في الوضع الافتراضي).
     */
    private function mergeCommissionIntoReceiptConstraint(
        Invoice $invoice,
        float $amount,
        float $tax,
        int $commissionAccountId,
        ?int $taxAccountId,
        ?int $costCenterId,
        string $statement
    ): ?Constraint {
        $receiptBond = $this->findInvoiceReceiptBond($invoice);
        if (!$receiptBond || !$receiptBond->constraint_id) {
            return null;
        }

        $constraint = Constraint::query()->lockForUpdate()->find((int) $receiptBond->constraint_id);
        if (!$constraint) {
            return null;
        }

        $musanedLine = ConstraintDetails::query()
            ->where('constraint_id', $constraint->id)
            ->where('account_id', (int) $receiptBond->debit_account_id)
            ->where('debtor_value', '>=', $amount)
            ->orderBy('id')
            ->first();
        if (!$musanedLine) {
            return null;
        }

        $musanedLine->update([
            'debtor_value' => $this->roundMoney((float) $musanedLine->debtor_value - $amount),
        ]);

        $append = function (int $accountId, float $value, ?int $lineCostCenterId) use ($musanedLine, $statement): void {
            if ($value <= 0) {
                return;
            }
            $line = $musanedLine->replicate();
            $line->account_id = $accountId;
            $line->debtor_value = $value;
            $line->creditor_value = 0;
            $line->cost_center_id = $lineCostCenterId;
            $line->statement = $statement;
            $line->save();
        };

        $append($commissionAccountId, $this->roundMoney($amount - $tax), $costCenterId);
        if ($tax > 0 && $taxAccountId) {
            $append($taxAccountId, $tax, null);
        }

        return $constraint->fresh();
    }

    private function findInvoiceReceiptBond(Invoice $invoice): ?Bond
    {
        return Bond::query()
            ->where('type', BondTypes::Qabd->value)
            ->whereNull('rental_contract_id')
            ->when($invoice->contract_id, function ($query) use ($invoice) {
                $query->where('recruitment_contract_id', $invoice->contract_id);
            })
            ->where('statement', 'regexp', 'الفاتورة #' . (int) $invoice->id . '([^0-9]|$)')
            ->latest('id')
            ->first();
    }

    public function index(Request $request)
    {
        $filters = $this->indexFilters($request);
        $query = MusanedTransfer::query();
        $this->applyRequestBranchFilter($query, $request, 'musaned_transfers');
        $this->applyIndexFilters($query, $filters);

        $summary = (clone $query)->toBase()->selectRaw(
            'COUNT(*) as transfers_count, COALESCE(SUM(contracts_count), 0) as items_count,'
            . ' COALESCE(SUM(customer_paid_total), 0) as customer_paid_total,'
            . ' COALESCE(SUM(transferred_total), 0) as transferred_total,'
            . ' COALESCE(SUM(commission_total), 0) as commission_total'
        )->first();

        [$sortColumn, $sortDirection] = [
            'oldest' => ['transfer_date', 'asc'],
            'amount_desc' => ['transferred_total', 'desc'],
            'amount_asc' => ['transferred_total', 'asc'],
            'items_desc' => ['contracts_count', 'desc'],
        ][$filters['sort']] ?? ['transfer_date', 'desc'];

        $branchesEnabled = \App\Support\SystemModuleManager::isEnabled('branches')
            && Schema::hasColumn('musaned_transfers', 'branch_id');

        $transfers = $query
            ->with(['createdBy', 'bankAccount', 'items.contract.user', 'items.ingazDelegation.user'])
            ->when($branchesEnabled, fn ($q) => $q->with('branch'))
            ->orderBy($sortColumn, $sortDirection)
            ->orderBy('id', $sortDirection)
            ->paginate($filters['per_page'])
            ->withQueryString();

        $bankAccounts = Account::query()
            ->whereIn('id', MusanedTransfer::query()->whereNotNull('bank_account_id')->distinct()->select('bank_account_id'))
            ->orderBy('name')
            ->get(['id', 'name']);
        $creators = \App\Models\auth\Admin::query()
            ->whereIn('id', MusanedTransfer::query()->whereNotNull('created_by_id')->distinct()->select('created_by_id'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.ingaz.musaned_transfers.index', compact('transfers', 'filters', 'summary', 'bankAccounts', 'creators', 'branchesEnabled'));
    }

    private function indexFilters(Request $request): array
    {
        $date = fn (string $key) => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query($key)) ? $request->query($key) : null;
        $amount = fn (string $key) => is_numeric($request->query($key)) ? (float) $request->query($key) : null;
        $perPage = (int) $request->query('per_page', 20);

        return [
            'q' => trim(mb_substr((string) $request->query('q', ''), 0, 100)),
            'date_from' => $date('date_from'),
            'date_to' => $date('date_to'),
            'bank_account_id' => (int) $request->query('bank_account_id') ?: null,
            'created_by_id' => (int) $request->query('created_by_id') ?: null,
            'kind' => in_array($request->query('kind'), ['single', 'bulk'], true) ? $request->query('kind') : null,
            'source' => in_array($request->query('source'), ['contract', 'delegation'], true) ? $request->query('source') : null,
            'amount_min' => $amount('amount_min'),
            'amount_max' => $amount('amount_max'),
            'sort' => in_array($request->query('sort'), ['oldest', 'amount_desc', 'amount_asc', 'items_desc'], true) ? $request->query('sort') : 'latest',
            'per_page' => in_array($perPage, [20, 50, 100], true) ? $perPage : 20,
        ];
    }

    private function applyIndexFilters($query, array $filters): void
    {
        $query
            ->when($filters['date_from'], fn ($q, $value) => $q->whereDate('transfer_date', '>=', $value))
            ->when($filters['date_to'], fn ($q, $value) => $q->whereDate('transfer_date', '<=', $value))
            ->when($filters['bank_account_id'], fn ($q, $value) => $q->where('bank_account_id', $value))
            ->when($filters['created_by_id'], fn ($q, $value) => $q->where('created_by_id', $value))
            ->when($filters['kind'] === 'single', fn ($q) => $q->where('contracts_count', '<=', 1))
            ->when($filters['kind'] === 'bulk', fn ($q) => $q->where('contracts_count', '>', 1))
            ->when($filters['source'] === 'contract', fn ($q) => $q->whereHas('items', fn ($item) => $item->whereNotNull('contract_id')))
            ->when($filters['source'] === 'delegation', fn ($q) => $q->whereHas('items', fn ($item) => $item->whereNotNull('ingaz_delegation_id')))
            ->when($filters['amount_min'] !== null, fn ($q) => $q->where('transferred_total', '>=', $filters['amount_min']))
            ->when($filters['amount_max'] !== null, fn ($q) => $q->where('transferred_total', '<=', $filters['amount_max']));

        if ($filters['q'] === '') {
            return;
        }

        $term = $filters['q'];
        $like = '%' . addcslashes($term, '%_\\') . '%';
        $customer = fn ($user) => $user->where('name', 'like', $like)->orWhere('phone', 'like', $like);

        $query->where(function ($q) use ($term, $like, $customer) {
            $q->where('reference_number', 'like', $like)->orWhere('notes', 'like', $like);
            if (ctype_digit($term)) {
                $q->orWhere('id', (int) $term);
            }
            $q->orWhereHas('items', function ($item) use ($term, $like, $customer) {
                $item->whereHas('contract.user', $customer)
                    ->orWhereHas('ingazDelegation', fn ($delegation) => $delegation
                        ->where('ingaz_number', 'like', $like)
                        ->orWhere('msaned_contract_number', 'like', $like)
                        ->orWhereHas('user', $customer));
                if (ctype_digit($term)) {
                    $item->orWhere('contract_id', (int) $term)->orWhere('invoice_id', (int) $term);
                }
            });
        });
    }

    public function create(Request $request)
    {
        $selectedContractIds = collect((array) $request->query('contract_ids', []))
            ->push($request->query('contract_id'))
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        $selectedIngazDelegationIds = collect((array) $request->query('ingaz_delegation_ids', []))
            ->push($request->query('ingaz_delegation_id'))
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        $oldItems = collect((array) session()->getOldInput('items', []));
        $selectedContractIds = collect($selectedContractIds)
            ->merge($oldItems->pluck('contract_id'))
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();
        $selectedIngazDelegationIds = collect($selectedIngazDelegationIds)
            ->merge($oldItems->pluck('ingaz_delegation_id'))
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values()
            ->all();

        $selectedInvoices = collect();
        if ($selectedContractIds || $selectedIngazDelegationIds) {
            $selectedInvoices = $this->getEligibleInvoicesQuery()
                ->where(function ($query) use ($selectedContractIds, $selectedIngazDelegationIds) {
                    if ($selectedContractIds) {
                        $query->orWhereIn('contract_id', $selectedContractIds);
                    }

                    if ($selectedIngazDelegationIds) {
                        $query->orWhereIn('ingaz_delegation_id', $selectedIngazDelegationIds);
                    }
                })
                ->get();
        }

        $selectedContractIds = $selectedInvoices->pluck('contract_id')->filter()->map(fn ($id) => (int) $id)->values()->all();
        $selectedIngazDelegationIds = $selectedInvoices->pluck('ingaz_delegation_id')->filter()->map(fn ($id) => (int) $id)->values()->all();

        $bankAccounts = $this->getBankAccounts();
        $expenseTaxRate = $this->resolveExpenseTaxRate();

        return view('admin.ingaz.musaned_transfers.create', [
            'hasEligibleInvoices' => $selectedInvoices->isNotEmpty() || $this->getEligibleInvoicesQuery()->exists(),
            'invoiceOptions' => $selectedInvoices->map(fn (Invoice $invoice) => $this->invoiceOption($invoice))->values(),
            'selectedContractIds' => $selectedContractIds,
            'selectedIngazDelegationIds' => $selectedIngazDelegationIds,
            'bankAccounts' => $bankAccounts,
            'expenseTaxRate' => $expenseTaxRate,
            'automaticEntriesEnabled' => AutomaticJournalEntries::enabled(),
        ]);
    }

    public function financialWorkspaceOptions(): array
    {
        return [
            'bankAccounts' => $this->getBankAccounts(),
            'expenseTaxRate' => $this->resolveExpenseTaxRate(),
            'automaticEntriesEnabled' => AutomaticJournalEntries::enabled(),
        ];
    }

    public function search(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        if ($term !== '' && mb_strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $query = $this->getEligibleInvoicesQuery();
        $like = '%' . addcslashes($term, '%_\\') . '%';

        if ($term !== '') {
            $query->where(function ($query) use ($term, $like) {
                if (ctype_digit($term)) {
                    $query->orWhere('contract_id', (int) $term)
                        ->orWhere('ingaz_delegation_id', (int) $term)
                        ->orWhere('id', (int) $term);
                }

                $query->orWhereHas('contract.user', fn ($relation) => $relation->where('name', 'like', $like))
                    ->orWhereHas('contract.info', fn ($relation) => $relation->where('msaned_number', 'like', $like))
                    ->orWhereHas('ingazDelegation.user', fn ($relation) => $relation->where('name', 'like', $like))
                    ->orWhereHas('ingazDelegation', fn ($relation) => $relation->where('msaned_contract_number', 'like', $like));
            });
        }

        return response()->json([
            'data' => $query->orderByDesc('id')->limit(50)->get()
                ->map(fn (Invoice $invoice) => $this->invoiceOption($invoice))
                ->values(),
        ]);
    }

    public function store(Request $request, ObjService $service, YearService $yearService)
    {
        $request->validate(['items' => 'required|array|min:1', 'items.*.invoice_id' => 'required|integer|min:1']);
        $transfer = $this->createTransfer($request, $service, $yearService);
        $returnContract = $request->input('return_to_contract');
        return (is_numeric($returnContract)
            ? redirect()->route('recruitment-contract-finance.show', (int) $returnContract)
            : redirect()->route('musaned-transfers.show', $transfer->id))
            ->with('success', AutomaticJournalEntries::enabled()
                ? 'تم تسجيل تحويل مساند وإنشاء القيود المحاسبية بنجاح.'
                : 'تم تسجيل تحويل مساند دون قيود محاسبية لأن القيود التلقائية غير مفعلة.');
    }

    /** Shared creation path for the form and reviewed imports. */
    public function createTransfer(Request $request, ObjService $service, YearService $yearService): MusanedTransfer
    {
        $automaticEntriesEnabled = AutomaticJournalEntries::enabled();
        $validated = $request->validate([
            'reference_number' => 'nullable|string|max:255',
            'transfer_date' => 'required|date',
            'bank_account_id' => ($automaticEntriesEnabled ? 'required' : 'nullable') . '|integer|exists:fn_accounts,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.contract_id' => 'nullable|required_without:items.*.ingaz_delegation_id|integer|exists:contracts,id',
            'items.*.ingaz_delegation_id' => 'nullable|required_without:items.*.contract_id|integer|exists:ingaz_delegations,id',
            'items.*.invoice_id' => 'nullable|integer|min:1',
            'items.*.transferred_amount' => 'required|numeric|min:0.01',
        ]);

        if ($automaticEntriesEnabled && !$yearService->checkDateRange($validated['transfer_date'])) {
            throw ValidationException::withMessages([
                'transfer_date' => 'لا يوجد سنة مالية نشطة للتاريخ المحدد.',
            ]);
        }

        $activeYear = $automaticEntriesEnabled ? $yearService->getActiveYear() : null;
        $bankAccount = !empty($validated['bank_account_id'])
            ? $this->findValidBankAccount((int) $validated['bank_account_id'])
            : null;

        if ($automaticEntriesEnabled && !$bankAccount) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'الحساب البنكي المحدد غير صالح للتحويل.',
            ]);
        }

        // وضع التحصيل يُقرأ مرة واحدة ويُثبَّت على التحويل، فالتحويلات السابقة تبقى
        // مقروءة بالطريقة التي أُنشئت بها حتى لو بُدّل الإعداد بعدها.
        $collectionMode = RecruitmentSupplierSettings::musanedCollectionMode();
        $usesIntermediate = $collectionMode === RecruitmentSupplierSettings::MUSANED_INTERMEDIATE_TWO_STEP;

        // الحساب الوسيط لا يُطلب إلا في الوضع الحالي؛ وضع سند القبض المباشر لا يستخدمه.
        $intermediateAccount = null;
        $commissionAccount = $automaticEntriesEnabled ? $this->resolveCommissionAccount() : null;
        $expenseTaxRate = $this->resolveExpenseTaxRate();
        $expenseTaxAccount = $automaticEntriesEnabled ? $this->resolveExpenseTaxAccount() : null;

        try {
            $transfer = DB::transaction(function () use (
                $validated,
                $service,
                $activeYear,
                $bankAccount,
                $intermediateAccount,
                $commissionAccount,
                $expenseTaxRate,
                $expenseTaxAccount,
                $automaticEntriesEnabled,
                $collectionMode,
                $usesIntermediate
            ) {
                $itemsInput = collect($validated['items'])
                    ->map(function ($item) {
                        $contractId = isset($item['contract_id']) && $item['contract_id'] !== ''
                            ? (int) $item['contract_id']
                            : null;
                        $delegationId = isset($item['ingaz_delegation_id']) && $item['ingaz_delegation_id'] !== ''
                            ? (int) $item['ingaz_delegation_id']
                            : null;

                        if ($contractId && $delegationId) {
                            throw ValidationException::withMessages([
                                'items' => 'لا يمكن اختيار عقد وتفويض في نفس بند تحويل مساند.',
                            ]);
                        }

                        if (!$contractId && !$delegationId) {
                            throw ValidationException::withMessages([
                                'items' => 'يجب اختيار عقد أو تفويض لكل بند تحويل مساند.',
                            ]);
                        }

                        return [
                            'contract_id' => $contractId,
                            'ingaz_delegation_id' => $delegationId,
                            'invoice_id' => isset($item['invoice_id']) ? (int) $item['invoice_id'] : null,
                            'source_key' => $contractId ? ('contract:' . $contractId) : ('delegation:' . $delegationId),
                            'transferred_amount' => $this->roundMoney((float) $item['transferred_amount']),
                        ];
                    })
                    ->values();

                if ($itemsInput->unique('source_key')->count() !== $itemsInput->count()) {
                    throw ValidationException::withMessages(['items' => 'لا يمكن تكرار العقد أو التفويض في التحويل نفسه.']);
                }

                $contractIds = $itemsInput->pluck('contract_id')->filter()->all();
                $delegationIds = $itemsInput->pluck('ingaz_delegation_id')->filter()->all();

                $invoices = $this->getEligibleInvoicesQuery()
                    ->where(function ($query) use ($contractIds, $delegationIds) {
                        if ($contractIds) {
                            $query->orWhereIn('contract_id', $contractIds);
                        }

                        if ($delegationIds) {
                            $query->orWhereIn('ingaz_delegation_id', $delegationIds);
                        }
                    })
                    ->lockForUpdate()
                    ->get()
                    ->groupBy(fn (Invoice $invoice) => $this->invoiceSourceKey($invoice));

                $invoices = $itemsInput->mapWithKeys(function ($row) use ($invoices) {
                    $candidates = $invoices->get($row['source_key'], collect());
                    // Internal imports may resolve a single eligible invoice without an ID.
                    // Browser submissions pin the displayed invoice and must never switch it.
                    if ($row['invoice_id']) {
                        $candidates = $candidates->where('id', $row['invoice_id']);
                    }
                    if ($candidates->count() !== 1) {
                        throw ValidationException::withMessages(['items' => 'الفاتورة المحددة لم تعد صالحة للتحويل أو يوجد أكثر من فاتورة. أعد تحميل الصفحة واختر الفاتورة الصحيحة.']);
                    }
                    return [$row['source_key'] => $candidates->first()];
                });

                if ($itemsInput->count() !== $invoices->count()) {
                    throw ValidationException::withMessages([
                        'items' => 'بعض البنود المحددة لا تحتوي فاتورة صالحة أو تم تسجيل تحويل مساند لها مسبقاً.',
                    ]);
                }

                $branchAttributes = [];
                $multiBranchTransfer = false;
                $employeeBranchId = null;
                if (\App\Support\SystemModuleManager::isEnabled('branches')
                    && Schema::hasColumn('musaned_transfers', 'branch_id')) {
                    $employeeBranchId = \App\Support\DataImportBranchContext::$active
                        ? \App\Support\DataImportBranchContext::$branchId
                        : Auth::guard('admin')->user()?->branch_id;
                    $sourceBranchIds = \App\Support\MusanedTransferBranch::branchIds($invoices);
                    $multiBranchTransfer = count($sourceBranchIds) > 1;
                    if ($multiBranchTransfer) {
                        $branchAttributes = ['branch_id' => null, 'is_multi_branch' => true];
                    } else {
                        $branchId = \App\Support\MusanedTransferBranch::resolve(
                            $invoices,
                            $employeeBranchId ? (int) $employeeBranchId : null
                        );
                        if (!$branchId) {
                            throw ValidationException::withMessages([
                                'items' => 'تعذر تحديد فرع تحويل مساند. حدد فرع العقد أو الفاتورة أو الموظف أولًا.',
                            ]);
                        }
                        $branchAttributes = ['branch_id' => $branchId];
                    }
                    if ($multiBranchTransfer && (!Schema::hasColumn('musaned_transfers', 'is_multi_branch')
                        || !Schema::hasColumn('fn_constraints', 'is_multi_branch'))) {
                        throw ValidationException::withMessages([
                            'items' => 'يلزم تحديث قاعدة البيانات لدعم التحويلات المجمعة من فروع مختلفة.',
                        ]);
                    }
                }

                $intermediateAccount = ($automaticEntriesEnabled && $usesIntermediate)
                    ? ($multiBranchTransfer
                        && \App\Support\MusanedIntermediateAccountSettings::enabled()
                        && \App\Support\MusanedIntermediateAccountSettings::mode() === \App\Support\MusanedIntermediateAccountSettings::MODE_PER_BRANCH
                            ? null
                            : $this->resolveIntermediateAccount($branchAttributes['branch_id'] ?? null))
                    : null;

                // Older transfer deletions may have left their item records
                // behind. They have no transfer parent and must not prevent
                // the invoice from being transferred again.
                MusanedTransferItem::query()
                    ->whereIn('invoice_id', $invoices->pluck('id'))
                    ->whereDoesntHave('transfer')
                    ->delete();

                $itemPayloads = [];
                $totals = [
                    'customer_paid_total' => 0.0,
                    'transferred_total' => 0.0,
                    'commission_total' => 0.0,
                    'expense_tax_total' => 0.0,
                    'commission_net_total' => 0.0,
                ];

                foreach ($itemsInput as $row) {
                    /** @var \App\Models\ingaz\Invoice $invoice */
                    $invoice = $invoices->get($row['source_key']);
                    $contract = $invoice->contract;
                    $delegation = $invoice->ingazDelegation;
                    $itemBranchId = null;
                    if (\App\Support\SystemModuleManager::isEnabled('branches')
                        && Schema::hasColumn('musaned_transfer_items', 'branch_id')) {
                        $itemBranchId = \App\Support\MusanedTransferBranch::branchForInvoice($invoice)
                            ?: ($branchAttributes['branch_id'] ?? ($employeeBranchId ? (int) $employeeBranchId : null));
                    }
                    if ($multiBranchTransfer && !$itemBranchId) {
                        throw ValidationException::withMessages(['items' => 'تعذر تحديد فرع إحدى فواتير التحويل المجمع.']);
                    }
                    $itemIntermediateAccount = ($automaticEntriesEnabled && $usesIntermediate)
                        ? ($multiBranchTransfer
                            ? $this->resolveIntermediateAccount($itemBranchId)
                            : $intermediateAccount)
                        : null;
                    $itemLabel = $contract ? ('العقد #' . $contract->id) : ('تفويض إنجاز #' . $delegation?->id);
                    $customerPaidAmount = $this->roundMoney((float) $invoice->total_due);
                    $transferredAmount = $row['transferred_amount'];

                    if ($transferredAmount > $customerPaidAmount) {
                        throw ValidationException::withMessages([
                            'items' => 'المبلغ المحول من مساند لا يمكن أن يكون أكبر من قيمة الفاتورة لـ ' . $itemLabel,
                        ]);
                    }

                    $commissionAmount = $this->roundMoney($customerPaidAmount - $transferredAmount);
                    if ($invoice->musaned_commission_amount !== null) {
                        $commissionAmount = (float) $invoice->musaned_commission_amount;
                        if (abs($this->roundMoney($customerPaidAmount - $commissionAmount) - $transferredAmount) > 0.01) {
                            throw ValidationException::withMessages(['items' => 'المبلغ المتوقع من مساند بعد العمولة لـ ' . $itemLabel . ' هو ' . number_format($customerPaidAmount - $commissionAmount, 2) . ' ريال.']);
                        }
                    }
                    $expenseTaxAmount = $invoice->musaned_commission_constraint_id
                        ? (float) $invoice->musaned_commission_tax
                        : ($commissionAmount > 0 ? $this->calculateExpenseTaxAmount($commissionAmount, $expenseTaxRate) : 0.0);
                    $commissionNetAmount = $this->roundMoney($commissionAmount - $expenseTaxAmount);
                    $settlementReceiptAccount = $usesIntermediate ? $itemIntermediateAccount : $bankAccount;
                    $sourceAccountId = $automaticEntriesEnabled
                        ? $this->resolveInvoiceMusanedSourceAccountId($invoice, (int) $settlementReceiptAccount->id)
                        : null;

                    // في سند القبض المباشر، لو انتهى حساب المصدر إلى البنك نفسه لصار القيد
                    // مديناً ودائناً على حساب واحد، أي صفراً بلا أثر. يُرفض بوضوح.
                    if ($sourceAccountId && !$usesIntermediate && (int) $sourceAccountId === (int) $bankAccount->id) {
                        throw ValidationException::withMessages([
                            'bank_account_id' => 'حساب مساند المسجَّل عليه التحصيل هو نفس البنك المحدد لـ ' . $itemLabel
                                . '. اختر بنكاً مختلفاً أو حدّد حساب مساند في إعدادات الحسابات.',
                        ]);
                    }
                    $costCenter = $automaticEntriesEnabled && $contract
                        ? $this->ensureRecruitmentContractCostCenter($contract)
                        : null;

                    $itemPayloads[] = [
                        ...(isset($itemBranchId) ? ['branch_id' => $itemBranchId] : []),
                        'contract_id' => $contract?->id,
                        'ingaz_delegation_id' => $delegation?->id,
                        'invoice_id' => $invoice->id,
                        'cost_center_id' => $costCenter?->id,
                        'source_account_id' => $sourceAccountId,
                        'customer_paid_amount' => $customerPaidAmount,
                        'transferred_amount' => $transferredAmount,
                        'commission_amount' => $commissionAmount,
                        'expense_tax_amount' => $expenseTaxAmount,
                        'commission_net_amount' => $commissionNetAmount,
                        'notes' => $contract?->info?->msaned_number ?: $delegation?->msaned_contract_number,
                    ];

                    $totals['customer_paid_total'] += $customerPaidAmount;
                    $totals['transferred_total'] += $transferredAmount;
                    $totals['commission_total'] += $commissionAmount;
                    $totals['expense_tax_total'] += $expenseTaxAmount;
                    $totals['commission_net_total'] += $commissionNetAmount;
                }

                foreach ($totals as $key => $value) {
                    $totals[$key] = $this->roundMoney($value);
                }

                $transfer = MusanedTransfer::query()->create([
                    ...$branchAttributes,
                    'reference_number' => ($validated['reference_number'] ?? null) ?: null,
                    'transfer_date' => $validated['transfer_date'],
                    'notes' => ($validated['notes'] ?? null) ?: null,
                    'contracts_count' => count($itemPayloads),
                    'customer_paid_total' => $totals['customer_paid_total'],
                    'transferred_total' => $totals['transferred_total'],
                    'commission_total' => $totals['commission_total'],
                    'expense_tax_total' => $totals['expense_tax_total'],
                    'commission_net_total' => $totals['commission_net_total'],
                    'expense_tax_rate' => $expenseTaxRate,
                    'bank_account_id' => $bankAccount?->id,
                    'intermediate_account_id' => $intermediateAccount?->id,
                    ...(Schema::hasColumn('musaned_transfers', 'is_multi_branch')
                        ? ['is_multi_branch' => $multiBranchTransfer]
                        : []),
                    'commission_account_id' => $commissionAccount?->id,
                    'expense_tax_account_id' => $expenseTaxAccount?->id,
                    'created_by_id' => Auth::guard('admin')->id() ?: Auth::id(),
                    ...(Schema::hasColumn('musaned_transfers', RecruitmentSupplierSettings::MUSANED_TRANSFER_COLUMN)
                        ? [RecruitmentSupplierSettings::MUSANED_TRANSFER_COLUMN => $collectionMode]
                        : []),
                ]);

                $transfer->items()->createMany($itemPayloads);
                $transfer->load(['items.contract.user', 'items.ingazDelegation.user', 'items.invoice.contract.info', 'items.invoice.ingazDelegation']);

                $settlementConstraint = null;
                $bankTransferConstraint = null;

                if ($automaticEntriesEnabled) {
                    // الوضع الحالي: صافي التحصيل على الحساب الوسيط ثم قيد تحويل بنكي.
                    // وضع سند القبض المباشر: مدين البنك المحصل في قيد واحد، بلا وسيط.
                    $settlementConstraint = $this->storeSettlementConstraint(
                        $transfer,
                        $service,
                        $activeYear,
                        (int) $bankAccount->id,
                        $usesIntermediate,
                        (int) $commissionAccount->id,
                        $expenseTaxAccount?->id ? (int) $expenseTaxAccount->id : null
                    );

                    if ($usesIntermediate) {
                        $bankTransferConstraint = $this->storeBankTransferConstraint(
                            $transfer,
                            $service,
                            $activeYear,
                            (int) $bankAccount->id
                        );
                    }
                }

                $transfer->update([
                    'settlement_constraint_id' => $settlementConstraint?->id,
                    'bank_transfer_constraint_id' => $bankTransferConstraint?->id,
                ]);

                return $transfer->fresh([
                    'items.contract.user',
                    'items.ingazDelegation.user',
                    'items.invoice.contract.info',
                    'items.invoice.ingazDelegation',
                    'bankAccount',
                    'intermediateAccount',
                    'commissionAccount',
                    'expenseTaxAccount',
                    'settlementConstraint',
                    'bankTransferConstraint',
                ]);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'items' => $exception->getMessage(),
            ]);
        }

        foreach ($transfer->items->whereNotNull('ingaz_delegation_id') as $item) {
            if ($item->ingazDelegation) {
                IngazDelegationTimeline::record($item->ingazDelegation, 'musaned_transfer_created', [
                    'summary' => 'تم ربط التفويض بتحويل مساند.',
                    'transfer_id' => $transfer->id,
                    'reference_number' => $transfer->reference_number,
                    'transferred_amount' => (float) $item->transferred_amount,
                    'transfer_date' => optional($transfer->transfer_date)->format('Y-m-d'),
                ]);
            }
        }

        return $transfer;
    }

    public function show(int $id)
    {
        $transfer = MusanedTransfer::query()
            ->with([
                'items.contract.user',
                'items.ingazDelegation.user',
                'items.invoice.contract.info',
                'items.invoice.ingazDelegation',
                'items.costCenter',
                'items.sourceAccount',
                'bankAccount',
                'intermediateAccount',
                'commissionAccount',
                'expenseTaxAccount',
                'settlementConstraint',
                'bankTransferConstraint',
                'createdBy',
            ])
            ->findOrFail($id);
        $this->assertCanAccessBranchModel($transfer);

        return view('admin.ingaz.musaned_transfers.show', compact('transfer'));
    }

    public function destroy(int $id, ObjService $service)
    {
        $transfer = MusanedTransfer::query()
            ->with('items.ingazDelegation')
            ->findOrFail($id);
        $this->assertCanAccessBranchModel($transfer);
        $delegationItems = $transfer->items
            ->whereNotNull('ingaz_delegation_id')
            ->filter(fn (MusanedTransferItem $item) => $item->ingazDelegation)
            ->values();

        // استرداد العقد يُبنى على بند التحويل وقيده؛ حذف التحويل يترك الاسترداد بلا أصل.
        if ($this->transferHasContractRefunds($transfer)) {
            return back()->withErrors([
                'delete' => 'لا يمكن حذف تحويل مساند بُني عليه استرداد عقد. اعكس الاسترداد أولاً.',
            ]);
        }

        try {
            $deletedConstraintsCount = DB::transaction(function () use ($transfer, $service) {
                $lockedTransfer = MusanedTransfer::query()
                    ->lockForUpdate()
                    ->findOrFail($transfer->id);

                $constraintIds = collect([
                    $lockedTransfer->settlement_constraint_id,
                    $lockedTransfer->bank_transfer_constraint_id,
                ])->filter()->unique()->values();

                $constraints = Constraint::query()
                    ->whereIn('id', $constraintIds)
                    ->lockForUpdate()
                    ->get();

                foreach ($constraints as $constraint) {
                    // التحويل يملك قيديه، لذا يتجاوز حارس القيود الآلية (بعد فحص الاستردادات أعلاه).
                    $service->deleteConstraint((int) $constraint->id, true);
                }

                // Explicitly remove items as a safeguard for installations
                // where the database foreign-key cascade was not applied.
                $lockedTransfer->items()->delete();
                $lockedTransfer->delete();

                return $constraints->count();
            });
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'delete' => 'تعذر حذف تحويل مساند والقيود المرتبطة به. لم يتم حذف أي بيانات.',
            ]);
        }

        foreach ($delegationItems as $item) {
            IngazDelegationTimeline::record($item->ingazDelegation, 'musaned_transfer_deleted', [
                'summary' => 'تم حذف تحويل مساند المرتبط بالتفويض.',
                'transfer_id' => $transfer->id,
                'reference_number' => $transfer->reference_number,
                'transferred_amount' => (float) $item->transferred_amount,
                'transfer_date' => optional($transfer->transfer_date)->format('Y-m-d'),
            ]);
        }

        return redirect()
            ->route('musaned-transfers.index')
            ->with('success', 'تم حذف تحويل مساند وحذف ' . $deletedConstraintsCount . ' من القيود المحاسبية المرتبطة به بنجاح.');
    }

    private function transferHasContractRefunds(MusanedTransfer $transfer): bool
    {
        if (! Schema::hasTable('recruitment_contract_refunds')
            || ! Schema::hasColumn('recruitment_contract_refunds', 'musaned_transfer_item_id')) {
            return false;
        }

        $itemIds = $transfer->items->pluck('id')->filter()->values();
        if ($itemIds->isEmpty()) {
            return false;
        }

        return DB::table('recruitment_contract_refunds')
            ->whereIn('musaned_transfer_item_id', $itemIds)
            ->exists();
    }

    /** Read-only checks used by import review; creation repeats all checks under locks. */
    public function validateImportAccounting(Invoice $invoice, int $bankAccountId): void
    {
        if (!AutomaticJournalEntries::enabled()) return;
        $usesIntermediate = RecruitmentSupplierSettings::musanedCollectionMode() === RecruitmentSupplierSettings::MUSANED_INTERMEDIATE_TWO_STEP;
        $receiptAccount = $usesIntermediate
            ? $this->resolveIntermediateAccount($invoice->contract?->branch_id ?: $invoice->ingazDelegation?->branch_id)
            : $this->findValidBankAccount($bankAccountId);
        if (!$receiptAccount) throw new \RuntimeException('الحساب البنكي المحدد غير صالح.');
        $this->resolveCommissionAccount();
        $this->resolveExpenseTaxAccount();
        $sourceId = $this->resolveInvoiceMusanedSourceAccountId($invoice, (int) $receiptAccount->id);
        if (!$usesIntermediate && $sourceId === $bankAccountId) {
            throw new \RuntimeException('حساب مساند المسجل عليه التحصيل هو نفس البنك المحدد؛ اختر بنكًا مختلفًا أو حدد حساب مساند في الإعدادات.');
        }
    }

    public function getEligibleInvoicesQuery()
    {
        $query = Invoice::query()
            ->with([
                'contract.user',
                'contract.info',
                'contract.maid.nationality',
                'contract.requiredMaidNationality',
                'ingazDelegation.user',
                'ingazDelegation.country',
                'ingazDelegation.nationality',
                'musanedTransferItem',
            ])
            // A transfer item only blocks its invoice while its parent
            // transfer still exists. This also releases legacy orphan items.
            ->whereDoesntHave('musanedTransferItem.transfer')
            ->blockingReplacement()
            ->where(function ($query) {
                $query->whereHas('contract', fn ($contract) => $contract->forCompanyRecruitment(false))
                    ->orWhereHas('ingazDelegation');
            });

        $this->applyRequestBranchFilter($query, request(), 'invoices');

        return $query;
    }

    private function getBankAccounts()
    {
        return Account::query()
            ->whereDoesntHave('children')
            ->whereIn('type', [
                AccountTypesEnum::Banks->value,
                AccountTypesEnum::Bank->value,
                AccountTypesEnum::Boxs->value,
                AccountTypesEnum::Box->value,
                AccountTypesEnum::BankName->value,
            ])
            ->orderBy('code')
            ->get();
    }

    private function resolveIntermediateAccount(?int $branchId = null): Account
    {
        $branchAccount = \App\Support\MusanedIntermediateAccountSettings::accountForBranch($branchId);
        $accountId = $branchAccount?->id;
        if (!$accountId && (!\App\Support\MusanedIntermediateAccountSettings::enabled()
            || \App\Support\MusanedIntermediateAccountSettings::mode() === \App\Support\MusanedIntermediateAccountSettings::MODE_ALL)) {
            $accountId = ConfirmSetting::query()
                ->where('title', ConfirmSettingTypes::MusanedIntermediateAccount->value)
                ->latest('updated_at')
                ->value('value');
        }

        if (!$accountId) {
            throw new \RuntimeException('لا يوجد حساب وسيط مساند محدد من إعدادات النظام المحاسبي.');
        }

        $account = $this->findValidMusanedAccount((int) $accountId);
        if (!$account) {
            throw new \RuntimeException('الحساب الوسيط لمساند المحدد غير صالح.');
        }

        return $account;
    }

    private function resolveCommissionAccount(): Account
    {
        $accountId = ConfirmSetting::query()
            ->where('title', ConfirmSettingTypes::MusanedCommissionAccount->value)
            ->latest('updated_at')
            ->value('value');

        if (!$accountId) {
            $accountId = Account::query()
                ->whereDoesntHave('children')
                ->where('status', 'debtor')
                ->where(function ($query) {
                    $query->where('name', 'like', '%عمولة%مساند%')
                        ->orWhere('name', 'like', '%عمولات%مساند%');
                })
                ->orderBy('code')
                ->value('id');
        }

        if (!$accountId) {
            throw new \RuntimeException('لا يوجد حساب للتكاليف البنكية لمساند محدد من إعدادات النظام المحاسبي.');
        }

        $account = $this->findValidDebtorLeafAccount((int) $accountId);
        if (!$account) {
            throw new \RuntimeException('حساب التكاليف البنكية لمساند المحدد غير صالح.');
        }

        return $account;
    }

    private function resolveExpenseTaxAccount(): ?Account
    {
        return app(\Nami\Finance\Services\TaxAccountResolver::class)->resolve('expense_tax_account');
    }

    private function resolveExpenseTaxRate(): float
    {
        $taxSettingId = ConfirmSetting::query()
            ->where('title', ConfirmSettingTypes::TaxRateExpenditure->value)
            ->latest('updated_at')
            ->value('value');

        if ($taxSettingId) {
            $taxRate = (float) TaxSetting::query()->whereKey($taxSettingId)->value('value');
            if ($taxRate > 0) {
                return $taxRate;
            }
        }

        return 15.0;
    }

    private function resolveInvoiceMusanedSourceAccountId(Invoice $invoice, int $fallbackAccountId): int
    {
        $receiptBond = $this->findInvoiceReceiptBond($invoice);

        if ($receiptBond && $this->findValidMusanedAccount((int) $receiptBond->debit_account_id)) {
            return (int) $receiptBond->debit_account_id;
        }

        $settingAccountId = ConfirmSetting::query()
            ->where('title', ConfirmSettingTypes::MusanedAccount->value)
            ->latest('updated_at')
            ->value('value');

        if ($settingAccountId && $this->findValidMusanedAccount((int) $settingAccountId)) {
            return (int) $settingAccountId;
        }

        return $fallbackAccountId;
    }

    private function invoiceSourceKey(Invoice $invoice): string
    {
        return $invoice->contract_id
            ? ('contract:' . $invoice->contract_id)
            : ('delegation:' . $invoice->ingaz_delegation_id);
    }

    private function invoiceOption(Invoice $invoice): array
    {
        $contract = $invoice->contract;
        $delegation = $invoice->ingazDelegation;
        $clientName = $contract?->user?->name ?: ($delegation?->user?->name ?: 'غير محدد');
        $msanedNumber = $contract?->info?->msaned_number ?: ($delegation?->msaned_contract_number ?: '-');
        $sourceType = $contract ? 'contract' : 'delegation';
        $sourceId = $contract?->id ?: $delegation?->id;
        $sourceLabel = $contract ? ('عقد #' . $contract->id) : ('تفويض #' . $delegation?->id);

        return [
            'contract_id' => $contract?->id,
            'ingaz_delegation_id' => $delegation?->id,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_key' => $sourceType . ':' . $sourceId,
            'invoice_id' => $invoice->id,
            'label' => $sourceLabel . ' - ' . $clientName . ' - مساند ' . $msanedNumber,
            'client_name' => $clientName,
            'msaned_number' => $msanedNumber,
            'invoice_total' => (float) $invoice->total_due,
            'expected_transfer' => $invoice->musaned_commission_amount !== null
                ? $this->roundMoney((float) $invoice->total_due - (float) $invoice->musaned_commission_amount) : null,
        ];
    }

    private function storeSettlementConstraint(
        MusanedTransfer $transfer,
        ObjService $service,
        $activeYear,
        int $bankAccountId,
        bool $usesIntermediate,
        int $commissionAccountId,
        ?int $expenseTaxAccountId
    ): ?Constraint {
        $lines = [];

        foreach ($transfer->items as $item) {
            $costCenterId = $item->cost_center_id;
            $musanedClearingCostCenterId = null;
            $branchId = $transfer->is_multi_branch ? (int) $item->branch_id : ($transfer->branch_id ? (int) $transfer->branch_id : null);
            $receiptAccountId = $usesIntermediate
                ? ($transfer->is_multi_branch
                    ? (int) $this->resolveIntermediateAccount($branchId)->id
                    : (int) $transfer->intermediate_account_id)
                : $bankAccountId;

            if ((float) $item->transferred_amount > 0) {
                $statement = $this->buildSettlementItemStatement($transfer, $item, 'صافي المبلغ المحول من مساند');
                $this->appendConstraintLine($lines, $receiptAccountId, 'debtor', (float) $item->transferred_amount, $statement, $musanedClearingCostCenterId, $branchId);
                $this->appendConstraintLine($lines, (int) $item->source_account_id, 'creditor', (float) $item->transferred_amount, $statement, $musanedClearingCostCenterId, $branchId);
            }

            $commissionAlreadyRecorded = (bool) $item->invoice?->musaned_commission_constraint_id;
            if (!$commissionAlreadyRecorded && (float) $item->commission_amount > 0) {
                $statement = $this->buildSettlementItemStatement($transfer, $item, 'التكاليف البنكية لمساند على العقد');
                $this->appendConstraintLine($lines, $commissionAccountId, 'debtor', (float) $item->commission_amount, $statement, $costCenterId, $branchId);
                $this->appendConstraintLine($lines, (int) $item->source_account_id, 'creditor', (float) $item->commission_amount, $statement, null, $branchId);
            }

            if (!$commissionAlreadyRecorded && $expenseTaxAccountId && (float) $item->expense_tax_amount > 0) {
                $statement = $this->buildSettlementItemStatement($transfer, $item, 'فصل ضريبة المصروفات من التكاليف البنكية لمساند');
                $this->appendConstraintLine($lines, $expenseTaxAccountId, 'debtor', (float) $item->expense_tax_amount, $statement, null, $branchId);
                $this->appendConstraintLine($lines, $commissionAccountId, 'creditor', (float) $item->expense_tax_amount, $statement, $costCenterId, $branchId);
            }
        }

        if (empty($lines)) {
            return null;
        }

        $data = [
            ...($transfer->is_multi_branch ? ['is_multi_branch' => true] : (isset($transfer->branch_id) ? ['branch_id' => (int) $transfer->branch_id] : [])),
            'code' => $service->lastCode(),
            'date' => $transfer->transfer_date->format('Y-m-d'),
            'statement' => $this->buildSettlementStatement($transfer),
            'account_id' => array_column($lines, 'account_id'),
            'account_type' => array_column($lines, 'account_type'),
            'debtor_value' => array_column($lines, 'debtor_value'),
            'creditor_value' => array_column($lines, 'creditor_value'),
            'statement_account' => array_column($lines, 'statement'),
            'cost_center_required' => 'off',
            'cost_center_id' => array_column($lines, 'cost_center_id'),
            'detail_branch_id' => array_column($lines, 'branch_id'),
            'value' => $this->roundMoney(array_sum(array_column($lines, 'debtor_value'))),
        ];

        $constraint = $service->storeConstraint($data, $activeYear);
        $service->storeConstraintDetails($data['account_id'], $constraint->id, $data);

        return $constraint;
    }

    private function storeBankTransferConstraint(
        MusanedTransfer $transfer,
        ObjService $service,
        $activeYear,
        int $bankAccountId
    ): ?Constraint {
        $amount = $this->roundMoney((float) $transfer->transferred_total);
        if ($amount <= 0) {
            return null;
        }

        $statement = $this->buildBankTransferStatement($transfer);
        $lines = [];
        $branchTotals = $transfer->items->groupBy(fn ($item) => $transfer->is_multi_branch
            ? (int) $item->branch_id
            : (int) ($transfer->branch_id ?: 0));
        foreach ($branchTotals as $branchKey => $items) {
            $branchId = (int) $branchKey ?: null;
            $branchAmount = $this->roundMoney((float) $items->sum('transferred_amount'));
            $intermediateAccountId = $transfer->intermediate_account_id
                ? (int) $transfer->intermediate_account_id
                : (int) $this->resolveIntermediateAccount($branchId)->id;
            $this->appendConstraintLine($lines, $bankAccountId, 'debtor', $branchAmount, $statement, null, $branchId);
            $this->appendConstraintLine($lines, $intermediateAccountId, 'creditor', $branchAmount, $statement, null, $branchId);
        }

        $data = [
            ...($transfer->is_multi_branch ? ['is_multi_branch' => true] : (isset($transfer->branch_id) ? ['branch_id' => (int) $transfer->branch_id] : [])),
            'code' => $service->lastCode(),
            'date' => $transfer->transfer_date->format('Y-m-d'),
            'statement' => $statement,
            'account_id' => array_column($lines, 'account_id'),
            'account_type' => array_column($lines, 'account_type'),
            'debtor_value' => array_column($lines, 'debtor_value'),
            'creditor_value' => array_column($lines, 'creditor_value'),
            'statement_account' => array_column($lines, 'statement'),
            'cost_center_required' => 'off',
            'cost_center_id' => array_column($lines, 'cost_center_id'),
            'detail_branch_id' => array_column($lines, 'branch_id'),
            'value' => $amount,
        ];

        $constraint = $service->storeConstraint($data, $activeYear);
        $service->storeConstraintDetails($data['account_id'], $constraint->id, $data);

        return $constraint;
    }

    private function appendConstraintLine(
        array &$lines,
        int $accountId,
        string $accountType,
        float $amount,
        string $statement,
        ?int $costCenterId = null,
        ?int $branchId = null
    ): void {
        $amount = $this->roundMoney($amount);
        if ($amount <= 0) {
            return;
        }

        $lines[] = [
            'account_id' => $accountId,
            'account_type' => $accountType,
            'debtor_value' => $accountType === 'debtor' ? $amount : 0,
            'creditor_value' => $accountType === 'creditor' ? $amount : 0,
            'statement' => $statement,
            'cost_center_id' => $costCenterId,
            'branch_id' => $branchId,
        ];
    }

    private function ensureRecruitmentContractCostCenter(Contract $contract): CostCenter
    {
        $contract->loadMissing(['maid.nationality', 'requiredMaidNationality']);
        $nationality = $contract->maid?->nationality ?? $contract->requiredMaidNationality;
        $nationalityName = trim((string) ($nationality?->sub_title ?? $nationality?->title ?? ''));

        return RecruitmentCostCenters::forContract((int) $contract->id, $nationalityName);
    }

    private function findValidMusanedAccount(int $accountId): ?Account
    {
        return Account::query()
            ->whereKey($accountId)
            ->musanedAccountCandidate()
            ->first();
    }

    private function findValidDebtorLeafAccount(int $accountId): ?Account
    {
        return Account::query()
            ->whereKey($accountId)
            ->whereDoesntHave('children')
            ->where('status', 'debtor')
            ->first();
    }

    public function findValidBankAccount(int $accountId): ?Account
    {
        return Account::query()
            ->whereKey($accountId)
            ->whereDoesntHave('children')
            ->whereIn('type', [
                AccountTypesEnum::Banks->value,
                AccountTypesEnum::Bank->value,
                AccountTypesEnum::Boxs->value,
                AccountTypesEnum::Box->value,
                AccountTypesEnum::BankName->value,
            ])
            ->first();
    }

    private function buildSettlementStatement(MusanedTransfer $transfer): string
    {
        $reference = $transfer->reference_number ?: 'بدون مرجع';
        return 'قيد تسوية حوالة مساند ' . $reference . ' بتاريخ ' . $transfer->transfer_date->format('Y-m-d');
    }

    private function buildSettlementItemStatement(MusanedTransfer $transfer, MusanedTransferItem $item, ?string $operation = null): string
    {
        $reference = $transfer->reference_number ?: 'بدون مرجع';
        $msanedNumber = $item->invoice?->contract?->info?->msaned_number
            ?: ($item->invoice?->ingazDelegation?->msaned_contract_number ?: '-');
        $operationText = $operation ? ' / العملية: ' . $operation : '';
        $sourceText = $item->contract_id
            ? ('العقد #' . $item->contract_id)
            : ('تفويض إنجاز #' . $item->ingaz_delegation_id);

        return 'تسوية تحويل مساند' . $operationText . ' / ' . $sourceText . ' / مساند ' . $msanedNumber . ' / مرجع ' . $reference;
    }

    private function buildBankTransferStatement(MusanedTransfer $transfer): string
    {
        $reference = $transfer->reference_number ?: 'بدون مرجع';
        return 'تحويل بنكي لحوالة مساند ' . $reference . ' من الحساب الوسيط إلى البنك';
    }

    private function calculateExpenseTaxAmount(float $commissionAmount, float $taxRate): float
    {
        if ($commissionAmount <= 0 || $taxRate <= 0) {
            return 0.0;
        }

        $divider = 1 + ($taxRate / 100);
        return $this->roundMoney($commissionAmount - ($commissionAmount / $divider));
    }

    private function roundMoney(float $amount): float
    {
        return round($amount, 2);
    }
}
