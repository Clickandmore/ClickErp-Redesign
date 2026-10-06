<?php

namespace App\Services\ingaz;

use App\Models\ingaz\Invoice;
use App\Support\RecruitmentRevenueRecognition;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Constraint;

class LegacyInvoiceConstraintLinker
{
    public function inspect(Invoice $invoice, ?\Illuminate\Support\Collection $candidates = null): array
    {
        if (!$invoice->legacy_invoice_number) {
            return ['status' => 'not_legacy'];
        }
        $candidates ??= DB::table('fn_constraints')->whereNull('deleted_at')
            ->where(function ($query) use ($invoice) {
                $query->where('legacy_invoice_number', $invoice->legacy_invoice_number)
                    ->orWhere('statement', 'فاتورة: ' . $invoice->legacy_invoice_number);
            })->get();
        if ($candidates->count() !== 1) {
            return ['status' => $candidates->isEmpty() ? 'missing' : 'ambiguous'];
        }
        $source = $candidates->first();
        $base = ['constraint_id' => $source->id];
        if ($source->legacy_invoice_number && (int) $source->legacy_invoice_number !== (int) $invoice->legacy_invoice_number) {
            return $base + ['status' => 'identity_mismatch'];
        }
        if (DB::table('invoices')->where('invoice_constraint_id', $source->id)->where('id', '!=', $invoice->id)->exists()) {
            return $base + ['status' => 'source_already_linked'];
        }

        $headerDifferences = [];
        if (!$this->branchesMatch($source->branch_id, $invoice->branch_id)) {
            $headerDifferences['branch_id'] = ['invoice' => $invoice->branch_id, 'constraint' => $source->branch_id];
        }
        if ($source->date !== $invoice->created_at->toDateString()) {
            $headerDifferences['date'] = ['invoice' => $invoice->created_at->toDateString(), 'constraint' => $source->date];
        }
        if (abs((float) $source->value - (float) $invoice->total_due) > 0.009) {
            $headerDifferences['amount'] = ['invoice' => $invoice->total_due, 'constraint' => $source->value];
        }
        if ($headerDifferences) {
            return $base + ['status' => 'header_mismatch', 'differences' => $headerDifferences];
        }
        $lines = DB::table('fn_constraint_details')->where('constraint_id', $source->id)->whereNull('deleted_at')->get();
        $clientAccount = $invoice->contract?->user?->account_id ?: $invoice->client?->account_id;
        if (!$clientAccount || $lines->isEmpty()
            || $lines->contains(fn ($line) => !$this->branchesMatch($line->branch_id, $invoice->branch_id))
            || abs((float) $lines->sum('debtor_value') - (float) $invoice->total_due) > 0.009
            || abs((float) $lines->sum('creditor_value') - (float) $invoice->total_due) > 0.009
            || abs((float) $lines->where('account_id', $clientAccount)->sum('debtor_value') - (float) $invoice->total_due) > 0.009
            || (float) $lines->where('account_id', $clientAccount)->sum('creditor_value') !== 0.0) {
            return $base + ['status' => 'lines_mismatch'];
        }
        // The source accounts, not the current company setting, determine recognition.
        $income = false;
        $liability = false;
        foreach ($lines->where('creditor_value', '>', 0) as $line) {
            $accountId = $line->account_id;
            $seen = [];
            while ($accountId && !isset($seen[$accountId])) {
                $seen[$accountId] = true;
                $account = DB::table('fn_accounts')->where('id', $accountId)->first(['name', 'parent_id']);
                if (!$account) {
                    return $base + ['status' => 'account_missing'];
                }
                $income = $income || in_array($account->name, ['الإيرادات', 'الايرادات'], true);
                $liability = $liability || in_array($account->name, ['التزامات عقود', 'التزامات عقود الاستقدام'], true);
                $accountId = $account->parent_id;
            }
        }
        $mode = $income && !$liability ? RecruitmentRevenueRecognition::MODE_ON_INVOICE
            : ($liability && !$income ? RecruitmentRevenueRecognition::MODE_ON_ARRIVAL : null);
        if ($invoice->contract_id && !$mode) {
            return $base + ['status' => 'recognition_ambiguous'];
        }
        return $base + [
            'status' => $invoice->invoice_constraint_id && (int) $invoice->invoice_constraint_id !== (int) $source->id
                ? 'link_conflict' : ((int) $invoice->invoice_constraint_id === (int) $source->id ? 'linked' : 'ready'),
            'mode' => $mode,
        ];
    }

    private function branchesMatch(?int $sourceBranchId, ?int $invoiceBranchId): bool
    {
        if ($sourceBranchId === $invoiceBranchId) {
            return true;
        }
        // BranchScoped treats unassigned documents as belonging only to the main branch.
        // Keep historical branch fields intact; never match an explicit different branch.
        if ($sourceBranchId !== null && $invoiceBranchId !== null) {
            return false;
        }
        $mainBranchId = app(\App\Services\BranchAccessService::class)->mainBranchId();
        return $mainBranchId !== null && ($sourceBranchId ?? $invoiceBranchId) === $mainBranchId;
    }

    public function link(Invoice $invoice): Constraint
    {
        return DB::transaction(function () use ($invoice) {
            $invoice = Invoice::withoutGlobalScopes()->lockForUpdate()->findOrFail($invoice->id);
            $result = $this->inspect($invoice);
            if (!in_array($result['status'], ['ready', 'linked'], true)) {
                $details = [];
                $labels = ['branch_id' => 'الفرع', 'date' => 'التاريخ', 'amount' => 'المبلغ'];
                foreach ($result['differences'] ?? [] as $field => $values) {
                    $details[] = ($labels[$field] ?? $field) . ': الفاتورة ' . ($values['invoice'] ?? 'غير محدد')
                        . '، القيد ' . ($values['constraint'] ?? 'غير محدد');
                }
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'credit_note' => 'تعذر إنشاء إشعار دائن للفاتورة المنقولة #' . $invoice->legacy_invoice_number
                        . (isset($result['constraint_id']) ? ' مع القيد #' . $result['constraint_id'] : '')
                        . '. ' . ($details ? 'توجد اختلافات في البيانات: ' . implode('؛ ', $details) . '. ' : 'تعذر مطابقة القيد الأصلي (' . $result['status'] . '). ')
                        . 'راجع الفاتورة والقيد الأصلي وصحّح البيانات غير المطابقة قبل إعادة المحاولة.',
                ]);
            }
            $source = Constraint::withoutGlobalScopes()->whereNull('deleted_at')->lockForUpdate()->findOrFail($result['constraint_id']);
            $updates = ['invoice_constraint_id' => $source->id];
            if ($invoice->contract_id && $result['mode']) {
                $updates['revenue_recognition_mode'] = $result['mode'];
            }
            $invoice->fill($updates);
            if ($invoice->isDirty()) {
                $invoice->save();
            }
            return $source;
        });
    }
}
