<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\ShelterAgentCostRecovery;
use App\Models\ShelterCostEntry;
use App\Models\ContractReturnSettlementLine;
use App\Models\ingaz\Maid;
use App\Support\AutomaticJournalEntries;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConstraintDetails;

class ShelterAgentCostRecoveryService
{
    public function __construct(protected ShelterCostLedgerService $ledger) {}

    /** S1 has no office line. The original supplier journal is the evidence, never today's office on the maid. */
    public function provenance(ShelterCostEntry $receipt): array
    {
        $contract = Contract::query()->find($receipt->contract_id);
        $invoice = $contract?->invoice;
        $supplierId = $receipt->supplier_constraint_id ?: $invoice?->external_office_cost_constraint_id;
        $officeId = $receipt->office_account_id;
        if (!$officeId && $supplierId) {
            $creditors = ConstraintDetails::query()->where('constraint_id', $supplierId)
                ->where('creditor_value', '>', 0)->get()->groupBy('account_id');
            if ($creditors->count() === 1 && round((float) $creditors->first()->sum('creditor_value'), 2) >= (float) $receipt->amount) {
                $officeId = (int) $creditors->keys()->first();
            }
        }
        $assetId = $receipt->asset_account_id;
        if (!$assetId && $receipt->constraint_id) {
            $details = ConstraintDetails::query()->where('constraint_id', $receipt->constraint_id)
                ->where('debtor_value', '>', 0)->get();
            // Legacy entry 9 includes client/VAT lines; select only a documented shelter asset.
            if ($contract) {
                try {
                    $expected = $this->ledger->assetAccountIdForContract($contract);
                    $detail = $details->firstWhere('account_id', $expected);
                    if ($detail && round((float) $detail->debtor_value, 2) >= (float) $receipt->amount) {
                        $assetId = $expected;
                    }
                } catch (\RuntimeException $e) {
                    // Unresolved history is reported, never inferred from the current office.
                }
            }
        }
        $settled = (bool) $invoice?->external_office_cost_reversal_constraint_id;
        $valid = $receipt->constraint_id && $supplierId && $officeId && $assetId && !$settled
            && Account::query()->whereKey($officeId)->whereDoesntHave('children')->exists()
            && Account::query()->whereKey($assetId)->whereDoesntHave('children')->exists();
        return ['office_account_id' => $officeId, 'asset_account_id' => $assetId,
            'supplier_constraint_id' => $supplierId, 'settled' => $settled, 'valid' => (bool) $valid];
    }

    /** Replay complete exits and trial returns so a sold/replaced cost cannot be recovered again. */
    public function summary(Contract $contract): array
    {
        $lots = []; $trials = []; $living = 0.0; $issues = []; $posted = 0.0; $recovered = 0.0;
        if (!Schema::hasTable('shelter_agent_cost_recoveries')) {
            return ['lots' => [], 'posted' => 0.0, 'recovered' => 0.0, 'available' => 0.0, 'living' => 0.0,
                'issues' => ['يلزم تطبيق تحديث قاعدة بيانات استرداد تكلفة الوكيل.'], 'settled' => false];
        }
        $recoveries = ShelterAgentCostRecovery::query()->where('maid_id', $contract->maid_id)->get()->keyBy('id');
        $refundLines = ContractReturnSettlementLine::query()->where('type', 'office_charge')
            ->whereHas('settlement', fn ($q) => $q->whereHas('contract', fn ($q) => $q->where('maid_id', $contract->maid_id)))
            ->get()->keyBy('id');
        $entries = $this->ledger->entriesFor((int) $contract->maid_id)->sortBy(function ($entry) {
            // Imported rows have new IDs: replay by original journal chronology.
            return sprintf('%s-%020d-%020d', substr((string) ($entry->constraint?->date ?: $entry->created_at), 0, 10), $entry->constraint_id ?: PHP_INT_MAX, $entry->id);
        });
        $supplierReceipts = [];
        foreach ($entries as $entry) {
            $amount = round((float) $entry->amount, 2);
            switch ($entry->kind) {
                case ShelterCostEntry::KIND_CAPITALIZE_OFFICE_COST:
                    $lots[$entry->id] = ['entry' => $entry, 'remaining' => $amount] + $this->provenance($entry);
                    $supplier = $lots[$entry->id]['supplier_constraint_id'];
                    if ($supplier && isset($supplierReceipts[$supplier])) {
                        $issues[] = 'تكلفة المورد نفسها مثبتة في أكثر من استلام؛ يلزم مراجعة التكرار.';
                    }
                    if ($supplier) { $supplierReceipts[$supplier] = true; }
                    $posted += $amount;
                    break;
                case ShelterCostEntry::KIND_LIVING_EXPENSE:
                case ShelterCostEntry::KIND_LIVING_EXPENSE_REVERSAL:
                    $living = round($living + $amount, 2);
                    if (!$this->livingEntryMatchesJournal($entry)) {
                        $issues[] = 'حركة إعاشة بالدفتر لا تطابق قيداً على أصل الإيواء؛ راجع المصروفات القديمة قبل التنفيذ.';
                    }
                    break;
                case ShelterCostEntry::KIND_POST_REFUND_RECOVERY:
                case ShelterCostEntry::KIND_POST_REFUND_REVERSAL:
                    $line = $refundLines->get($entry->source_id);
                    $receiptId = $line?->metadata['shelter_receipt_entry_id'] ?? null;
                    if (!$line || !isset($lots[$receiptId])) {
                        $issues[] = 'مرجع تحميل المكتب في مستند الاسترداد غير مكتمل.';
                        break;
                    }
                    $lots[$receiptId]['remaining'] = round($lots[$receiptId]['remaining'] + $amount, 2);
                    $recovered -= $amount;
                    break;
                case ShelterCostEntry::KIND_AGENT_RECOVERY:
                case ShelterCostEntry::KIND_AGENT_RECOVERY_REVERSAL:
                    $recovery = $recoveries->get($entry->source_id);
                    if (!$recovery || !isset($lots[$recovery->receipt_entry_id])) {
                        $issues[] = 'مرجع استرداد تكلفة الوكيل غير مكتمل.';
                        break;
                    }
                    $lots[$recovery->receipt_entry_id]['remaining'] = round($lots[$recovery->receipt_entry_id]['remaining'] + $amount, 2);
                    $recovered -= $amount;
                    break;
                case ShelterCostEntry::KIND_RELEASE_TO_TRIAL:
                case ShelterCostEntry::KIND_RELEASE_TO_SALE:
                case ShelterCostEntry::KIND_RELEASE_TO_DEPORTATION:
                case ShelterCostEntry::KIND_WRITE_OFF:
                case ShelterCostEntry::KIND_RETURN_TO_CUSTOMER:
                    $balance = round(array_sum(array_column($lots, 'remaining')) + $living, 2);
                    if (abs($balance + $amount) > 0.01) {
                        $issues[] = 'حركة خروج قديمة جزئية: يلزم مطابقة مكونات التكلفة قبل الاسترداد أو الترحيل.';
                    }
                    if ($entry->kind === ShelterCostEntry::KIND_RELEASE_TO_TRIAL) {
                        $trials[$entry->source_type . ':' . $entry->source_id] = [$lots, $living];
                    }
                    foreach ($lots as &$lot) { $lot['remaining'] = 0.0; } unset($lot);
                    $living = round($balance + $amount, 2);
                    break;
                case ShelterCostEntry::KIND_RETURN_FROM_TRIAL:
                    $trial = $trials[$entry->source_type . ':' . $entry->source_id] ?? null;
                    if (!$trial || abs(array_sum(array_column($trial[0], 'remaining')) + $trial[1] - $amount) > 0.01) {
                        $issues[] = 'رجوع من تجربة دون تفاصيل تكلفة مطابقة.';
                        break;
                    }
                    foreach ($trial[0] as $id => $returned) {
                        if (!isset($lots[$id])) { $lots[$id] = $returned; }
                        else { $lots[$id]['remaining'] = round($lots[$id]['remaining'] + $returned['remaining'], 2); }
                    }
                    $living = round($living + $trial[1], 2);
                    unset($trials[$entry->source_type . ':' . $entry->source_id]);
                    break;
                default:
                    $issues[] = 'توجد تسوية قديمة غير مصنفة بين تكلفة الوكيل والإعاشة.';
            }
        }
        // Old refund journals did not reduce the shelter ledger. Never charge
        // that outstanding ledger balance again without reconciling its source.
        foreach ($refundLines as $line) {
            if (!in_array($line->status, ['posted', 'paid'], true) || !$line->constraint_id || $line->reversal_constraint_id) continue;
            $receiptId = $line->metadata['shelter_receipt_entry_id'] ?? null;
            $matchesAsset = $receiptId && isset($lots[$receiptId]);
            if (!$matchesAsset) {
                $matchesAsset = collect($lots)->contains(fn ($lot) => (int) $lot['entry']->contract_id === (int) $line->settlement->contract_id
                    && (int) $lot['asset_account_id'] === (int) $line->credit_account_id);
            }
            if ($matchesAsset && !$entries->contains(fn ($entry) => $entry->kind === ShelterCostEntry::KIND_POST_REFUND_RECOVERY
                && $entry->source_type === ContractReturnSettlementLine::class && (int) $entry->source_id === (int) $line->id)) {
                $issues[] = 'سبق تحميل المكتب في مستند الاسترداد #' . $line->settlement_id
                    . ' دون تسوية دفتر الإيواء؛ يلزم مطابقة القيد #' . $line->constraint?->code . ' قبل الاسترداد أو الترحيل لمنع التكرار.';
            }
        }
        foreach ($lots as $lot) {
            if ($lot['remaining'] < -0.01) { $issues[] = 'رصيد تكلفة الوكيل سالب؛ يلزم مراجعة القيود.'; }
        }
        if ($living < -0.01) { $issues[] = 'رصيد الإعاشة سالب؛ يلزم مراجعة القيود.'; }
        $available = round(array_sum(array_column($lots, 'remaining')), 2);
        if (abs($available + $living - $this->ledger->balance((int) $contract->maid_id)) > 0.01) {
            $issues[] = 'مكونات التكلفة لا تطابق رصيد دفتر الإيواء.';
        }
        return ['lots' => $lots, 'posted' => round($posted, 2), 'recovered' => round($recovered, 2),
            'available' => $available, 'living' => $living, 'issues' => array_values(array_unique($issues)),
            'settled' => (bool) $contract->invoice?->external_office_cost_reversal_constraint_id];
    }

    private function livingEntryMatchesJournal(ShelterCostEntry $entry): bool
    {
        if (!$entry->constraint_id) { return false; }
        $contract = Contract::query()->find($entry->contract_id);
        if (!$contract) { return false; }
        try {
            $assetId = $entry->asset_account_id ?: $this->ledger->assetAccountIdForContract($contract);
        } catch (\RuntimeException $e) { return false; }
        $side = (float) $entry->amount < 0 ? 'creditor_value' : 'debtor_value';
        $details = ConstraintDetails::query()->where('constraint_id', $entry->constraint_id)->where('account_id', $assetId);
        if ($entry->source_type === \App\Models\ShelterLivingExpenseAllocation::class) {
            $allocation = \App\Models\ShelterLivingExpenseAllocation::query()->find($entry->source_id);
            if (!$allocation || !$allocation->cost_center_id) { return false; }
            $details->where('cost_center_id', $allocation->cost_center_id);
        }
        return round((float) $details->sum($side), 2) >= abs(round((float) $entry->amount, 2));
    }

    public function recover(Contract $contract, int $receiptId, string $date, string $reason, string $requestKey): ShelterAgentCostRecovery
    {
        return DB::transaction(function () use ($contract, $receiptId, $date, $reason, $requestKey) {
            Maid::query()->whereKey($contract->maid_id)->lockForUpdate()->firstOrFail();
            $contract = Contract::query()->with('info')->findOrFail($contract->id);
            $existing = ShelterAgentCostRecovery::query()->where('request_key', $requestKey)->first();
            if ($existing) {
                if ((int) $existing->contract_id !== (int) $contract->id || (int) $existing->receipt_entry_id !== $receiptId) {
                    throw new \RuntimeException('مرجع الطلب مستخدم لعملية أخرى.');
                }
                return $existing;
            }
            $this->assertInShelter($contract);
            $hasPostArrivalOfficeCharge = ContractReturnSettlementLine::query()
                ->where('type', 'office_charge')
                ->where('status', '!=', 'reversed')
                ->whereHas('settlement', fn ($query) => $query
                    ->where('contract_id', $contract->id)
                    ->where('status', '!=', 'cancelled'))
                ->exists();
            if ($hasPostArrivalOfficeCharge) {
                throw new \RuntimeException('تحميل المكتب مسجل ضمن مستند الاسترداد بعد الوصول؛ لا يمكن تكراره من استرداد تكلفة الوكيل. افتح مستند الاسترداد المرتبط.');
            }
            if (!AutomaticJournalEntries::enabled()) { throw new \RuntimeException('يلزم تفعيل القيود الآلية لتنفيذ الاسترداد.'); }
            $summary = $this->summary($contract);
            if ($summary['issues']) { throw new \RuntimeException(implode(' ', $summary['issues'])); }
            if ($summary['settled']) { throw new \RuntimeException('تمت تسوية تكلفة الوكيل بعكس قيد المورد.'); }
            $lot = $summary['lots'][$receiptId] ?? null;
            if (!$lot || $lot['remaining'] <= 0) { throw new \RuntimeException('لا تكلفة وكيل مثبتة ومتاحة للاسترداد.'); }
            if (!$lot['valid']) { throw new \RuntimeException('يلزم إثبات حساب المكتب وأصل الإيواء من القيود الأصلية؛ أو تمت تسوية التكلفة سابقاً.'); }
            // Recovery is for the original contract/cycle, never a different contract now using the same maid.
            if ((int) $lot['entry']->contract_id !== (int) $contract->id) { throw new \RuntimeException('التكلفة تخص عقداً آخر؛ افتح العقد الأصلي للمراجعة.'); }
            $latestDate = DB::table('shelter_cost_entries as e')->join('fn_constraints as c', 'c.id', '=', 'e.constraint_id')
                ->where('e.maid_id', $contract->maid_id)->max('c.date');
            if ($latestDate && $date < substr($latestDate, 0, 10)) { throw new \RuntimeException('تاريخ الاسترداد لا يسبق آخر حركة إيواء مثبتة.'); }
            if (trim($reason) === '') { throw new \RuntimeException('سبب الاسترداد مطلوب.'); }
            $receipt = $lot['entry'];
            $receipt->update(['office_account_id' => $lot['office_account_id'], 'asset_account_id' => $lot['asset_account_id'],
                'supplier_constraint_id' => $lot['supplier_constraint_id']]);
            $center = ConstraintDetails::query()->where('constraint_id', $receipt->constraint_id)
                ->where('account_id', $lot['asset_account_id'])->where('debtor_value', '>', 0)->value('cost_center_id');
            $recovery = ShelterAgentCostRecovery::query()->create([
                'contract_id' => $contract->id, 'maid_id' => $contract->maid_id, 'receipt_entry_id' => $receiptId,
                'request_key' => $requestKey, 'amount' => $lot['remaining'], 'date' => $date, 'reason' => trim($reason),
                'office_account_id' => $lot['office_account_id'], 'asset_account_id' => $lot['asset_account_id'],
                'cost_center_id' => $center, 'created_by_id' => auth('admin')->id(),
            ]);
            $constraint = $this->ledger->postAgentRecovery($contract, $recovery, $date, false);
            $recovery->update(['constraint_id' => $constraint->id]);
            return $recovery;
        });
    }

    public function reverse(Contract $contract, int $recoveryId, string $reason): ShelterAgentCostRecovery
    {
        return DB::transaction(function () use ($contract, $recoveryId, $reason) {
            Maid::query()->whereKey($contract->maid_id)->lockForUpdate()->firstOrFail();
            $recovery = ShelterAgentCostRecovery::query()->where('contract_id', $contract->id)->lockForUpdate()->findOrFail($recoveryId);
            if ($recovery->reversal_constraint_id) { return $recovery; }
            $this->assertInShelter($contract->fresh('info'));
            if (!AutomaticJournalEntries::enabled()) { throw new \RuntimeException('يلزم تفعيل القيود الآلية لتنفيذ العكس.'); }
            $entry = $this->ledger->findEntry($recovery, ShelterCostEntry::KIND_AGENT_RECOVERY);
            if (!$entry) { throw new \RuntimeException('حركة الاسترداد الأصلية غير موجودة.'); }
            // A later exit changes the downstream cost/revenue, even if trial subsequently failed.
            if (ShelterCostEntry::query()->where('maid_id', $contract->maid_id)->where('id', '>', $entry->id)
                ->whereIn('kind', [ShelterCostEntry::KIND_RELEASE_TO_TRIAL, ShelterCostEntry::KIND_RELEASE_TO_SALE,
                    ShelterCostEntry::KIND_RELEASE_TO_DEPORTATION, ShelterCostEntry::KIND_WRITE_OFF,
                    ShelterCostEntry::KIND_RETURN_TO_CUSTOMER])->exists()) {
                throw new \RuntimeException('لا يمكن العكس بعد خروج للتجربة أو البيع أو الترحيل أو التصفية؛ يلزم مراجعة العمليات اللاحقة.');
            }
            if (trim($reason) === '') { throw new \RuntimeException('سبب العكس مطلوب.'); }
            $recovery->reversal_reason = trim($reason);
            $constraint = $this->ledger->postAgentRecovery($contract, $recovery, now()->toDateString(), true);
            $recovery->fill(['reversal_constraint_id' => $constraint->id, 'reversed_at' => now(), 'reversed_by_id' => auth('admin')->id()])->save();
            return $recovery;
        });
    }

    private function assertInShelter(Contract $contract): void
    {
        if (!in_array($contract->type, ['recruitment', 'maid'], true) || $contract->status !== ContractStatusEnum::Shelter->value || $contract->info?->is_outside_shelter) {
            throw new \RuntimeException('العملية متاحة لعقد استقدام لعاملة موجودة داخل الإيواء فقط.');
        }
        if ($contract->deportationProcedure()->where('status', 'completed')->exists()) {
            throw new \RuntimeException('لا يمكن الاسترداد أو العكس بعد اكتمال الترحيل.');
        }
    }
}
