<?php

namespace App\Services\ingaz;

use App\Http\Controllers\ingaz\InvoiceController;
use App\Models\Contract;
use App\Models\ContractReturnSettlement;
use App\Models\ContractReturnSettlementLine;
use App\Models\FinancialRequest;
use App\Models\ShelterAgentCostRecovery;
use App\Models\ShelterCostEntry;
use App\Support\NationalityAccountResolver;
use App\Models\commissions\CommissionBatch;
use App\Models\commissions\CommissionBatchItem;
use App\Services\commissions\CommissionOperationService;
use App\Services\commissions\RecruitmentCommissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\ConstraintDetails;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;

class PostArrivalRefundService
{
    public function __construct(
        protected ConstraintService $constraintService,
        protected YearService $yearService,
        protected RecruitmentContractWarrantyReturnService $warrantyReturnService,
        protected ShelterCostLedgerService $shelterLedger,
        protected RecruitmentCommissionService $commissionService,
    ) {}

    public function proposal(Contract $contract, string $date): array
    {
        $contract->loadMissing(['invoice.recruitmentCreditNotes', 'user', 'maid.office', 'info', 'externalOfficeDispatch']);
        if (! $contract->invoice) {
            throw ValidationException::withMessages(['contract_id' => 'العقد لا يملك فاتورة استقدام.']);
        }
        if (! $contract->info?->arrival_date) {
            throw ValidationException::withMessages(['contract_id' => 'هذه الشاشة مخصصة للعقود التي لها تاريخ وصول.']);
        }

        $invoice = $contract->invoice;
        $total = round((float) $invoice->total_due, 2);
        $credited = round((float) $invoice->recruitmentCreditNotes->sum('total'), 2);
        $creditRemaining = max(0, round($total - $credited, 2));
        $net = max(0, round($total - (float) $invoice->vat_amount, 2));
        $officeSource = $this->officeSource($contract);
        $officeCreditAccount = $this->officeChargeCreditAccount($contract);

        return [
            'credit_note' => $creditRemaining,
            'client_deduction' => $this->warrantyReturnService->calculateStayAmount($contract, $date, $net),
            'office_charge' => $officeSource['amount'],
            // Shelter admission already capitalized the office cost; proposing it again would double it.
            'shelter_cost' => ($officeCreditAccount['shelter_cleared'] || $officeCreditAccount['receipt_entry_id']) ? 0.0 : $officeSource['amount'],
            'marketer_commission' => $this->commissionProposal($contract),
            'invoice_total' => $total,
            'previous_credit' => $credited,
            'client_account_id' => (int) ($contract->user?->account_id ?: 0),
            'office_account_id' => $officeSource['account_id'],
            'office_charge_credit_account_id' => $officeCreditAccount['account_id'],
            'office_charge_credit_account_code' => $officeCreditAccount['account_code'],
            'office_charge_credit_account_name' => $officeCreditAccount['account_name'],
            'office_charge_account_source' => $officeCreditAccount['source'],
            'office_charge_receipt_entry_id' => $officeCreditAccount['receipt_entry_id'],
        ] + $this->clientDeductionAccount($contract);
    }

    /**
     * The operational event that ends the placement after arrival. A completed
     * contract is never returned "directly": the worker comes back through the
     * shelter, escapes, or is deported from the customer. Legacy cancelled
     * contracts and approved customer return requests remain accepted.
     */
    public function returnEvent(Contract $contract): ?array
    {
        if ($this->commissionService->isReturned($contract)) {
            return ['type' => 'returned', 'label' => 'العقد مسجّل مرتجعاً (' . getContractStatus($contract->status) . ')'];
        }
        if ($contract->status === \App\Enums\ingaz\ContractStatusEnum::Shelter->value) {
            return ['type' => 'shelter', 'label' => 'العاملة داخل الإيواء'];
        }
        $escape = $contract->escapeReports()->latest('id')->first();
        if ($escape) {
            return ['type' => 'escape', 'label' => 'بلاغ هروب #' . $escape->id . ' بتاريخ ' . optional($escape->escape_date)->format('Y-m-d')];
        }
        $deportation = \App\Models\DeportationProcedure::query()->where('contract_id', $contract->id)
            ->where('status', '!=', 'cancelled')->latest('id')->first();
        if ($deportation) {
            return ['type' => 'deportation', 'label' => 'إجراءات ترحيل #' . $deportation->id . ' (' . $deportation->status . ')'];
        }
        return null;
    }

    /**
     * Pre-flight checklist shown on the draft screen. Mirrors the blocking checks
     * of post() so the user sees every missing step at once, with the fix,
     * instead of discovering them one rejection at a time.
     *
     * @return array<int, array{key: string, title: string, ok: bool, detail: string, data?: array}>
     */
    public function readiness(ContractReturnSettlement $settlement): array
    {
        $settlement->loadMissing(['contract.invoice.recruitmentCreditNotes', 'contract.invoice.contract.user', 'contract.user',
            'contract.returnRequests', 'contract.latestShelterLog', 'lines']);
        $contract = $settlement->contract;
        $draftLines = $settlement->lines->where('status', 'draft');
        $steps = [];

        // 1) Return event after arrival: shelter admission, escape report or deportation.
        $event = $this->returnEvent($contract);
        $pending = $contract->returnRequests->where('status', 'pending')->sortByDesc('id')->first();
        $detail = $event
            ? $event['label'] . '.'
            : 'العقد في «' . getContractStatus($contract->status) . '» ولم يُسجَّل له حدث إرجاع بعد الوصول. '
                . 'سجّل الحدث المناسب أولاً: إدخال العاملة الإيواء، أو بلاغ هروب، أو بدء إجراءات الترحيل من عند العميل.'
                . ($pending ? ' (يوجد أيضاً طلب إرجاع من العميل #' . $pending->id . ' بانتظار الاعتماد.)' : '');
        $steps[] = ['key' => 'returned', 'title' => 'حدث الإرجاع (إيواء / هروب / ترحيل)', 'ok' => (bool) $event, 'detail' => $detail,
            'data' => ['pending_request_id' => $pending?->id, 'contract_status' => $contract->status]];

        // 2) Financial year
        $date = $settlement->settlement_date?->toDateString();
        $yearOpen = $date && $this->yearService->checkDateRange($date);
        $steps[] = ['key' => 'year', 'title' => 'السنة المالية لتاريخ المستند', 'ok' => (bool) $yearOpen,
            'detail' => $yearOpen ? 'السنة المالية مفتوحة لتاريخ ' . $date . '.' : 'لا توجد سنة مالية مفتوحة لتاريخ ' . $date . '. عدّل تاريخ المسودة أو افتح السنة المالية.'];

        // 3) Original invoice journal (needed by the credit note)
        $creditLine = $draftLines->firstWhere('type', 'credit_note');
        if ($creditLine) {
            $invoice = $contract->invoice;
            $linker = app(LegacyInvoiceConstraintLinker::class);
            $data = ['invoice_id' => $invoice?->id];
            if (!$invoice) {
                [$ok, $detail] = [false, 'العقد لا يملك فاتورة إيراد استقدام؛ لا يمكن إصدار إشعار دائن. احذف بند الإشعار من المسودة.'];
            } elseif ((int) $settlement->invoice_id !== (int) $invoice->id) {
                [$ok, $detail] = [false, 'تغيّرت فاتورة العقد بعد تجهيز المسودة. افتح «تعديل المسودة» واحفظها لتحديث المبالغ.'];
            } elseif ($invoice->invoice_constraint_id || $invoice->arrival_revenue_constraint_id) {
                [$ok, $detail] = [true, 'الفاتورة #' . $invoice->id . ' مربوطة بقيدها الأصلي.'];
            } else {
                $result = $linker->inspect($invoice);
                $ok = $result['status'] === 'ready';
                $detail = $ok
                    ? 'الفاتورة #' . $invoice->id . ' غير مربوطة بعد، وسيُربط قيدها الأصلي #' . $result['constraint_id'] . ' تلقائياً عند الاعتماد.'
                    : $linker->describe($invoice, $result);
                $data['needs_manual_link'] = !$ok;
            }
            if ($ok && $invoice) {
                $remaining = max(0, round((float) $invoice->total_due - (float) $invoice->recruitmentCreditNotes->sum('total'), 2));
                if (round((float) $creditLine->approved_amount, 2) > $remaining) {
                    [$ok, $detail] = [false, 'مبلغ الإشعار ' . number_format((float) $creditLine->approved_amount, 2)
                        . ' يتجاوز رصيد الفاتورة المتبقي ' . number_format($remaining, 2) . '. عدّل المسودة.'];
                }
            }
            $steps[] = ['key' => 'invoice', 'title' => 'قيد الفاتورة الأصلي (للإشعار الدائن)', 'ok' => $ok, 'detail' => $detail, 'data' => $data];
        }

        // 4) Marketer commission
        $commissionLine = $draftLines->firstWhere('type', 'marketer_commission');
        if ($commissionLine) {
            $item = CommissionBatchItem::query()->with('batch')
                ->where('source_type', CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT)
                ->where('source_id', $contract->id)->first();
            $reversed = $item?->commission_reversal_transaction_id
                ? (float) \App\Models\commissions\CommissionBalanceTransaction::query()->whereKey($item->commission_reversal_transaction_id)->value('amount')
                : 0.0;
            $amount = (float) $commissionLine->approved_amount;
            [$ok, $detail] = match (true) {
                !$item || (float) $item->commission_amount <= 0 => [false, 'لا توجد عمولة مسوق لهذا العقد. احذف بند العمولة من المسودة.'],
                $reversed > 0 && abs($reversed - $amount) <= 0.009 => [true, 'العمولة عُكست سابقاً من شاشة العمولات؛ سيُربط البند بذلك العكس دون تكرار.'],
                $reversed > 0 => [false, 'العمولة عُكست سابقاً بمبلغ ' . number_format($reversed, 2) . ' ويختلف عن مبلغ البند ' . number_format($amount, 2) . '. عدّل البند أو احذفه.'],
                $item->batch?->status !== CommissionBatch::STATUS_APPROVED => [false, 'دفعة العمولة #' . $item->batch_id . ' غير معتمدة بعد؛ لا يوجد ما يُسترجع. احذف البند أو اعتمد الدفعة أولاً.'],
                abs((float) $item->commission_amount - $amount) > 0.009 => [false, 'الاسترجاع الجزئي غير مدعوم؛ يجب أن يساوي البند كامل العمولة ' . number_format((float) $item->commission_amount, 2) . '.'],
                default => [true, 'عمولة معتمدة ' . number_format((float) $item->commission_amount, 2) . ' ستُسترجع عند الاعتماد.'],
            };
            $steps[] = ['key' => 'commission', 'title' => 'عمولة المسوق', 'ok' => $ok, 'detail' => $detail];
        }

        // 5) Office charge: which account will be credited, and is the shelter balance enough?
        $officeLine = $draftLines->firstWhere('type', 'office_charge');
        if ($officeLine) {
            $account = fn ($id) => ($row = Account::query()->find($id)) ? $row->code . ' — ' . trim($row->name) : 'حساب #' . $id;
            if (ShelterAgentCostRecovery::query()->where('contract_id', $contract->id)->whereNull('reversal_constraint_id')->exists()) {
                [$ok, $detail] = [false, 'سبق استرداد تكلفة الوكيل لهذا العقد من شاشة الإيواء. احذف بند تحميل المكتب من المسودة حتى لا يُحمَّل مرتين.'];
            } elseif ($officeLine->settlement_method === 'next_dispatch_deduction') {
                [$ok, $detail] = [true, 'يُخصم من الإرسالية القادمة للمكتب؛ لا قيد الآن.'];
            } else {
                $source = $this->officeChargeCreditAccount($contract);
                $current = (int) $officeLine->credit_account_id;
                $forced = $this->expectedOfficeChargeAccount($contract, $current);
                $keepsShelterBalance = $forced && $current > 0 && $forced !== $current
                    && ! $this->officeLinePredatesShelterReceipt($officeLine, (int) $source['receipt_entry_id']);
                $expected = $keepsShelterBalance ? $current : ($forced ?: $current);
                $ok = $expected > 0;
                $clearedAsset = ! $source['receipt_entry_id'] && $current > 0 && ShelterCostEntry::query()->where('contract_id', $contract->id)
                    ->where('kind', ShelterCostEntry::KIND_CAPITALIZE_OFFICE_COST)->where('asset_account_id', $current)->exists();
                $detail = ! $ok ? 'لا يوجد حساب دائن لبند تحميل المكتب. عدّل المسودة واختر الحساب.'
                    : ($keepsShelterBalance
                        ? 'سيُقيد دائناً على ' . $account($expected) . ' حسب اختيارك. رصيد الإيواء (حركة #' . $source['receipt_entry_id'] . ') يبقى كما هو ويُسوّى عند خروج العاملة. الحساب المقترح كان ' . $account($forced) . '.'
                        : 'سيُقيد دائناً على ' . $account($expected) . ($source['receipt_entry_id'] ? ' (رصيد الإيواء، حركة #' . $source['receipt_entry_id'] . ')' : '') . '.');
                if ($clearedAsset) {
                    [$ok, $detail] = [false, 'رصيد الإيواء تمت تصفيته (خروج أو ترحيل)، والمسودة ما زالت على حساب أصل الإيواء ' . $account($current)
                        . '. قرار محاسبي: افتح «تعديل المسودة» واختر حساب مردودات تكلفة الاستقدام' . ($source['account_id'] ? ' (المقترح: ' . $account($source['account_id']) . ')' : '')
                        . '، أو احذف البند إن كانت التكلفة سُوّيت في الترحيل.'];
                }
                if ($ok && $expected !== $current) {
                    $detail .= ' الحساب المحفوظ في المسودة (' . ($current ? $account($current) : 'غير محدد') . ') سيُصحَّح تلقائياً عند الاعتماد لأن حالة الإيواء تغيّرت بعد تجهيز المسودة.';
                }
                if ($ok && $source['receipt_entry_id'] && ! $keepsShelterBalance) {
                    $summary = app(ShelterAgentCostRecoveryService::class)->summary($contract);
                    $remaining = (float) ($summary['lots'][$source['receipt_entry_id']]['remaining'] ?? 0);
                    if ($summary['issues']) {
                        [$ok, $detail] = [false, 'اختلافات في دفتر الإيواء: ' . implode(' ', $summary['issues']) . ' اطلب من المحاسب مراجعتها.'];
                    } elseif ((float) $officeLine->approved_amount > $remaining + 0.009) {
                        [$ok, $detail] = [false, 'مبلغ التحميل ' . number_format((float) $officeLine->approved_amount, 2) . ' أكبر من رصيد الإيواء المتاح ' . number_format($remaining, 2) . '. عدّل المسودة.'];
                    }
                }
            }
            $steps[] = ['key' => 'office', 'title' => 'تحميل المكتب الخارجي', 'ok' => $ok, 'detail' => $detail];
        }

        // 6) Shelter cost line would capitalize the office cost a second time
        if ($draftLines->firstWhere('type', 'shelter_cost') && ($existing = ShelterCostEntry::query()->where('contract_id', $contract->id)
                ->where('kind', ShelterCostEntry::KIND_CAPITALIZE_OFFICE_COST)->latest('id')->first())) {
            $steps[] = ['key' => 'shelter_cost', 'title' => 'تكلفة الإيواء', 'ok' => false,
                'detail' => 'تكلفة المكتب نُقلت للإيواء مسبقاً (حركة #' . $existing->id . ' بمبلغ ' . number_format((float) $existing->amount, 2) . '). احذف بند «تكلفة الإيواء» من المسودة حتى لا تُنقل مرتين.'];
        }

        return $steps;
    }

    public function createDraft(Contract $contract, array $data): ContractReturnSettlement
    {
        return $this->saveDraft($contract, $data);
    }

    public function updateDraft(ContractReturnSettlement $settlement, array $data): ContractReturnSettlement
    {
        return $this->saveDraft($settlement->contract, $data, $settlement);
    }

    private function saveDraft(Contract $contract, array $data, ?ContractReturnSettlement $existing = null): ContractReturnSettlement
    {
        return DB::transaction(function () use ($contract, $data, $existing) {
            $contract = Contract::query()->with(['invoice', 'user', 'maid.office', 'info'])->lockForUpdate()->findOrFail($contract->id);
            if ($existing) {
                $existing = ContractReturnSettlement::query()->lockForUpdate()->findOrFail($existing->id);
                if ($existing->status !== 'draft' || $existing->lines()->where('status', '!=', 'draft')->exists()) {
                    throw ValidationException::withMessages(['status' => 'يمكن تعديل المسودة فقط قبل اعتماد أي بند.']);
                }
            }
            if (! $existing && ContractReturnSettlement::query()->where('contract_id', $contract->id)->whereNotIn('status', ['cancelled'])->exists()) {
                throw ValidationException::withMessages(['contract_id' => 'يوجد مستند استرداد نشط لهذا العقد.']);
            }

            $proposal = $this->proposal($contract, $data['settlement_date']);
            $settlement = $existing ?: new ContractReturnSettlement();
            $settlement->fill([
                'contract_id' => $contract->id,
                'invoice_id' => $contract->invoice?->id,
                'return_reason_code_id' => $data['return_reason_code_id'],
                'settlement_date' => $data['settlement_date'],
                'status' => 'draft',
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'created_by_id' => $existing?->created_by_id ?? Auth::guard('admin')->id(),
                'settings_snapshot' => ['posting_mode' => 'manual_screen'],
            ]);

            $settlement->save();
            if ($existing) $settlement->lines()->delete();

            foreach ($data['lines'] as $index => $line) {
                if (empty($line['enabled'])) continue;
                $amount = round((float) ($line['approved_amount'] ?? 0), 2);
                if ($amount <= 0) continue;
                $type = $line['type'];
                if ($type === 'credit_note' && $amount > (float) $proposal['credit_note']) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.approved_amount" => 'مبلغ الإشعار الدائن لا يمكن أن يتجاوز رصيد الفاتورة المتبقي: ' . number_format((float) $proposal['credit_note'], 2) . ' ريال.',
                    ]);
                }
                if ($type === 'office_charge'
                    && ($line['settlement_method'] ?? 'claim') !== 'next_dispatch_deduction'
                    && ! empty($proposal['office_charge_credit_account_id'])
                    && empty($line['credit_account_id'])) {
                    $line['credit_account_id'] = $proposal['office_charge_credit_account_id'];
                }
                if ($type === 'client_deduction' && empty($line['credit_account_id']) && ! empty($proposal['client_deduction_credit_account_id'])) {
                    $line['credit_account_id'] = $proposal['client_deduction_credit_account_id'];
                }
                if ($type === 'shelter_cost' && ! empty($proposal['office_charge_receipt_entry_id'])) {
                    throw ValidationException::withMessages([
                        "lines.{$index}.approved_amount" => 'تكلفة المكتب نُقلت للإيواء تلقائياً عند دخول العاملة (حركة الإيواء #' . $proposal['office_charge_receipt_entry_id'] . '). ألغِ اختيار بند «تكلفة الإيواء» حتى لا تُنقل مرتين.',
                    ]);
                }
                $needsCreditAccount = $type === 'client_deduction'
                    || ($type === 'office_charge' && ($line['settlement_method'] ?? 'claim') !== 'next_dispatch_deduction');
                if ($needsCreditAccount) {
                    $creditAccountId = (int) ($line['credit_account_id'] ?? 0);
                    $isLeaf = $creditAccountId > 0
                        && Account::query()->whereKey($creditAccountId)->whereDoesntHave('children')->exists();
                    if (! $isLeaf) {
                        $label = $type === 'client_deduction' ? 'خصم العميل' : 'تحميل المكتب الخارجي';
                        throw ValidationException::withMessages([
                            "lines.{$index}.credit_account_id" => "اختر حساباً دائناً طرفياً لبند {$label} قبل حفظ المسودة.",
                        ]);
                    }
                }
                $proposed = round((float) ($proposal[$type] ?? $amount), 2);
                if (abs($amount - $proposed) > 0.009 && trim((string) ($line['reason'] ?? '')) === '') {
                    throw ValidationException::withMessages(['lines' => 'سبب التعديل مطلوب لكل بند يختلف عن المقترح.']);
                }
                $settlement->lines()->create([
                    'type' => $type,
                    'status' => 'draft',
                    'proposed_amount' => $proposed,
                    'approved_amount' => $amount,
                    'credit_account_id' => $line['credit_account_id'] ?? null,
                    'settlement_method' => $line['settlement_method'] ?? null,
                    'reason' => $line['reason'] ?? null,
                    'metadata' => $type === 'office_charge' ? [
                        'credit_account_source' => $proposal['office_charge_account_source'] ?? null,
                        'shelter_receipt_entry_id' => $proposal['office_charge_receipt_entry_id'] ?? null,
                    ] : null,
                ]);
            }

            if (! $settlement->lines()->exists()) {
                throw ValidationException::withMessages(['lines' => 'اختر بنداً واحداً على الأقل بمبلغ أكبر من صفر.']);
            }

            $settlement->update([
                'proposed_total' => $settlement->lines()->sum('proposed_amount'),
                'approved_total' => $settlement->lines()->sum('approved_amount'),
            ]);

            return $settlement->fresh('lines');
        });
    }

    public function post(ContractReturnSettlement $settlement): ContractReturnSettlement
    {
        return DB::transaction(function () use ($settlement) {
            $settlement = ContractReturnSettlement::query()->lockForUpdate()->findOrFail($settlement->id);
            if ($settlement->status !== 'draft') return $settlement;
            // Read under locks before loading relations, so a competing exit or
            // recovery cannot leave this transaction with an older cost snapshot.
            $contractRow = DB::table('contracts')->where('id', $settlement->contract_id)->lockForUpdate()->first(['maid_id']);
            if ($contractRow?->maid_id) {
                \App\Models\ingaz\Maid::query()->whereKey($contractRow->maid_id)->lockForUpdate()->firstOrFail();
            }
            $settlement->load(['contract.invoice.recruitmentCreditNotes', 'contract.user', 'contract.maid.office', 'lines']);
            if (!$settlement->contract->invoice || (int) $settlement->invoice_id !== (int) $settlement->contract->invoice->id) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'تغيرت فاتورة إيراد العقد أو كانت المسودة مرتبطة بفاتورة إغلاق. عدّل المسودة وراجع المبالغ قبل الاعتماد.',
                ]);
            }
            if (! $this->returnEvent($settlement->contract)) {
                throw ValidationException::withMessages([
                    'contract_id' => 'لا يمكن الاعتماد: العقد #' . $settlement->contract_id . ' (' . getContractStatus($settlement->contract->status) . ') '
                        . 'لم يُسجَّل له حدث إرجاع بعد الوصول. أدخل العاملة الإيواء، أو سجّل بلاغ هروب، أو ابدأ إجراءات الترحيل، ثم أعد الاعتماد. راجع «خطوات الاعتماد» أعلى المستند.',
                ]);
            }
            if (! $this->yearService->checkDateRange($settlement->settlement_date->toDateString())) {
                throw new \RuntimeException('لا توجد سنة مالية نشطة لتاريخ المستند.');
            }

            foreach ($settlement->lines as $line) {
                $this->postLine($settlement, $line);
            }
            $credit = (float) $settlement->lines()->where('type', 'credit_note')->where('status', 'posted')->sum('approved_amount');
            $deduction = (float) $settlement->lines()->where('type', 'client_deduction')->where('status', 'posted')->sum('approved_amount');
            $due = max(0, round($credit - $deduction, 2));
            $settlement->update([
                'status' => 'posted',
                'client_due_amount' => $due,
                'client_paid_amount' => 0,
                'approved_by_id' => Auth::guard('admin')->id(),
                'approved_at' => now(),
                'settings_snapshot' => $this->settingsSnapshot($settlement),
            ]);
            return $settlement->fresh(['lines', 'contract.user', 'contract.maid']);
        });
    }

    private function postLine(ContractReturnSettlement $settlement, ContractReturnSettlementLine $line): void
    {
        if ($line->status !== 'draft') return;
        $contract = $settlement->contract;
        $statement = $line->typeLabel() . ' — مستند استرداد بعد الوصول #' . $settlement->id . ' — عقد #' . $contract->id;

        if ($line->type === 'credit_note') {
            $proposal = $this->proposal($contract, $settlement->settlement_date->toDateString());
            if ((float) $line->approved_amount <= 0 || round((float) $line->approved_amount, 2) > (float) $proposal['credit_note']) {
                throw ValidationException::withMessages([
                    'credit_note' => sprintf(
                        'تعذر الاعتماد: مبلغ الإشعار الدائن المدخل هو %s ريال. يجب أن يكون أكبر من صفر وألا يتجاوز رصيد الفاتورة المتبقي: %s ريال.',
                        number_format((float) $line->approved_amount, 2),
                        number_format((float) $proposal['credit_note'], 2)
                    ),
                ]);
            }
            $note = app(InvoiceController::class)->createSystemCreditNoteForRecruitmentRefund(
                $contract->invoice,
                $settlement->settlement_date->toDateString(),
                $statement,
                $this->constraintService,
                $this->yearService,
                (float) $line->approved_amount
            );
            $line->update(['status' => 'posted', 'credit_note_id' => $note?->id, 'constraint_id' => $note?->constraint_id]);
            return;
        }

        if ($line->type === 'client_payout') {
            if ($line->settlement_method === 'balance_only') {
                $line->update(['status' => 'posted']);
                return;
            }
            $request = $this->createFinancialRequest($settlement, $line);
            $line->update(['status' => 'posted', 'financial_request_id' => $request->id]);
            return;
        }

        if ($line->type === 'shelter_cost') {
            $existing = ShelterCostEntry::query()->where('contract_id', $contract->id)
                ->where('kind', ShelterCostEntry::KIND_CAPITALIZE_OFFICE_COST)->latest('id')->first();
            if ($existing) {
                throw ValidationException::withMessages(['shelter_cost' => 'تكلفة المكتب نُقلت للإيواء مسبقاً (حركة الإيواء #' . $existing->id
                    . ' بمبلغ ' . number_format((float) $existing->amount, 2) . '). افتح «تعديل المسودة» وألغِ بند «تكلفة الإيواء» حتى لا تُنقل مرتين.']);
            }
            $result = $this->shelterLedger->capitalizeOfficeCost(
                $contract,
                (float) $line->approved_amount,
                $settlement->settlement_date->toDateString(),
                $line,
                $statement
            );
            if (! ($result['constraint'] ?? null)) {
                throw new \RuntimeException('تعذر إنشاء قيد تكلفة الإيواء؛ تحقق من تفعيل القيود وحسابات الجنسية ومصدر التكلفة.');
            }
            $line->update(['status' => 'posted', 'constraint_id' => $result['constraint']->id, 'metadata' => ['shelter_entry_id' => $result['entry']?->id]]);
            return;
        }

        if ($line->type === 'marketer_commission') {
            $item = CommissionBatchItem::query()->with('batch')
                ->where('source_type', CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT)
                ->where('source_id', $contract->id)->lockForUpdate()->first();
            // Already reversed in full from the commissions screen: link that reversal
            // instead of failing the whole document or reversing twice.
            $reversedAmount = $item?->commission_reversal_transaction_id
                ? (float) \App\Models\commissions\CommissionBalanceTransaction::query()
                    ->whereKey($item->commission_reversal_transaction_id)->value('amount')
                : 0.0;
            if ($reversedAmount > 0 && abs($reversedAmount - (float) $line->approved_amount) <= 0.009) {
                $line->update(['status' => 'posted', 'metadata' => [
                    'commission_item_id' => $item->id,
                    'commission_reversal_transaction_id' => $item->commission_reversal_transaction_id,
                    'commission_reversal_constraint_id' => $item->commission_reversal_constraint_id,
                    'already_reversed' => true,
                ]]);
                return;
            }
            if (! $item || $item->batch?->status !== CommissionBatch::STATUS_APPROVED || (float) $item->commission_amount <= 0) {
                throw new \RuntimeException('لا توجد عمولة مسوق معتمدة قابلة للاسترجاع لهذا العقد.');
            }
            if (abs((float) $item->commission_amount - (float) $line->approved_amount) > 0.009) {
                throw new \RuntimeException('استرجاع عمولة المسوق الجزئي غير مدعوم؛ يجب أن يساوي البند كامل العمولة المعتمدة.');
            }
            $item = $this->commissionService->resolveSettlement($item, RecruitmentCommissionService::SETTLEMENT_DEDUCTED, (int) Auth::guard('admin')->id(), $line->reason ?: $statement, null, true);
            $line->update(['status' => 'posted', 'metadata' => ['commission_item_id' => $item->id, 'commission_reversal_transaction_id' => $item->commission_reversal_transaction_id]]);
            return;
        }

        if ($line->type === 'office_charge'
            && ShelterAgentCostRecovery::query()->where('contract_id', $contract->id)
                ->whereNull('reversal_constraint_id')->exists()) {
            throw ValidationException::withMessages([
                'office_charge' => 'سبق إثبات استرداد تكلفة الوكيل لهذا العقد من شاشة الإيواء؛ لا يمكن تحميل المكتب مرة ثانية ضمن مستند الاسترداد بعد الوصول.',
            ]);
        }

        if ($line->type === 'office_charge' && $line->settlement_method === 'next_dispatch_deduction') {
            $source = $this->officeSource($contract);
            $this->assertLeafAccount((int) $source['account_id'], 'حساب المكتب من قيد المورد الأصلي');
            $line->update([
                'status' => 'pending',
                'debit_account_id' => $source['account_id'],
                'account_snapshot' => $this->accountSnapshot([(int) $source['account_id']]),
                'metadata' => ['awaiting_next_dispatch' => true],
            ]);
            return;
        }

        $debitAccountId = match ($line->type) {
            'client_deduction' => (int) ($contract->user?->account_id ?: 0),
            'office_charge' => (int) $this->officeSource($contract)['account_id'],
            default => (int) ($line->debit_account_id ?: 0),
        };
        $creditAccountId = (int) ($line->credit_account_id ?: 0);
        if ($line->type === 'office_charge') {
            $source = $this->officeChargeCreditAccount($contract);
            $receiptId = $source['receipt_entry_id'];
            // The draft may predate a shelter admission or exit. The correct credit account follows
            // the shelter state at posting time, so align it instead of rejecting the document.
            $expected = $this->expectedOfficeChargeAccount($contract, $creditAccountId);
            if ($expected && $expected !== $creditAccountId) {
                if ($this->officeLinePredatesShelterReceipt($line, (int) $receiptId)) {
                    $line->update([
                        'credit_account_id' => $expected,
                        'metadata' => array_replace($line->metadata ?? [], ['credit_account_realigned_from' => $creditAccountId, 'credit_account_source' => $source['source']]),
                    ]);
                    $creditAccountId = $expected;
                } else {
                    // Accountant chose another account after admission: the shelter balance is not
                    // reduced here (no ledger entry) and is settled later at the worker's exit.
                    $line->update(['metadata' => array_replace($line->metadata ?? [], ['shelter_receipt_entry_id' => null, 'shelter_balance_kept' => true])]);
                    $receiptId = null;
                }
            }
            if (! $receiptId) {
                $line->update(['metadata' => array_replace($line->metadata ?? [], ['shelter_receipt_entry_id' => null])]);
                if (ShelterCostEntry::query()->where('contract_id', $contract->id)
                    ->where('kind', ShelterCostEntry::KIND_CAPITALIZE_OFFICE_COST)
                    ->where('asset_account_id', $creditAccountId)->exists()) {
                    throw ValidationException::withMessages(['office_charge' => 'رصيد الإيواء تمت تصفيته، ولا يمكن تحميل المكتب على حساب أصل الإيواء. افتح «تعديل المسودة»، واختر حساب مردودات تكلفة الاستقدام لبند تحميل المكتب الخارجي، ثم احفظ وأعد الاعتماد.']);
                }
            }
            if ($receiptId) {
                $summary = app(ShelterAgentCostRecoveryService::class)->summary($contract);
                $remaining = $summary['lots'][$receiptId]['remaining'] ?? 0;
                if ($summary['issues'] || (float) $line->approved_amount > $remaining || $remaining <= 0) {
                    throw ValidationException::withMessages(['office_charge' => $summary['issues']
                        ? 'تعذر اعتماد تحميل المكتب بسبب اختلافات في دفتر الإيواء: ' . implode(' ', $summary['issues']) . ' اطلب من المحاسب مراجعة حركات الإيواء قبل إعادة الاعتماد.'
                        : 'تعذر اعتماد تحميل المكتب على رصيد الإيواء: المبلغ المطلوب ' . number_format((float) $line->approved_amount, 2) . ' ريال، والمتاح ' . number_format((float) $remaining, 2) . ' ريال. افتح «تعديل المسودة» وراجع مبلغ التحميل والحساب، ثم احفظ وأعد الاعتماد.']);
                }
                $line->update(['metadata' => array_replace($line->metadata ?? [], ['shelter_receipt_entry_id' => $receiptId])]);
            }
        }
        $this->assertLeafAccount($debitAccountId, 'الحساب المدين');
        $this->assertLeafAccount($creditAccountId, 'الحساب الدائن');
        $constraint = $this->storeConstraint($settlement->settlement_date->toDateString(), $statement, $debitAccountId, $creditAccountId, (float) $line->approved_amount);
        $line->update([
            'status' => 'posted',
            'debit_account_id' => $debitAccountId,
            'constraint_id' => $constraint->id,
            'account_snapshot' => $this->accountSnapshot([$debitAccountId, $creditAccountId]),
        ]);
        if ($line->type === 'office_charge') {
            $this->shelterLedger->recordPostArrivalRecovery($contract, $line, $constraint);
        }
    }

    public function reverseLine(ContractReturnSettlementLine $line, string $reason): ContractReturnSettlementLine
    {
        return DB::transaction(function () use ($line, $reason) {
            $line = ContractReturnSettlementLine::query()->lockForUpdate()->findOrFail($line->id);
            if ($line->status === 'reversed') return $line;
            $contractRow = DB::table('contracts as c')->join('contract_return_settlements as s', 's.contract_id', '=', 'c.id')
                ->where('s.id', $line->settlement_id)->lockForUpdate()->first(['c.maid_id']);
            if ($contractRow?->maid_id) {
                \App\Models\ingaz\Maid::query()->whereKey($contractRow->maid_id)->lockForUpdate()->firstOrFail();
            }
            $contract = $line->settlement->contract;
            $recoveryEntry = ShelterCostEntry::query()->where('source_type', ContractReturnSettlementLine::class)
                ->where('source_id', $line->id)->where('kind', ShelterCostEntry::KIND_POST_REFUND_RECOVERY)->first();
            if ($recoveryEntry && ShelterCostEntry::query()->where('maid_id', $recoveryEntry->maid_id)
                ->where('id', '>', $recoveryEntry->id)->whereIn('kind', [ShelterCostEntry::KIND_RELEASE_TO_DEPORTATION,
                    ShelterCostEntry::KIND_RELEASE_TO_SALE, ShelterCostEntry::KIND_RELEASE_TO_TRIAL, ShelterCostEntry::KIND_WRITE_OFF,
                    ShelterCostEntry::KIND_RETURN_TO_CUSTOMER])->exists()) {
                throw ValidationException::withMessages(['reason' => 'لا يمكن عكس تحميل المكتب بعد خروج العاملة أو تصفية تكلفة الإيواء.']);
            }
            if ($line->credit_note_id) {
                throw ValidationException::withMessages(['reason' => 'الإشعار الدائن الضريبي لا يعكس بقيد يومية. أنشئ إشعاراً مديناً نظامياً ثم اربطه بالمستند.']);
            }
            if ($line->type === 'marketer_commission') {
                throw ValidationException::withMessages(['reason' => 'عكس استرجاع عمولة المسوق يتم من شاشة العمولات حتى تُحفظ حركة الرصيد المقابلة.']);
            }
            if ($line->financial_request_id) {
                $request = FinancialRequest::query()->find($line->financial_request_id);
                if ($request && ! in_array($request->status, [FinancialRequest::STATUS_PREPARING, FinancialRequest::STATUS_FOR_APPROVAL, FinancialRequest::STATUS_REJECTED], true)) {
                    throw ValidationException::withMessages(['reason' => 'لا يمكن عكس طلب مالي بعد اعتماده أو دفعه.']);
                }
                $request?->delete();
            }
            $reversal = null;
            if ($line->constraint_id) {
                $original = Constraint::query()->with('constraintDetails')->findOrFail($line->constraint_id);
                $details = $original->constraintDetails;
                if ($details->isEmpty()) throw new \RuntimeException('القيد الأصلي بلا تفاصيل.');
                $date = now()->toDateString();
                if (! $this->yearService->checkDateRange($date)) throw new \RuntimeException('لا توجد سنة مالية نشطة لتاريخ العكس.');
                $statement = 'عكس ' . $original->statement . ' — ' . $reason;
                $rows = $details->map(fn ($d) => [
                    'account_id' => (int) $d->account_id,
                    'account_type' => (float) $d->creditor_value > 0 ? 'debtor' : 'creditor',
                    'debtor_value' => (float) $d->creditor_value,
                    'creditor_value' => (float) $d->debtor_value,
                    'statement' => $statement,
                    'cost_center_id' => $d->cost_center_id,
                ])->all();
                $reversal = $this->storeRows($date, $statement, $rows);
            }
            $line->update(['status' => 'reversed', 'reversal_constraint_id' => $reversal?->id, 'reversal_reason' => $reason, 'reversed_by_id' => Auth::guard('admin')->id(), 'reversed_at' => now()]);
            if ($recoveryEntry && $reversal) {
                $this->shelterLedger->recordPostArrivalRecovery($contract, $line, $reversal, true);
            }
            return $line->fresh();
        });
    }

    public function requestPayout(ContractReturnSettlement $settlement, float $amount, string $method = 'financial_request'): ContractReturnSettlementLine
    {
        return DB::transaction(function () use ($settlement, $amount, $method) {
            $settlement = ContractReturnSettlement::query()->with(['contract.user', 'contract.maid', 'lines'])->lockForUpdate()->findOrFail($settlement->id);
            if (! in_array($settlement->status, ['posted', 'partially_paid'], true)) {
                throw ValidationException::withMessages(['amount' => 'يجب اعتماد المستند قبل إضافة صرف.']);
            }
            $alreadyRequested = (float) $settlement->lines()->where('type', 'client_payout')->whereIn('status', ['posted', 'paid'])->sum('approved_amount');
            $remaining = max(0, round((float) $settlement->client_due_amount - $alreadyRequested, 2));
            $amount = round($amount, 2);
            if ($amount <= 0 || $amount > $remaining) {
                throw ValidationException::withMessages(['amount' => 'مبلغ الصرف يجب ألا يتجاوز المتبقي ' . number_format($remaining, 2) . '.']);
            }
            $line = $settlement->lines()->create([
                'type' => 'client_payout', 'status' => 'draft', 'proposed_amount' => $remaining,
                'approved_amount' => $amount, 'settlement_method' => $method,
                'reason' => $amount < $remaining ? 'صرف جزئي' : null,
            ]);
            $this->postLine($settlement, $line);
            return $line->fresh('financialRequest');
        });
    }

    private function createFinancialRequest(ContractReturnSettlement $settlement, ContractReturnSettlementLine $line): FinancialRequest
    {
        $contract = $settlement->contract;
        $sequence = (int) FinancialRequest::query()->lockForUpdate()->whereYear('created_at', now()->year)->count() + 1;
        return FinancialRequest::query()->create([
            'request_no' => 'PAR-' . now()->format('Y') . '-' . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
            'type' => 'client_refund', 'status' => FinancialRequest::STATUS_FOR_APPROVAL, 'priority' => FinancialRequest::PRIORITY_NORMAL,
            'amount' => $line->approved_amount, 'request_date' => $settlement->settlement_date,
            'description' => 'صرف استرداد بعد الوصول للمستند #' . $settlement->id,
            'payload' => ['refund_reason' => $settlement->reason],
            'client_id' => $contract->user_id, 'client_name' => $contract->user?->name,
            'maid_id' => $contract->maid_id, 'maid_name' => $contract->maid?->name,
            'contract_id' => $contract->id, 'return_settlement_id' => $settlement->id,
            'contract_type' => 'recruitment', 'contract_number' => (string) $contract->id,
            'created_by_id' => Auth::guard('admin')->id(), 'updated_by_id' => Auth::guard('admin')->id(),
        ]);
    }

    private function storeConstraint(string $date, string $statement, int $debit, int $credit, float $amount): Constraint
    {
        return $this->storeRows($date, $statement, [
            ['account_id' => $debit, 'account_type' => 'debtor', 'debtor_value' => $amount, 'creditor_value' => 0, 'statement' => $statement, 'cost_center_id' => null],
            ['account_id' => $credit, 'account_type' => 'creditor', 'debtor_value' => 0, 'creditor_value' => $amount, 'statement' => $statement, 'cost_center_id' => null],
        ]);
    }

    private function storeRows(string $date, string $statement, array $rows): Constraint
    {
        $year = $this->yearService->checkDateRange($date);
        if (! $year) throw new \RuntimeException('لا توجد سنة مالية نشطة للتاريخ المحدد.');
        $value = round((float) collect($rows)->sum('debtor_value'), 2);
        $data = ['code' => $this->constraintService->lastCode(), 'date' => $date, 'statement' => $statement,
            'account_id' => array_column($rows, 'account_id'), 'account_type' => array_column($rows, 'account_type'),
            'debtor_value' => array_column($rows, 'debtor_value'), 'creditor_value' => array_column($rows, 'creditor_value'),
            'statement_account' => array_column($rows, 'statement'), 'cost_center_required' => 'off',
            'cost_center_id' => array_column($rows, 'cost_center_id'), 'value' => $value];
        $constraint = $this->constraintService->storeConstraint($data, $year);
        $this->constraintService->storeConstraintDetails($data['account_id'], $constraint->id, $data);
        $this->constraintService->storeFiles($constraint, $data);
        return $constraint;
    }

    private function assertLeafAccount(int $id, string $label): void
    {
        if ($id <= 0 || ! Account::query()->whereKey($id)->whereDoesntHave('children')->exists()) {
            throw ValidationException::withMessages(['account' => $label . ' مفقود أو غير طرفي.']);
        }
    }

    /** حساب المكتب ومبلغه من قيد المورد الأصلي، لا من المكتب الحالي للعاملة. */
    private function officeSource(Contract $contract): array
    {
        $constraintId = (int) ($contract->invoice?->external_office_cost_constraint_id ?: 0);
        if ($constraintId <= 0) return ['account_id' => 0, 'amount' => 0.0];
        $creditors = ConstraintDetails::query()->where('constraint_id', $constraintId)
            ->where('creditor_value', '>', 0)->get()->groupBy('account_id');
        if ($creditors->count() !== 1) return ['account_id' => 0, 'amount' => 0.0];
        $accountId = (int) $creditors->keys()->first();
        if (! Account::query()->whereKey($accountId)->whereDoesntHave('children')->exists()) {
            return ['account_id' => 0, 'amount' => 0.0];
        }
        return ['account_id' => $accountId, 'amount' => round((float) $creditors->first()->sum('creditor_value'), 2)];
    }

    /** الحساب المقابل لتحميل المكتب: أصل الإيواء المثبت أولاً، وإلا مردودات تكلفة الجنسية. */
    /**
     * While the worker has an open shelter balance the office charge can only reduce
     * that balance, so its credit account is forced. Returns null otherwise: after the
     * balance is cleared the account is the accountant's decision (see post guards).
     */
    private function expectedOfficeChargeAccount(Contract $contract, int $currentAccountId): ?int
    {
        $source = $this->officeChargeCreditAccount($contract);
        return $source['receipt_entry_id'] && $source['account_id'] ? (int) $source['account_id'] : null;
    }

    /** A draft line saved before the shelter admission still carries the pre-admission account. */
    private function officeLinePredatesShelterReceipt(ContractReturnSettlementLine $line, int $receiptId): bool
    {
        $receipt = $receiptId ? ShelterCostEntry::query()->find($receiptId) : null;
        return $receipt && $line->created_at && $receipt->created_at && $line->created_at->lt($receipt->created_at);
    }

    /** Suggested credit for the stay deduction: the nationality's cost of sales (company practice). */
    private function clientDeductionAccount(Contract $contract): array
    {
        $nationality = $this->shelterLedger->nationalityOf($contract);
        $name = trim((string) ($nationality?->sub_title ?: $nationality?->title ?: ''));
        $id = $name !== '' ? app(RecruitmentTripAccountingService::class)->resolveCostOfSalesAccountId($name, $nationality?->id) : null;
        $account = $id ? Account::query()->whereKey($id)->whereDoesntHave('children')->first() : null;
        return [
            'client_deduction_credit_account_id' => (int) ($account?->id ?: 0),
            'client_deduction_credit_account_code' => $account?->code,
            'client_deduction_credit_account_name' => $account?->name,
        ];
    }

    private function officeChargeCreditAccount(Contract $contract): array
    {
        $supplierConstraintId = (int) ($contract->invoice?->external_office_cost_constraint_id ?: 0);
        $receiptQuery = ShelterCostEntry::query()
            ->where('contract_id', $contract->id)
            ->where('kind', ShelterCostEntry::KIND_CAPITALIZE_OFFICE_COST)
            ->whereNotNull('asset_account_id');
        if ($supplierConstraintId > 0) {
            $receiptQuery->where('supplier_constraint_id', $supplierConstraintId);
        }
        $receipt = $receiptQuery->latest('id')->first();
        $shelterCleared = false;
        if ($receipt) {
            $summary = app(ShelterAgentCostRecoveryService::class)->summary($contract);
            $remaining = $summary['lots'][$receipt->id]['remaining'] ?? null;
            $hasFinalExit = ShelterCostEntry::query()->where('maid_id', $contract->maid_id)
                ->where('id', '>', $receipt->id)
                ->whereIn('kind', [ShelterCostEntry::KIND_RELEASE_TO_DEPORTATION, ShelterCostEntry::KIND_WRITE_OFF, ShelterCostEntry::KIND_RETURN_TO_CUSTOMER])
                ->exists();
            // A historical receipt is not an available asset after a complete exit.
            // Keep unresolved or recovered lots guarded by the posting checks.
            if (! $summary['issues'] && $remaining !== null && (float) $remaining === 0.0
                && (float) $summary['recovered'] === 0.0 && $hasFinalExit) {
                $receipt = null;
                $shelterCleared = true;
            }
        }
        $accountId = (int) ($receipt?->asset_account_id ?: 0);
        $source = $accountId > 0 ? 'shelter_receipt' : null;

        if ($accountId <= 0) {
            $nationality = $this->shelterLedger->nationalityOf($contract);
            $accountId = (int) (NationalityAccountResolver::forNationality($nationality, 'recruitment_cost_return') ?: 0);
            $source = $accountId > 0 ? 'nationality_cost_return' : null;
        }

        $account = $accountId > 0
            ? Account::query()->whereKey($accountId)->whereDoesntHave('children')->first()
            : null;

        return [
            'account_id' => (int) ($account?->id ?: 0),
            'account_code' => $account?->code,
            'account_name' => $account?->name,
            'source' => $account ? $source : null,
            'receipt_entry_id' => $source === 'shelter_receipt' ? $receipt?->id : null,
            'shelter_cleared' => $shelterCleared,
        ];
    }

    private function commissionProposal(Contract $contract): float
    {
        return round((float) CommissionBatchItem::query()
            ->where('source_type', CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT)
            ->where('source_id', $contract->id)
            ->whereNull('commission_reversal_transaction_id')
            ->value('commission_amount'), 2);
    }

    private function accountSnapshot(array $ids): array
    {
        return Account::query()->whereIn('id', $ids)->get()->map(fn ($a) => ['id' => $a->id, 'code' => $a->code, 'name' => $a->name])->values()->all();
    }

    private function settingsSnapshot(ContractReturnSettlement $settlement): array
    {
        return ['posting_mode' => 'manual_screen', 'accounts' => $settlement->lines->flatMap(fn ($line) => $line->account_snapshot ?? [])->values()->all()];
    }
}
