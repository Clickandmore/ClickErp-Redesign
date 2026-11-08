<?php

namespace App\Console\Commands;

use App\Models\ingaz\Invoice;
use App\Services\ingaz\LegacyInvoiceConstraintLinker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;
use RuntimeException;

class RepairRecruitmentInvoiceLinks extends Command
{
    protected $signature = 'finance:repair-recruitment-invoice-links
        {--mode=links : links or duplicates}
        {--contract=* : Limit to contract IDs; required for duplicates}
        {--apply : Commit verified corrections; otherwise preview only}
        {--skip-blocked : In links mode only, leave unmatched invoices unchanged}
        {--database= : Required exact database name when applying}
        {--admin-id= : Required administrator ID when applying}
        {--date= : Required posting date YYYY-MM-DD for duplicate reversals}';

    protected $description = 'Preview or repair recruitment invoice links; reverse verified duplicate journals without deleting history';
    private const MARKER = 'recruitment_duplicate_repair_v1';

    public function handle(LegacyInvoiceConstraintLinker $linker, ConstraintService $journals, YearService $years): int
    {
        try {
            $mode = $this->option('mode');
            $apply = (bool) $this->option('apply');
            $contracts = $this->option('contract');
            if (!in_array($mode, ['links', 'duplicates'], true)) throw new RuntimeException('Invalid --mode.');
            foreach ($contracts as $id) {
                if (!ctype_digit((string) $id) || (int) $id < 1) throw new RuntimeException('Invalid --contract.');
            }
            if ($mode === 'duplicates' && !$contracts) throw new RuntimeException('Select explicit --contract IDs for duplicate repair.');
            $database = DB::connection()->getDatabaseName();
            $this->info('Database: ' . $database . ' | ' . ($apply ? 'APPLY' : 'PREVIEW (no changes)'));
            if ($apply && $this->option('database') !== $database) throw new RuntimeException('--database must exactly match the connected database.');
            $admin = $this->option('admin-id');
            if ($apply && (!ctype_digit((string) $admin) || (int) $admin < 1
                || !DB::table('admins')->where('id', $admin)->exists())) throw new RuntimeException('A valid --admin-id is required.');
            $date = $this->option('date');
            if ($mode === 'duplicates' && (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date)
                || !checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4)))) {
                throw new RuntimeException('A valid --date=YYYY-MM-DD is required for reversals.');
            }
            if ($mode === 'duplicates' && !$years->checkDateRange($date)) throw new RuntimeException('No open financial year for reversal date.');
            DB::transaction(function () use ($linker, $journals, $years, $mode, $apply, $contracts, $admin, $date) {
                $query = Invoice::withoutGlobalScopes()->whereNotNull('contract_id');
                if ($contracts) $query->whereIn('contract_id', $contracts);
                if ($mode === 'links') $query->whereNull('invoice_constraint_id')->whereNull('arrival_revenue_constraint_id');
                $invoices = $query->orderBy('id')->lockForUpdate()->get();
                if ($contracts && array_diff(array_map('intval', $contracts), $invoices->pluck('contract_id')->map(fn ($id) => (int) $id)->all()) && $mode === 'duplicates') {
                    throw new RuntimeException('One or more selected contracts have no invoice.');
                }
                $plans = [];
                foreach ($invoices as $invoice) {
                    $plan = $mode === 'links' ? $linker->inspect($invoice) : $this->duplicatePlan($invoice, $linker);
                    $this->line(json_encode(['invoice' => $invoice->id, 'contract' => $invoice->contract_id] + $plan, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                    if (!in_array($plan['status'], ['ready', 'ready_to_finalize', 'awaiting_approval', 'already_applied'], true)) {
                        if ($apply && !($mode === 'links' && $this->option('skip-blocked'))) throw new RuntimeException('Blocked invoice #' . $invoice->id . ': ' . $plan['status'] . '. Entire batch rolled back.');
                        continue;
                    }
                    if (in_array($plan['status'], ['ready', 'ready_to_finalize'], true)) $plans[] = [$invoice, $plan];
                }
                if ($apply) {
                    foreach ($plans as [$invoice, $plan]) {
                        if ($mode === 'links') {
                            $before = $invoice->getAttributes();
                            $source = $linker->link($invoice);
                            $this->audit($source->id, (int) $admin, 'recruitment_link_repair_v1', ['before' => $before, 'after' => $invoice->fresh()->getAttributes()]);
                        } elseif ($plan['status'] === 'ready_to_finalize') {
                            $this->finalizeDuplicate($invoice, $plan, (int) $admin);
                        } else {
                            $this->reverseDuplicate($invoice, $plan, $journals, $years, $date, (int) $admin);
                        }
                    }
                }
                $this->info(($apply ? 'Processed: ' : 'Ready: ') . count($plans) . ($apply ? ' (all changes commit together; pending reversals still require approval and a rerun).' : ' (nothing changed).'));
            });
            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    private function duplicatePlan(Invoice $invoice, LegacyInvoiceConstraintLinker $linker): array
    {
        // Restrict automatic reversal to the two independently audited incidents.
        $cases = [546 => [480, 2802, 6722, 3999], 610 => [502, 2858, 6713, 13995]];
        $case = $cases[$invoice->contract_id] ?? null;
        if (!$case || (int) $invoice->id !== $case[0] || abs((float) $invoice->total_due - $case[3]) > 0.009) return ['status' => 'not_audited'];
        $entries = DB::table('fn_constraints')->whereIn('code', [$case[1], $case[2]])->whereNull('deleted_at')->lockForUpdate()->get();
        if ($entries->count() !== 2) return ['status' => 'journal_count_mismatch'];
        $original = $entries->firstWhere('code', $case[1]);
        $duplicate = $entries->firstWhere('code', $case[2]);
        if (!$original || !$duplicate) return ['status' => 'journal_identity_mismatch'];
        foreach (\App\Support\ConstraintReferenceGuard::referencesFor((int) $duplicate->id) as $reference) {
            if ($reference['table'] !== 'invoices' || $reference['column'] !== 'invoice_constraint_id'
                || (int) $reference['id'] !== (int) $invoice->id) return ['status' => 'unexpected_duplicate_reference'];
        }
        if (DB::table('fn_constraints')->where('statement', $original->statement)->whereNull('deleted_at')->count() !== 2) return ['status' => 'unexpected_extra_journals'];
        $logs = DB::table('fn_constraint_logs')->where('constraint_id', $duplicate->id)->where('action', self::MARKER)->get();
        if ($logs->isNotEmpty()) {
            $metadata = json_decode($logs->first()->metadata, true);
            $reversal = isset($metadata['reversal_id']) ? DB::table('fn_constraints')->where('id', $metadata['reversal_id'])->whereNull('deleted_at')->lockForUpdate()->first() : null;
            $valid = $logs->count() === 1 && $reversal
                && (int) ($metadata['plan']['original_id'] ?? 0) === (int) $original->id
                && (int) ($metadata['plan']['duplicate_id'] ?? 0) === (int) $duplicate->id
                && in_array($reversal->approval_status, ['pending', 'approved'], true)
                && (!isset($metadata['reversal_header']) || $metadata['reversal_header'] == $this->reversalHeader($reversal))
                && $this->fingerprint($original->id) === $this->fingerprint($duplicate->id)
                && $this->fingerprint($duplicate->id, true) === $this->fingerprint($reversal->id);
            if (!$valid) return ['status' => 'repair_audit_mismatch'];
            if (($metadata['stage'] ?? 'completed') === 'completed') {
                return ['status' => $reversal->approval_status === 'approved' && (int) $invoice->invoice_constraint_id === (int) $original->id ? 'already_applied' : 'repair_audit_mismatch'];
            }
            if (($metadata['stage'] ?? null) !== 'prepared' || (int) $invoice->invoice_constraint_id !== (int) $duplicate->id) return ['status' => 'repair_audit_mismatch'];
            $copy = clone $invoice;
            $copy->invoice_constraint_id = null;
            if ($linker->inspect($copy, collect([$original]))['status'] !== 'ready') return ['status' => 'original_validation_failed'];
            return array_replace($metadata['plan'], ['status' => $reversal->approval_status === 'approved' ? 'ready_to_finalize' : 'awaiting_approval',
                'reversal_id' => $reversal->id, 'reversal_code' => $reversal->code]);
        }
        $prefix = 'قيد فاتورة طلب استقدام للعقد #' . $invoice->contract_id . ' / الفاتورة #' . $invoice->id . ' / مساند ';
        if (!str_starts_with($original->statement, $prefix) || $original->statement !== $duplicate->statement
            || $original->date !== $duplicate->date || $original->branch_id !== $duplicate->branch_id
            || $original->year_id !== $duplicate->year_id || $original->approval_status !== 'approved' || $duplicate->approval_status !== 'approved'
            || $original->created_at >= $duplicate->created_at || (int) $invoice->invoice_constraint_id !== (int) $duplicate->id
            || $invoice->arrival_revenue_constraint_id || abs((float) $duplicate->value - $case[3]) > 0.009
            || $this->fingerprint($original->id) !== $this->fingerprint($duplicate->id)) return ['status' => 'duplicate_fingerprint_mismatch'];
        $copy = clone $invoice;
        $copy->invoice_constraint_id = null;
        $verified = $linker->inspect($copy, collect([$original]));
        if ($verified['status'] !== 'ready') return ['status' => 'original_' . $verified['status']];
        $verifiedDuplicate = $linker->inspect($invoice, collect([$duplicate]));
        if ($verifiedDuplicate['status'] !== 'linked') return ['status' => 'duplicate_' . $verifiedDuplicate['status']];
        return ['status' => 'ready', 'original_id' => $original->id, 'duplicate_id' => $duplicate->id,
            'original_code' => $case[1], 'duplicate_code' => $case[2], 'amount' => $case[3], 'mode' => $verified['mode']];
    }

    private function fingerprint(int $id, bool $reverse = false): array
    {
        $lines = DB::table('fn_constraint_details')->where('constraint_id', $id)->whereNull('deleted_at')->lockForUpdate()->get();
        $result = $lines->map(function ($line) use ($reverse) {
            return [(int) $line->account_id, $line->branch_id, $line->cost_center_id,
                number_format((float) ($reverse ? $line->creditor_value : $line->debtor_value), 2, '.', ''),
                number_format((float) ($reverse ? $line->debtor_value : $line->creditor_value), 2, '.', '')];
        })->all();
        sort($result);
        return $result;
    }

    private function reverseDuplicate(Invoice $invoice, array $plan, ConstraintService $journals, YearService $years, string $date, int $admin): void
    {
        $year = $years->checkDateRange($date);
        if (!$year) throw new RuntimeException('No open financial year for reversal date.');
        $duplicate = DB::table('fn_constraints')->where('id', $plan['duplicate_id'])->first();
        $lines = DB::table('fn_constraint_details')->where('constraint_id', $duplicate->id)->whereNull('deleted_at')->orderBy('id')->lockForUpdate()->get();
        $statement = 'تصحيح تكرار قيد فاتورة الاستقدام #' . $invoice->id . ' / عكس القيد #' . $duplicate->code;
        $data = ['date' => $date, 'statement' => $statement, 'branch_id' => $duplicate->branch_id,
            'created_by_id' => $admin, 'value' => $duplicate->value, 'cost_center_required' => 'off',
            'account_id' => [], 'account_type' => [], 'debtor_value' => [], 'creditor_value' => [],
            'statement_account' => [], 'cost_center_id' => [], 'detail_branch_id' => []];
        foreach ($lines as $line) {
            $data['account_id'][] = $line->account_id;
            $data['account_type'][] = (float) $line->creditor_value > 0 ? 'debtor' : 'creditor';
            $data['debtor_value'][] = $line->creditor_value;
            $data['creditor_value'][] = $line->debtor_value;
            $data['statement_account'][] = $statement;
            $data['cost_center_id'][] = $line->cost_center_id;
            $data['detail_branch_id'][] = $line->branch_id;
        }
        $reversal = $journals->storeConstraint($data, $year);
        $journals->storeConstraintDetails($data['account_id'], $reversal->id, $data, $year->id, null, $date);
        $reversal = $reversal->fresh();
        if (!in_array($reversal->approval_status, ['pending', 'approved'], true)) throw new RuntimeException('Unexpected reversal approval status.');
        if ($this->fingerprint($duplicate->id, true) !== $this->fingerprint($reversal->id)) throw new RuntimeException('Reversal verification failed.');
        $before = $invoice->getAttributes();
        if ($reversal->approval_status === 'approved') {
            $invoice->update(['invoice_constraint_id' => $plan['original_id'], 'revenue_recognition_mode' => $plan['mode']]);
        }
        $header = DB::table('fn_constraints')->where('id', $reversal->id)->first();
        $this->audit($duplicate->id, $admin, self::MARKER, ['stage' => $reversal->approval_status === 'approved' ? 'completed' : 'prepared',
            'before' => $before, 'after' => $invoice->getAttributes(), 'reversal_id' => $reversal->id, 'reversal_header' => $this->reversalHeader($header), 'plan' => $plan]);
        $this->line(json_encode(['invoice' => $invoice->id, 'status' => $reversal->approval_status === 'approved' ? 'completed' : 'awaiting_approval',
            'reversal_id' => $reversal->id, 'reversal_code' => $header->code], JSON_UNESCAPED_UNICODE));
    }

    private function reversalHeader(object $row): array
    {
        return ['date' => $row->date, 'branch_id' => $row->branch_id, 'year_id' => $row->year_id,
            'value' => number_format((float) $row->value, 2, '.', ''), 'statement' => $row->statement];
    }

    private function finalizeDuplicate(Invoice $invoice, array $plan, int $admin): void
    {
        $log = DB::table('fn_constraint_logs')->where('constraint_id', $plan['duplicate_id'])->where('action', self::MARKER);
        $metadata = json_decode($log->first()->metadata, true, 512, JSON_THROW_ON_ERROR);
        $invoice->update(['invoice_constraint_id' => $plan['original_id'], 'revenue_recognition_mode' => $plan['mode']]);
        $metadata['stage'] = 'completed';
        $metadata['after'] = $invoice->getAttributes();
        $metadata['finalized_by'] = $admin;
        $log->update(['metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'updated_at' => now()]);
    }

    private function audit(int $journal, int $admin, string $action, array $metadata): void
    {
        DB::table('fn_constraint_logs')->insert(['constraint_id' => $journal, 'admin_id' => $admin,
            'branch_id' => DB::table('fn_constraints')->where('id', $journal)->value('branch_id'),
            'action' => $action, 'description' => 'تصحيح مدقق لربط فاتورة استقدام',
            'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now()]);
    }
}
