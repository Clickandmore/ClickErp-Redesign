<?php

namespace App\Services\ingaz;

use App\Models\ingaz\Invoice;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Constraint;

class DelegationInvoiceJournalService
{
    public function ensure(Invoice $invoice, callable $create): ?Constraint
    {
        return DB::transaction(function () use ($invoice, $create) {
            $locked = Invoice::whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $matches = Constraint::where('statement', 'like',
                "قيد فاتورة تفويض إنجاز #{$locked->ingaz_delegation_id} / الفاتورة #{$locked->id} /%"
            )->lockForUpdate()->get();
            if ($matches->count() > 1) {
                throw new \RuntimeException('توجد قيود مكررة لهذه الفاتورة، يجب تسويتها قبل المتابعة.');
            }
            $journal = $locked->invoice_constraint_id
                ? Constraint::findOrFail($locked->invoice_constraint_id)
                : $matches->first();
            if ($journal && $matches->isNotEmpty() && $matches->first()->id !== $journal->id) {
                throw new \RuntimeException('رابط قيد الفاتورة لا يطابق القيد الموجود.');
            }
            if ($journal && abs((float) $journal->value - (float) $locked->total_due) > 0.01) {
                throw new \RuntimeException('قيمة قيد الفاتورة لا تطابق إجماليها.');
            }
            $journal ??= $create();
            if ($journal) {
                $locked->update(['invoice_constraint_id' => $journal->id]);
                $invoice->invoice_constraint_id = $journal->id;
                $invoice->setRelation('invoiceConstraint', $journal);
            }
            return $journal;
        });
    }
}
