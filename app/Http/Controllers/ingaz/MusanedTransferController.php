<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\Contract;
use App\Models\ingaz\Invoice;
use App\Models\MusanedTransfer;
use App\Models\MusanedTransferItem;
use App\Support\AutomaticJournalEntries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Enums\ConfirmSettingTypes;
use Nami\Finance\Enums\CostCenterTypeEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\CostCenter;
use Nami\Finance\Models\TaxSetting;
use Nami\Finance\Services\ConstraintService as ObjService;
use Nami\Finance\Services\YearService;

class MusanedTransferController extends Controller
{
    public function index(Request $request)
    {
        $query = MusanedTransfer::query()
            ->with(['createdBy', 'bankAccount', 'items.contract.user', 'items.ingazDelegation.user']);
        $this->applyRequestBranchFilter($query, $request, 'musaned_transfers');

        $transfers = $query
            ->latest('transfer_date')
            ->latest('id')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.ingaz.musaned_transfers.index', compact('transfers'));
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
        $automaticEntriesEnabled = AutomaticJournalEntries::enabled();
        $validated = $request->validate([
            'reference_number' => 'nullable|string|max:255',
            'transfer_date' => 'required|date',
            'bank_account_id' => ($automaticEntriesEnabled ? 'required' : 'nullable') . '|integer|exists:fn_accounts,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.contract_id' => 'nullable|required_without:items.*.ingaz_delegation_id|integer|exists:contracts,id',
            'items.*.ingaz_delegation_id' => 'nullable|required_without:items.*.contract_id|integer|exists:ingaz_delegations,id',
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

        $intermediateAccount = $automaticEntriesEnabled ? $this->resolveIntermediateAccount() : null;
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
                $automaticEntriesEnabled
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
                            'source_key' => $contractId ? ('contract:' . $contractId) : ('delegation:' . $delegationId),
                            'transferred_amount' => $this->roundMoney((float) $item['transferred_amount']),
                        ];
                    })
                    ->unique('source_key')
                    ->values();

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
                    ->keyBy(fn (Invoice $invoice) => $this->invoiceSourceKey($invoice));

                if ($itemsInput->count() !== $invoices->count()) {
                    throw ValidationException::withMessages([
                        'items' => 'بعض البنود المحددة لا تحتوي فاتورة صالحة أو تم تسجيل تحويل مساند لها مسبقاً.',
                    ]);
                }

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
                    $itemLabel = $contract ? ('العقد #' . $contract->id) : ('تفويض إنجاز #' . $delegation?->id);
                    $customerPaidAmount = $this->roundMoney((float) $invoice->total_due);
                    $transferredAmount = $row['transferred_amount'];

                    if ($transferredAmount > $customerPaidAmount) {
                        throw ValidationException::withMessages([
                            'items' => 'المبلغ المحول من مساند لا يمكن أن يكون أكبر من قيمة الفاتورة لـ ' . $itemLabel,
                        ]);
                    }

                    $commissionAmount = $this->roundMoney($customerPaidAmount - $transferredAmount);
                    $expenseTaxAmount = $commissionAmount > 0
                        ? $this->calculateExpenseTaxAmount($commissionAmount, $expenseTaxRate)
                        : 0.0;
                    $commissionNetAmount = $this->roundMoney($commissionAmount - $expenseTaxAmount);
                    $sourceAccountId = $automaticEntriesEnabled
                        ? $this->resolveInvoiceMusanedSourceAccountId($invoice, (int) $intermediateAccount->id)
                        : null;
                    $costCenter = $automaticEntriesEnabled && $contract
                        ? $this->ensureRecruitmentContractCostCenter($contract)
                        : null;

                    $itemPayloads[] = [
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
                    'reference_number' => $validated['reference_number'] ?: null,
                    'transfer_date' => $validated['transfer_date'],
                    'notes' => $validated['notes'] ?: null,
                    'contracts_count' => count($itemPayloads),
                    'customer_paid_total' => $totals['customer_paid_total'],
                    'transferred_total' => $totals['transferred_total'],
                    'commission_total' => $totals['commission_total'],
                    'expense_tax_total' => $totals['expense_tax_total'],
                    'commission_net_total' => $totals['commission_net_total'],
                    'expense_tax_rate' => $expenseTaxRate,
                    'bank_account_id' => $bankAccount?->id,
                    'intermediate_account_id' => $intermediateAccount?->id,
                    'commission_account_id' => $commissionAccount?->id,
                    'expense_tax_account_id' => $expenseTaxAccount?->id,
                    'created_by_id' => Auth::guard('admin')->id() ?: Auth::id(),
                ]);

                $transfer->items()->createMany($itemPayloads);
                $transfer->load(['items.contract.user', 'items.ingazDelegation.user', 'items.invoice.contract.info', 'items.invoice.ingazDelegation']);

                $settlementConstraint = null;
                $bankTransferConstraint = null;

                if ($automaticEntriesEnabled) {
                    $settlementConstraint = $this->storeSettlementConstraint(
                        $transfer,
                        $service,
                        $activeYear,
                        (int) $intermediateAccount->id,
                        (int) $commissionAccount->id,
                        $expenseTaxAccount?->id ? (int) $expenseTaxAccount->id : null
                    );

                    $bankTransferConstraint = $this->storeBankTransferConstraint(
                        $transfer,
                        $service,
                        $activeYear,
                        (int) $bankAccount->id,
                        (int) $intermediateAccount->id
                    );
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

        return redirect()
            ->route('musaned-transfers.show', $transfer->id)
            ->with(
                'success',
                $automaticEntriesEnabled
                    ? 'تم تسجيل تحويل مساند وإنشاء القيود المحاسبية بنجاح.'
                    : 'تم تسجيل تحويل مساند دون قيود محاسبية لأن القيود التلقائية غير مفعلة.'
            );
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

    private function getEligibleInvoicesQuery()
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
            ->whereDoesntHave('musanedTransferItem')
            ->where(function ($query) {
                $query->whereHas('contract')
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

    private function resolveIntermediateAccount(): Account
    {
        $accountId = ConfirmSetting::query()
            ->where('title', ConfirmSettingTypes::MusanedIntermediateAccount->value)
            ->latest('updated_at')
            ->value('value');

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
            throw new \RuntimeException('لا يوجد حساب عمولة مساند محدد من إعدادات النظام المحاسبي.');
        }

        $account = $this->findValidDebtorLeafAccount((int) $accountId);
        if (!$account) {
            throw new \RuntimeException('حساب عمولة مساند المحدد غير صالح.');
        }

        return $account;
    }

    private function resolveExpenseTaxAccount(): ?Account
    {
        $directAccountId = ConfirmSetting::query()
            ->where('title', ConfirmSettingTypes::ExpenseTaxAccount->value)
            ->latest('updated_at')
            ->value('value');

        if ($directAccountId) {
            $directAccount = $this->findValidDebtorLeafAccount((int) $directAccountId);
            if ($directAccount) {
                return $directAccount;
            }
        }

        $taxSettingId = ConfirmSetting::query()
            ->where('title', ConfirmSettingTypes::TaxRateExpenditure->value)
            ->latest('updated_at')
            ->value('value');

        if ($taxSettingId) {
            $taxAccountId = TaxSetting::query()->whereKey($taxSettingId)->value('account_id');
            if ($taxAccountId) {
                $taxAccount = $this->findValidDebtorLeafAccount((int) $taxAccountId);
                if ($taxAccount) {
                    return $taxAccount;
                }
            }
        }

        $fallbackAccountId = Account::query()
            ->whereDoesntHave('children')
            ->where('status', 'debtor')
            ->where(function ($query) {
                $query->where('name', 'like', '%ضريبة%مصروف%')
                    ->orWhere('name', 'like', '%الضريبة على المصروفات%');
            })
            ->orderBy('code')
            ->value('id');

        return $fallbackAccountId
            ? Account::query()->find((int) $fallbackAccountId)
            : null;
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
        $receiptBond = Bond::query()
            ->where('type', BondTypes::Qabd->value)
            ->where('statement', 'like', '%الفاتورة #' . $invoice->id . '%')
            ->latest('id')
            ->first();

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
        ];
    }

    private function storeSettlementConstraint(
        MusanedTransfer $transfer,
        ObjService $service,
        $activeYear,
        int $intermediateAccountId,
        int $commissionAccountId,
        ?int $expenseTaxAccountId
    ): ?Constraint {
        $lines = [];

        foreach ($transfer->items as $item) {
            $costCenterId = $item->cost_center_id;
            $musanedClearingCostCenterId = null;

            if ((float) $item->transferred_amount > 0) {
                $statement = $this->buildSettlementItemStatement($transfer, $item, 'صافي المبلغ المحول من مساند');
                $this->appendConstraintLine($lines, $intermediateAccountId, 'debtor', (float) $item->transferred_amount, $statement, $musanedClearingCostCenterId);
                $this->appendConstraintLine($lines, (int) $item->source_account_id, 'creditor', (float) $item->transferred_amount, $statement, $musanedClearingCostCenterId);
            }

            if ((float) $item->commission_amount > 0) {
                $statement = $this->buildSettlementItemStatement($transfer, $item, 'عمولة مساند على العقد');
                $this->appendConstraintLine($lines, $commissionAccountId, 'debtor', (float) $item->commission_amount, $statement, $costCenterId);
                $this->appendConstraintLine($lines, (int) $item->source_account_id, 'creditor', (float) $item->commission_amount, $statement);
            }

            if ($expenseTaxAccountId && (float) $item->expense_tax_amount > 0) {
                $statement = $this->buildSettlementItemStatement($transfer, $item, 'فصل ضريبة المصروفات من عمولة مساند');
                $this->appendConstraintLine($lines, $expenseTaxAccountId, 'debtor', (float) $item->expense_tax_amount, $statement);
                $this->appendConstraintLine($lines, $commissionAccountId, 'creditor', (float) $item->expense_tax_amount, $statement, $costCenterId);
            }
        }

        if (empty($lines)) {
            return null;
        }

        $data = [
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
        int $bankAccountId,
        int $intermediateAccountId
    ): ?Constraint {
        $amount = $this->roundMoney((float) $transfer->transferred_total);
        if ($amount <= 0) {
            return null;
        }

        $statement = $this->buildBankTransferStatement($transfer);
        $lines = [];

        $this->appendConstraintLine($lines, $bankAccountId, 'debtor', $amount, $statement);
        $this->appendConstraintLine($lines, $intermediateAccountId, 'creditor', $amount, $statement);

        $data = [
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
        ?int $costCenterId = null
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
        ];
    }

    private function ensureRecruitmentContractCostCenter(Contract $contract): CostCenter
    {
        $rootCostCenter = $this->ensureRecruitmentContractsRootCostCenter();

        $costCenter = CostCenter::query()
            ->lockForUpdate()
            ->where('parent_id', $rootCostCenter->id)
            ->where('table_name', CostCenterTypeEnum::General->value)
            ->where('table_related_id', $contract->id)
            ->first();

        if ($costCenter) {
            return $costCenter;
        }

        $nationalityName = $contract->maid?->nationality?->sub_title
            ?? $contract->maid?->nationality?->title
            ?? $contract->requiredMaidNationality?->sub_title
            ?? $contract->requiredMaidNationality?->title;

        $title = 'عقد استقدام #' . $contract->id;
        if ($nationalityName) {
            $title .= ' - ' . $nationalityName;
        }

        return CostCenter::query()->create([
            'title' => $title,
            'parent_id' => $rootCostCenter->id,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => $contract->id,
            'code' => $this->nextCostCenterCode(),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    private function ensureRecruitmentContractsRootCostCenter(): CostCenter
    {
        $costCenter = CostCenter::query()
            ->lockForUpdate()
            ->whereNull('parent_id')
            ->where('title', 'عقود الاستقدام')
            ->first();

        if ($costCenter) {
            return $costCenter;
        }

        return CostCenter::query()->create([
            'title' => 'عقود الاستقدام',
            'parent_id' => null,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => 0,
            'code' => $this->nextCostCenterCode(),
            'created_by_id' => Auth::guard('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    private function nextCostCenterCode(): string
    {
        $lastCode = CostCenter::query()
            ->lockForUpdate()
            ->max('code');

        return (string) (((int) $lastCode) + 1);
    }

    private function findValidMusanedAccount(int $accountId): ?Account
    {
        return Account::query()
            ->whereKey($accountId)
            ->underMusanedParent()
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

    private function findValidBankAccount(int $accountId): ?Account
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
