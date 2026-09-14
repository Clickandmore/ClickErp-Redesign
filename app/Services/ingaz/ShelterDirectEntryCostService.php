<?php

namespace App\Services\ingaz;

use App\Models\Contract;
use App\Models\ShelterCostEntry;
use App\Models\ingaz\Maid;
use App\Services\ingaz\ShelterAgentCostRecoveryService;
use App\Services\ingaz\ShelterCostLedgerService;
use App\Support\AutomaticJournalEntries;
use App\Support\NationalityAccountResolver;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConstraintDetails;
use Nami\Finance\Models\CostCenter;
use Nami\Finance\Services\YearService;

class ShelterDirectEntryCostService
{
    public function reconcile(int $id, bool $apply = true): string
    {
        return DB::transaction(fn () => $this->process($id, $apply, app(ShelterCostLedgerService::class)));
    }

    private function process(int $id, bool $apply, ShelterCostLedgerService $ledger): string
    {
        $contract = Contract::query()->lockForUpdate()->findOrFail($id);
        Maid::query()->whereKey($contract->maid_id)->lockForUpdate()->firstOrFail();
        $contract->load('info');
        if ($contract->status !== 'shelter' || !$contract->info || $contract->info->is_outside_shelter) {
            throw new \RuntimeException('العاملة ليست داخل الإيواء.');
        }
        $invoice = $contract->invoice()->lockForUpdate()->first();
        $contract->setRelation('invoice', $invoice);
        $supplier = $invoice?->externalOfficeCostConstraint()->lockForUpdate()->first();
        if (!$supplier || $invoice->external_office_cost_reversal_constraint_id) {
            throw new \RuntimeException('قيد تكلفة المورد مفقود أو معكوس؛ لن تُحسب التكلفة من الإرسالية.');
        }
        $mainBranch = app(\App\Services\BranchAccessService::class)->mainBranchId();
        foreach ([$invoice, $supplier] as $document) {
            if ((int) ($document->branch_id ?? $mainBranch) !== (int) ($contract->branch_id ?? $mainBranch)) {
                throw new \RuntimeException('فرع قيد المورد أو الفاتورة لا يطابق العقد.');
            }
        }
        $receipt = $ledger->findEntry($contract, ShelterCostEntry::KIND_CAPITALIZE_OFFICE_COST);
        if ($receipt && (int) $receipt->supplier_constraint_id === (int) $supplier->id && $receipt->constraint) {
            return 'سبق النقل — قيد الإيواء #' . ($receipt->constraint->code ?: $receipt->constraint_id) . '؛ لا تعديل.';
        }
        // Refuse historical cycles and receipts linked through other source types.
        if (ShelterCostEntry::where('maid_id', $contract->maid_id)->exists()
            || ShelterCostEntry::where('supplier_constraint_id', $supplier->id)->exists()) {
            throw new \RuntimeException('توجد حركات إيواء سابقة أو تكلفة منقولة؛ يلزم مطابقة الدفتر بدلاً من إنشاء تكلفة أخرى.');
        }
        $lines = $supplier->constraintDetails()->lockForUpdate()->get();
        $credits = $lines->where('creditor_value', '>', 0);
        $amount = round((float) $credits->sum('creditor_value'), 2);
        if ($credits->count() !== 1 || $amount <= 0
            || abs((float) $lines->sum('debtor_value') - $amount) > 0.009
            || $lines->contains(fn ($line) => (float) $line->debtor_value < 0 || (float) $line->creditor_value < 0)) {
            throw new \RuntimeException('قيد المورد غير متوازن أو حسابه الدائن غير محدد بشكل منفرد.');
        }
        $assetId = $ledger->assetAccountIdForContract($contract);
        $returnId = NationalityAccountResolver::forNationality($ledger->nationalityOf($contract), 'recruitment_cost_return');
        foreach ([$assetId, $returnId, $credits->first()->account_id] as $accountId) {
            if (!$accountId || !Account::whereKey($accountId)->whereDoesntHave('children')->exists()) {
                throw new \RuntimeException('حساب المورد أو أصل الإيواء أو مردودات التكلفة غير صالح للترحيل.');
            }
        }
        $centers = CostCenter::where('table_related_id', $contract->maid_id)->pluck('id');
        if (ConstraintDetails::where('account_id', $assetId)->where('debtor_value', '>', 0)
            ->where(function ($query) use ($contract, $centers) {
                $query->where('statement', 'like', '%' . $contract->id . '%')
                    ->orWhereIn('cost_center_id', $centers)
                    ->orWhereHas('constraint', fn ($q) => $q->where('statement', 'like', '%' . $contract->id . '%'));
            })->exists()) {
            throw new \RuntimeException('يوجد قيد أصل إيواء سابق؛ يلزم ربطه بالدفتر دون إعادة ترحيله.');
        }
        $date = (string) $contract->info->maid_date_back;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || !checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))
            || $date > now()->toDateString() || $date < substr((string) $supplier->date, 0, 10)) {
            throw new \RuntimeException('تاريخ دخول الإيواء غير صالح أو يسبق قيد المورد.');
        }
        if (!AutomaticJournalEntries::enabled() || !app(YearService::class)->checkDateRange($date)) {
            throw new \RuntimeException('يلزم تفعيل القيود الآلية وسنة مالية نشطة لتاريخ الدخول.');
        }
        $description = number_format($amount, 2) . ' ريال بتاريخ ' . $date . ' من قيد المورد #' . $supplier->code;
        if (!$apply) {
            return 'جاهز للنقل: ' . $description;
        }
        $result = $ledger->capitalizeOfficeCost($contract, $amount, $date, $contract,
            'نقل تكلفة المورد إلى الإيواء للعقد #' . $id . ' — تسوية دخول مباشر — مرجع قيد المورد #' . $supplier->code);
        // The CLI has no logged-in operator; preserve the original contract branch explicitly.
        $result['constraint']->update(['branch_id' => $contract->branch_id ?? $mainBranch]);
        $result['constraint']->constraintDetails()->update(['branch_id' => $contract->branch_id ?? $mainBranch]);
        $summary = app(ShelterAgentCostRecoveryService::class)->summary($contract->fresh());
        if ($summary['issues'] || $summary['available'] !== $amount || !($summary['lots'][$result['entry']->id]['valid'] ?? false)) {
            throw new \RuntimeException('فشل التحقق من تكلفة الاسترداد؛ تم التراجع عن النقل.');
        }
        return 'تم النقل: ' . $description . ' — قيد الإيواء #' . $result['constraint']->code;
    }
}
