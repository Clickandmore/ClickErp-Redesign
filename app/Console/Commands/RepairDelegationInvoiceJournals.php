<?php

namespace App\Console\Commands;

use App\Models\ingaz\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\ConstraintService;

class RepairDelegationInvoiceJournals extends Command
{
    protected $signature = 'finance:repair-delegation-journals {--apply}';
    protected $description = 'Audit delegation invoice links and repair the verified duplicate for invoice 8306';

    public function handle(): int
    {
        return DB::transaction(function () {
            $plan = [];
            foreach (Invoice::whereNotNull('ingaz_delegation_id')->orderBy('id')->lockForUpdate()->get() as $invoice) {
                $journals = Constraint::without('admin')->where('statement', 'like',
                    "قيد فاتورة تفويض إنجاز #{$invoice->ingaz_delegation_id} / الفاتورة #{$invoice->id} /%"
                )->orderBy('id')->lockForUpdate()->get();
                if ($journals->isEmpty()) continue;
                $original = $journals->first();
                $accountId = $invoice->ingazDelegation?->user?->account_id;
                foreach ($journals as $journal) {
                    $lines = DB::table('fn_constraint_details')->where('constraint_id', $journal->id)->whereNull('deleted_at')->get();
                    if (!$accountId || abs($lines->where('account_id', $accountId)->sum('debtor_value') - $invoice->total_due) > 0.01
                        || abs($lines->sum('debtor_value') - $invoice->total_due) > 0.01
                        || abs($lines->sum('creditor_value') - $invoice->total_due) > 0.01) {
                        throw new \RuntimeException("Invoice {$invoice->id}: journal totals do not match; no changes applied.");
                    }
                }
                $duplicate = null;
                if ($journals->count() > 1) {
                    if ($invoice->id != 8306 || $journals->count() !== 2 || $original->code != 25221
                        || $journals->last()->code != 25230 || $journals->last()->approval_status !== 'pending') {
                        throw new \RuntimeException("Invoice {$invoice->id}: unexpected duplicate; manual review required.");
                    }
                    $duplicate = $journals->last();
                    $signature = fn ($id) => DB::table('fn_constraint_details')->where('constraint_id', $id)->whereNull('deleted_at')
                        ->orderBy('account_id')->orderBy('debtor_value')->get(['account_id', 'cost_center_id', 'debtor_value', 'creditor_value'])->toJson();
                    if ($signature($original->id) !== $signature($duplicate->id)) throw new \RuntimeException('Duplicate lines differ.');
                }
                if ($invoice->invoice_constraint_id && !in_array($invoice->invoice_constraint_id, $journals->pluck('id')->all())) {
                    throw new \RuntimeException("Invoice {$invoice->id}: unexpected existing link.");
                }
                if ($invoice->invoice_constraint_id != $original->id || $duplicate) {
                    $plan[] = ['invoice_id' => $invoice->id, 'old_link' => $invoice->invoice_constraint_id,
                        'original_id' => $original->id, 'original_code' => $original->code,
                        'duplicate_id' => $duplicate?->id, 'duplicate_code' => $duplicate?->code,
                        'amount' => $invoice->total_due];
                }
            }
            $this->info(json_encode(['apply' => (bool) $this->option('apply'), 'changes' => count($plan), 'plan' => $plan], JSON_UNESCAPED_UNICODE));
            if (!$this->option('apply') || !$plan) return 0;
            $path = storage_path('app/delegation-journal-repair-' . now()->format('Ymd-His') . '.json');
            if (file_put_contents($path, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
                throw new \RuntimeException('Unable to write repair audit.');
            }
            $service = app(ConstraintService::class);
            foreach ($plan as $change) {
                Invoice::whereKey($change['invoice_id'])->update(['invoice_constraint_id' => $change['original_id']]);
                $service->logConstraintActivity(Constraint::findOrFail($change['original_id']), 'invoice_link_repaired',
                    'تصحيح رابط قيد فاتورة تفويض إنجاز ومنع تكرار القيد عند الإلغاء', $change);
                if ($change['duplicate_id']) $service->deleteConstraint($change['duplicate_id']);
            }
            $this->info('Applied; audit: ' . $path);
            return 0;
        });
    }
}
