<?php

namespace App\Services\commissions;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\RentalContract;
use App\Models\RentalContractExtension;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionBatch;
use App\Models\commissions\CommissionBatchItem;
use App\Models\auth\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecruitmentCommissionService
{
    public const ZERO_SOURCE = 'recruitment_returned_zero';
    public const SETTLEMENT_PENDING = 'pending';
    public const SETTLEMENT_DEDUCTED = 'deducted';
    public const SETTLEMENT_ZEROED = 'zeroed';

    public function __construct(
        private readonly CommissionAccrualService $accrualService
    ) {}

    public function pendingDeductions(int $marketerId, array $filters = []): \Illuminate\Support\Collection
    {
        $items = CommissionBatchItem::query()
            ->with('batch')
            ->whereHas('batch', fn ($query) => $query->where('marketer_id', $marketerId)
                ->where('status', CommissionBatch::STATUS_APPROVED))
            ->where(function ($query) {
                $query->whereIn('source_type', [CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT, CommissionOperationService::SOURCE_RENTAL_CONTRACT])
                    ->orWhere('source_type', 'like', CommissionOperationService::SOURCE_RENTAL_EXTENSION_PERIOD_PREFIX . '%');
            })
            ->where('commission_amount', '>', 0)
            ->whereNull('commission_reversal_transaction_id')
            ->orderBy('id')
            ->get();
        $contracts = Contract::query()->with(['info', 'maid', 'travelHistory', 'returnRequests', 'contract_status_logs', 'latestShelterLog'])
            ->whereIn('id', $items->where('source_type', CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT)->pluck('source_id'))->get()->keyBy('id');
        $extensions = RentalContractExtension::query()->whereIn('id', $items->filter(fn ($item) => $this->isRentalExtensionItem($item))->pluck('source_id'))
            ->get()->keyBy('id');
        $rentalIds = $items->where('source_type', CommissionOperationService::SOURCE_RENTAL_CONTRACT)->pluck('source_id')
            ->merge($extensions->pluck('rental_contract_id'))->unique();
        $rentals = RentalContract::query()->with(['financialAdjustments', 'info', 'extensions'])->whereIn('id', $rentalIds)->get()->keyBy('id');

        return $items->filter(function ($item) use ($contracts, $rentals, $extensions, $filters) {
            if ($this->isZeroed($item)) return false;
            $isRental = $this->isRentalItem($item);
            if (!$isRental && $item->source_type !== CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT) return false;
            if (!empty($filters['service_type']) && $filters['service_type'] !== ($isRental ? 'rental' : 'recruitment')) return false;
            $contractId = $this->isRentalExtensionItem($item)
                ? $extensions->get($item->source_id)?->rental_contract_id : $item->source_id;
            $contract = ($isRental ? $rentals : $contracts)->get($contractId);
            if (!$contract || !$this->isReturned($contract)) return false;
            $returnDate = $isRental
                ? (($contract->status === 'cancelled' ? $contract->closed_at : null) ?: $contract->financialAdjustments->filter(fn ($adjustment) =>
                    $adjustment->type === 'expense' && (float) $adjustment->amount > 0
                    && in_array($adjustment->context, ['closure', 'manual_credit_note', 'closure_correction'], true))
                    ->map(fn ($adjustment) => $adjustment->issue_date ?: $adjustment->created_at)->sort()->first())
                : ($contract->returnRequests->where('status', 'approved')->pluck('processed_at')->filter()->sort()->first()
                    ?: $contract->contract_status_logs->whereIn('status', [ContractStatusEnum::Refunded->value, ContractStatusEnum::Back->value])->pluck('date')->filter()->sort()->first());
            $item->setAttribute('return_date', $returnDate ? \Carbon\Carbon::parse($returnDate) : null);
            $item->setAttribute('deduction_contract_id', $contract->id);
            $item->setAttribute('deduction_reason', $this->deductionReason($contract));
            $terms = $isRental ? app(RentalCommissionReturnPolicy::class)->terms($item, $contract, $extensions->get($item->source_id)) : ['policy' => 'full'];
            if ($this->isRentalExtensionItem($item)) {
                $periodDate = $item->operation_date ?: ($terms['start_date'] ?? null);
                if (!$this->extensionMatchesDateFilter($periodDate, $filters)) return false;
            }
            $calculation = app(RentalCommissionReturnPolicy::class)->calculate(
                (float) $item->commission_amount, $terms,
                $isRental ? app(RentalCommissionReturnPolicy::class)->returnedAt($contract) : $item->return_date
            );
            $item->setAttribute('deduction_policy', $terms['policy']);
            $item->setAttribute('suggested_deduction', $calculation['amount']);
            $item->setAttribute('retained_commission', $calculation['retained']);
            $item->setAttribute('deduction_used_days', $calculation['used_days']);
            $item->setAttribute('deduction_period_days', $calculation['days']);
            $item->setAttribute('deduction_calculation_error', $calculation['error']);
            $warrantyContract = $this->contractWithResolvedWarranty($contract);
            $warrantyReferenceDate = $isRental ? $item->return_date : now();
            $item->setAttribute('return_warranty_status', $this->returnWarrantyStatus($warrantyContract, $warrantyReferenceDate));
            $item->setAttribute('return_warranty_unknown_reason', $this->warrantyUnknownReason($warrantyContract, $warrantyReferenceDate));
            $item->setAttribute('return_warranty_start', $warrantyContract->warranty_start_date?->format('Y-m-d'));
            $item->setAttribute('return_warranty_end', $warrantyContract->warranty_end_date?->format('Y-m-d'));
            $item->setAttribute('deduction_contract_key', ($isRental ? 'rental:' : 'recruitment:') . $contract->id);
            $item->setAttribute('deduction_contract_url', $isRental
                ? route('RentContract.show', $contract->id) : url('/admin/contractsDetails/' . $contract->id));
            return true;
        })->values();
    }

    private function extensionMatchesDateFilter($date, array $filters): bool
    {
        $from = $to = null;
        if (($filters['date_filter'] ?? '') === 'month_year' && !empty($filters['year'])) {
            $from = \Carbon\Carbon::create((int) $filters['year'], (int) ($filters['month'] ?? 0) ?: 1, 1)->startOfDay();
            $to = !empty($filters['month']) ? $from->copy()->endOfMonth() : $from->copy()->endOfYear();
        } elseif (($filters['date_filter'] ?? '') === 'range') {
            $from = !empty($filters['from_date']) ? \Carbon\Carbon::parse($filters['from_date'])->startOfDay() : null;
            $to = !empty($filters['to_date']) ? \Carbon\Carbon::parse($filters['to_date'])->endOfDay() : null;
        }
        if (!$from && !$to) return true;
        if (!$date) return false;
        $date = \Carbon\Carbon::parse($date);
        return (!$from || $date->gte($from)) && (!$to || $date->lte($to));
    }

    private function deductionReason(Contract|RentalContract $contract): string
    {
        $reasons = [];
        if ($contract instanceof RentalContract) {
            $refunds = $contract->financialAdjustments->filter(fn ($adjustment) =>
                $adjustment->type === 'expense' && (float) $adjustment->amount > 0
                && in_array($adjustment->context, ['closure', 'manual_credit_note', 'closure_correction'], true));
            if ($contract->status === 'cancelled' && !$refunds->contains('context', 'closure')) $reasons[] = 'إلغاء عقد التأجير';
            foreach ($refunds->groupBy('context') as $context => $adjustments) {
                $label = match ($context) {
                    'closure' => 'إنهاء عقد التأجير مع تسجيل استرداد للعميل بقيمة',
                    'manual_credit_note' => 'تسجيل إشعار دائن لصالح العميل بقيمة',
                    'closure_correction' => 'تصحيح إنهاء عقد التأجير وتسجيل استرداد للعميل بقيمة',
                    default => null,
                };
                if ($label) {
                    $reasons[] = $label . ' ' . number_format($adjustments->sum('amount'), 2) . ' ر.س';
                }
            }
        } else {
            $label = match ($this->recruitmentReturnStatus($contract)) {
                ContractStatusEnum::Refunded->value => 'استرداد قيمة عقد الاستقدام',
                ContractStatusEnum::Back->value => 'إرجاع عاملة عقد الاستقدام',
                default => null,
            };
            if ($label) $reasons[] = $label;
            foreach ($contract->returnRequests->where('status', 'approved') as $request) {
                $reasons[] = 'طلب إرجاع معتمد #' . $request->id
                    . (trim((string) $request->notes) !== '' ? ' — ' . trim($request->notes) : '');
            }
        }
        return implode('؛ ', $reasons);
    }

    private function returnWarrantyStatus(Contract|RentalContract $contract, ?\Carbon\Carbon $returnDate): string
    {
        if (!$returnDate || !$contract->warranty_start_date || !$contract->warranty_end_date) return 'unknown';
        $start = $contract->warranty_start_date->copy()->startOfDay();
        $end = $contract->warranty_end_date->copy()->endOfDay();
        if ($end->lt($start)) return 'unknown';
        return $returnDate->betweenIncluded($start, $end) ? 'inside' : 'outside';
    }

    private function contractWithResolvedWarranty(Contract|RentalContract $contract): Contract|RentalContract
    {
        if ($contract instanceof RentalContract) return $contract;
        $resolved = clone $contract;
        $travel = $contract->travelHistory;
        $candidates = [
            $travel?->status === 'completed' ? $travel->resolveArrivalDateTime() : null,
            $travel?->status === 'completed' ? $travel->arrived_at : null,
            $contract->info?->arrival_date,
            $contract->warranty_start_date,
        ];
        $arrival = null;
        foreach ($candidates as $candidate) {
            if (!$candidate) continue;
            try {
                $date = \Carbon\Carbon::parse($candidate);
                if ($date->year >= 1900) { $arrival = $date; break; }
            } catch (\Throwable $exception) {
                continue;
            }
        }
        $resolved->warranty_start_date = $arrival;
        $resolved->warranty_end_date = $arrival
            ? $arrival->copy()->addDays(\App\Support\AutomationSettings::warrantyPeriodDays()) : null;
        return $resolved;
    }

    private function warrantyUnknownReason(Contract|RentalContract $contract, ?\Carbon\Carbon $returnDate): ?string
    {
        $reasons = [];
        if (!$returnDate) $reasons[] = 'تاريخ الإرجاع غير مسجل';
        if ($contract instanceof Contract && !$contract->warranty_start_date) {
            $reasons[] = 'تاريخ الوصول غير مسجل؛ لا يمكن حساب فترة الضمان';
        } elseif (!$contract->warranty_start_date && !$contract->warranty_end_date) {
            $reasons[] = 'بداية ونهاية الضمان غير مسجلتين';
        } elseif (!$contract->warranty_start_date) {
            $reasons[] = 'بداية الضمان غير مسجلة';
        } elseif (!$contract->warranty_end_date) {
            $reasons[] = 'نهاية الضمان غير مسجلة';
        } elseif ($contract->warranty_end_date->copy()->endOfDay()->lt($contract->warranty_start_date->copy()->startOfDay())) {
            $reasons[] = 'تواريخ الضمان غير صحيحة: النهاية قبل البداية';
        }
        return $reasons ? implode('؛ ', $reasons) : null;
    }

    private function isRentalExtensionItem(CommissionBatchItem $item): bool
    {
        return str_starts_with((string) $item->source_type, CommissionOperationService::SOURCE_RENTAL_EXTENSION_PERIOD_PREFIX);
    }

    private function isRentalItem(CommissionBatchItem $item): bool
    {
        return $item->source_type === CommissionOperationService::SOURCE_RENTAL_CONTRACT || $this->isRentalExtensionItem($item);
    }

    public function isEligible(Contract $contract): bool
    {
        return ! $this->isReturned($contract);
    }

    public function isReturned(Contract|RentalContract $contract): bool
    {
        if ($contract instanceof RentalContract) {
            return $contract->status === 'cancelled' || $contract->financialAdjustments->contains(fn ($adjustment) =>
                $adjustment->type === 'expense' && (float) $adjustment->amount > 0
                && in_array($adjustment->context, ['closure', 'manual_credit_note', 'closure_correction'], true));
        }
        if ($this->recruitmentReturnStatus($contract) !== null) {
            return true;
        }

        if ($contract->relationLoaded('returnRequests')) {
            return $contract->returnRequests->contains('status', 'approved');
        }

        return $contract->returnRequests()->where('status', 'approved')->exists();
    }

    /** Shelter admission is a location change, not a cancellation of the recruitment contract. */
    private function recruitmentReturnStatus(Contract $contract): ?string
    {
        $returned = [ContractStatusEnum::Refunded->value, ContractStatusEnum::Back->value];
        if (in_array((string) $contract->status, $returned, true)) {
            return (string) $contract->status;
        }
        if ($contract->status !== ContractStatusEnum::Shelter->value) {
            return null;
        }
        // Admission can replace the status of an already cancelled contract.
        // Keep that explicit cancellation effective both during save and afterwards.
        $previous = $contract->wasChanged('status') ? $contract->getOriginal('status') : null;
        if (!in_array($previous, $returned, true)) {
            $previous = $contract->latestShelterLog?->previous_status;
        }
        return in_array($previous, $returned, true) ? $previous : null;
    }

    public function assertEligible(Contract $contract): void
    {
        if ($this->isEligible($contract)) {
            return;
        }

        throw ValidationException::withMessages([
            'operations' => 'عقد الاستقدام مسترد أو مرتجع، ولذلك لا يستحق عمولة.',
        ]);
    }

    public function settleReturnedContract(
        Contract $contract,
        ?int $adminId = null,
        ?string $reason = null
    ): ?CommissionBatchItem {
        return DB::transaction(function () use ($contract, $adminId, $reason) {
            $lockedContract = Contract::query()
                ->with(['order.details', 'user', 'invoice', 'info'])
                ->whereKey($contract->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedContract || $this->isEligible($lockedContract)) {
                return null;
            }

            $reason = trim((string) ($reason ?: $this->defaultReason($lockedContract)));
            $item = CommissionBatchItem::query()
                ->with('batch')
                ->where('source_type', CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT)
                ->where('source_id', $lockedContract->id)
                ->lockForUpdate()
                ->first();

            if ($item) {
                if (
                    $item->batch?->status === CommissionBatch::STATUS_APPROVED
                    && (float) $item->commission_amount > 0
                    && ! $item->commission_reversal_transaction_id
                    && ! $this->isZeroed($item)
                ) {
                    $item->update([
                        'commission_reversal_reason' => $reason,
                    ]);
                }

                return $item->fresh();
            }

            $marketerId = (int) ($lockedContract->marketer_id
                ?: $lockedContract->order?->resolveResponsibleAdminId());
            if ($marketerId <= 0) {
                return null;
            }

            $baseAmount = round((float) ($lockedContract->invoice?->total_due
                ?? $lockedContract->info?->contract_value
                ?? $lockedContract->contract_cost
                ?? 0), 2);

            $batch = CommissionBatch::query()->create([
                'marketer_id' => $marketerId,
                'total_requests' => 1,
                'total_base_amount' => $baseAmount,
                'total_commission' => 0,
                'status' => CommissionBatch::STATUS_APPROVED,
                'notes' => 'إقفال عمولة عقد مسترد/مرتجع بصفر - عقد #' . $lockedContract->id,
                'approved_by' => $adminId,
                'approved_at' => now(),
                'created_by' => $adminId,
            ]);

            return CommissionBatchItem::query()->create([
                'batch_id' => $batch->id,
                'source_type' => CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT,
                'source_id' => $lockedContract->id,
                'order_id' => $lockedContract->order_id,
                'contract_id' => $lockedContract->id,
                'operation_reference' => (string) $lockedContract->id,
                'client_name' => $lockedContract->user?->name,
                'operation_date' => $lockedContract->contract_date ?: $lockedContract->created_at,
                'service_type' => 'recruitment',
                'base_amount' => $baseAmount,
                'method' => 'fixed',
                'rate_or_amount' => 0,
                'commission_amount' => 0,
                'legacy_source' => self::ZERO_SOURCE,
                'commission_reversed_at' => now(),
                'commission_reversal_reason' => $reason,
            ]);
        });
    }

    public function resolveSettlement(
        CommissionBatchItem $item,
        string $decision,
        int $adminId,
        ?string $notes = null,
        ?float $amount = null,
        bool $returnConfirmed = false
    ): CommissionBatchItem {
        // $returnConfirmed: the caller (post-arrival refund document) has already verified the
        // return event (shelter/escape/deportation), which isReturned() does not count by itself.
        return DB::transaction(function () use ($item, $decision, $adminId, $notes, $amount, $returnConfirmed) {
            \App\Models\auth\Admin::query()->whereKey($item->batch?->marketer_id)->lockForUpdate()->firstOrFail();
            $lockedItem = CommissionBatchItem::query()
                ->with('batch')
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();
            $isRental = $this->isRentalItem($lockedItem);
            $contractId = $this->isRentalExtensionItem($lockedItem)
                ? RentalContractExtension::query()->whereKey($lockedItem->source_id)->lockForUpdate()->firstOrFail()->rental_contract_id
                : $lockedItem->source_id;
            $contract = ($isRental ? RentalContract::query() : Contract::query())
                ->whereKey($contractId)->lockForUpdate()->firstOrFail();

            if (
                (!$isRental && $lockedItem->source_type !== CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT)
                || (!$returnConfirmed && !$this->isReturned($contract))
                || $lockedItem->commission_reversal_transaction_id
                || $this->isZeroed($lockedItem)
                || (float) $lockedItem->commission_amount <= 0
                || $lockedItem->batch?->status !== CommissionBatch::STATUS_APPROVED
            ) {
                throw ValidationException::withMessages([
                    'settlement' => 'هذه العمولة ليست في حالة تسمح باعتماد تسوية استرداد.',
                ]);
            }

            if ($decision === self::SETTLEMENT_DEDUCTED || $decision === 'partial') {
                $deduction = $decision === 'partial' ? round((float) $amount, 2) : (float) $lockedItem->commission_amount;
                if ($isRental) {
                    $policy = app(RentalCommissionReturnPolicy::class);
                    $extension = $this->isRentalExtensionItem($lockedItem)
                        ? RentalContractExtension::query()->findOrFail($lockedItem->source_id) : null;
                    $terms = $policy->terms($lockedItem, $contract, $extension);
                    $calculation = $policy->calculate((float) $lockedItem->commission_amount, $terms, $policy->returnedAt($contract));
                    if ($calculation['error'] || $deduction > $calculation['amount']) {
                        throw ValidationException::withMessages(['amount' => $calculation['error']
                            ?: 'لا يجوز أن يتجاوز الخصم نصيب المدة غير المنفذة: ' . number_format($calculation['amount'], 2) . ' ريال.']);
                    }
                    if ($lockedItem->rental_commission_terms === null) $lockedItem->update(['rental_commission_terms' => $terms]);
                }
                if ($deduction <= 0 || $deduction > (float) $lockedItem->commission_amount
                    || ($decision === 'partial' && $deduction >= (float) $lockedItem->commission_amount)) {
                    throw ValidationException::withMessages(['amount' => 'مبلغ الخصم الجزئي يجب أن يكون أكبر من صفر وأقل من العمولة الأصلية.']);
                }
                $this->reverseItem(
                    $lockedItem,
                    $contract,
                    $adminId,
                    trim((string) ($notes ?: $lockedItem->commission_reversal_reason ?: $this->defaultReason($contract))),
                    $deduction
                );
            } elseif ($decision === 'policy') {
                if (!$isRental) {
                    throw ValidationException::withMessages(['settlement' => 'تسوية المدة المتبقية متاحة لعقود التأجير فقط.']);
                }
                $policy = app(RentalCommissionReturnPolicy::class);
                $extension = $this->isRentalExtensionItem($lockedItem)
                    ? RentalContractExtension::query()->findOrFail($lockedItem->source_id) : null;
                $terms = $policy->terms($lockedItem, $contract, $extension);
                $calculation = $policy->calculate((float) $lockedItem->commission_amount, $terms, $policy->returnedAt($contract));
                if ($calculation['error']) {
                    throw ValidationException::withMessages(['settlement' => $calculation['error']]);
                }
                if ($lockedItem->rental_commission_terms === null) $lockedItem->update(['rental_commission_terms' => $terms]);
                $reason = trim((string) $notes) ?: 'تسوية حسب سياسة عمولة التأجير';
                if ($terms['policy'] === 'remaining_days') {
                    $reason .= '؛ المنفذ ' . $calculation['used_days'] . '/' . $calculation['days']
                        . ' يوم؛ العمولة المحتفظ بها ' . number_format($calculation['retained'], 2) . ' ريال';
                }
                if ($calculation['amount'] > 0) {
                    $this->reverseItem($lockedItem, $contract, $adminId, $reason, $calculation['amount']);
                } else {
                    $decision = self::SETTLEMENT_ZEROED;
                    $notes = $reason;
                }
            } elseif ($decision === self::SETTLEMENT_ZEROED) {
                // Closing at zero does not debit the marketer's balance.
            } else {
                throw ValidationException::withMessages([
                    'settlement' => 'قرار تسوية العمولة غير صحيح.',
                ]);
            }

            $lockedItem->update([
                'commission_reversed_at' => $decision === self::SETTLEMENT_ZEROED ? now() : $lockedItem->commission_reversed_at,
                'commission_reversal_reason' => $decision === self::SETTLEMENT_ZEROED
                    ? '[ZEROED] ' . (trim((string) $notes) ?: 'أُقفلت عمولة العقد المسترد بصفر دون إنشاء مستحق على المسوق.')
                    : $lockedItem->commission_reversal_reason,
            ]);

            return $lockedItem->fresh();
        });
    }

    private function reverseItem(
        CommissionBatchItem $item,
        Contract|RentalContract $contract,
        ?int $adminId,
        string $reason,
        ?float $deductionAmount = null
    ): ?CommissionBalanceTransaction {
        $item->refresh();
        if ($item->commission_reversal_transaction_id) {
            return CommissionBalanceTransaction::query()->find($item->commission_reversal_transaction_id);
        }

        $marketerId = (int) $item->batch?->marketer_id;
        if ($marketerId <= 0) {
            throw new \RuntimeException('تعذر تحديد المسوق المرتبط بعمولة عقد الاستقدام لإجراء العكس.');
        }

        $credit = (float) CommissionBalanceTransaction::query()
            ->where('marketer_id', $marketerId)
            ->where('type', CommissionBalanceTransaction::TYPE_CREDIT)
            ->sum('amount');
        $debit = (float) CommissionBalanceTransaction::query()
            ->where('marketer_id', $marketerId)
            ->where('type', CommissionBalanceTransaction::TYPE_DEBIT)
            ->sum('amount');
        $amount = round($deductionAmount ?? (float) $item->commission_amount, 2);
        $reversalDate = now();
        if ($this->isRentalExtensionItem($item)) {
            $extension = RentalContractExtension::query()->findOrFail($item->source_id);
            $terms = app(RentalCommissionReturnPolicy::class)->terms($item, $contract, $extension);
            $reversalDate = \Carbon\Carbon::parse($item->operation_date ?: $terms['start_date']);
        }

        $transaction = CommissionBalanceTransaction::query()->create([
            'branch_id' => $item->batch?->branch_id,
            'marketer_id' => $marketerId,
            'type' => CommissionBalanceTransaction::TYPE_DEBIT,
            'amount' => $amount,
            'reference_type' => 'commission_reversal',
            'reference_id' => $item->id,
            'balance_after' => round($credit - $debit - $amount, 2),
            'notes' => 'عكس عمولة عقد ' . ($contract instanceof RentalContract ? 'إيجار' : 'استقدام') . ' #' . $contract->id
                . ($this->isRentalExtensionItem($item) ? ' - تمديد #' . $item->source_id : '') . ' - ' . $reason,
            'created_by' => $adminId,
        ]);

        $hasFinancialOrigin = (bool) ($item->batch?->accrual_constraint_id || $item->batch?->payable_account_id)
            || Admin::withTrashed()->whereKey($marketerId)
                ->whereNotNull('commission_payable_account_id')
                ->exists();
        if ($hasFinancialOrigin) {
            $actorId = (int) ($adminId ?: $item->batch?->approved_by ?: $item->batch?->created_by);
            if ($actorId <= 0) {
                throw ValidationException::withMessages([
                    'settlement' => 'تعذر تحديد منفذ عكس العمولة لإنشاء القيد المحاسبي.',
                ]);
            }
            $this->accrualService->createReversalForItem(
                $item,
                $amount,
                $actorId,
                $reason,
                $reversalDate
            );
        }

        $item->update([
            'commission_reversal_transaction_id' => $transaction->id,
            'commission_reversed_at' => now(),
            'commission_reversal_reason' => $reason,
        ]);

        app(CommissionDeductionJournalService::class)->post($transaction, $adminId, $reversalDate->toDateString());

        return $transaction;
    }

    private function defaultReason(Contract|RentalContract $contract): string
    {
        if ($contract instanceof RentalContract) return 'تسوية عمولة عقد إيجار ملغي أو له استرداد/إشعار دائن.';
        return match ((string) $contract->status) {
            ContractStatusEnum::Refunded->value => 'العقد مسترد ولا يستحق عمولة.',
            ContractStatusEnum::Back->value => 'العقد مرتجع ولا يستحق عمولة.',
            default => 'العقد في مسار استرداد بعد الوصول ولا يستحق عمولة.',
        };
    }

    public function isZeroed(CommissionBatchItem $item): bool
    {
        return $item->legacy_source === self::ZERO_SOURCE
            || (
                ! $item->commission_reversal_transaction_id
                && $item->commission_reversed_at !== null
                && str_starts_with((string) $item->commission_reversal_reason, '[ZEROED]')
            );
    }
}
