<?php

namespace App\Services\ingaz;

use App\Models\Contract;
use App\Models\ingaz\Invoice;
use Nami\Finance\Models\Constraint;

class LegacySupplierCostResolver
{
    /** Resolve only documented legacy order + supplier invoice identities, never today's office or dispatch price. */
    public function resolve(Contract $contract): ?Constraint
    {
        $number = (int) $contract->arabia_supplier_invoice_number;
        $order = (int) $contract->arabia_order_number;
        if (!$number || !$order) {
            return null;
        }
        $candidates = Constraint::query()->where(function ($query) use ($number) {
            $query->where('statement', 'like', '%' . $number . '%')
                ->orWhereHas('constraintDetails', fn ($q) => $q->where('statement', 'like', '%' . $number . '%'));
        })->with('constraintDetails')->get()->filter(function ($journal) use ($number, $order) {
            return collect([$journal->statement])->merge($journal->constraintDetails->pluck('statement'))
                ->contains(fn ($text) => preg_match('/إغلاق\s+طلب\s*:\s*' . $order . '(?!\d)/u', $text ?? '')
                    && preg_match('/فاتورة\s+رقم\s*:\s*' . $number . '(?!\d)/u', $text ?? ''));
        });
        if ($candidates->count() !== 1) {
            throw new \RuntimeException('تعذر تحديد قيد المورد القديم بشكل منفرد للفاتورة #' . $number . '؛ يلزم مطابقة القيود أو الإلغاء قبل الإضافة.');
        }
        $journal = $candidates->first();
        $main = app(\App\Services\BranchAccessService::class)->mainBranchId();
        $branch = (int) ($contract->branch_id ?? $main);
        $lines = $journal->constraintDetails;
        $credits = $lines->where('creditor_value', '>', 0);
        if ((int) ($journal->branch_id ?? $main) !== $branch
            || $lines->contains(fn ($line) => (int) ($line->branch_id ?? $main) !== $branch)
            || $credits->count() !== 1 || (float) $credits->sum('creditor_value') <= 0
            || abs((float) $lines->sum('debtor_value') - (float) $credits->sum('creditor_value')) > 0.009
            || $lines->contains(fn ($line) => (float) $line->debtor_value < 0 || (float) $line->creditor_value < 0)
            || collect([$journal->statement])->merge($lines->pluck('statement'))->contains(fn ($text) => preg_match('/إلغاء|الغاء|عكس/u', $text ?? ''))
            || Invoice::withoutGlobalScopes()->where('external_office_cost_constraint_id', $journal->id)
                ->where('contract_id', '!=', $contract->id)->exists()) {
            throw new \RuntimeException('قيد المورد القديم لا يجتاز مطابقة الفرع والمبلغ والارتباط؛ لن ينشأ قيد جديد.');
        }
        return $journal;
    }
}
