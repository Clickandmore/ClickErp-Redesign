<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RepairInvoiceBranchesCommand extends Command
{
    protected $signature = 'invoices:repair-branches
        {--database= : Exact expected name of the configured database}
        {--invoice= : Limit review to one invoice ID}
        {--apply : Apply corrections; the default is a read-only preview}';

    protected $description = 'Correct recruitment invoice branches from their contracts or Ingaz delegations.';

    public function handle(): int
    {
        $database = DB::connection()->getDatabaseName();
        if (!$this->option('database') || $this->option('database') !== $database) {
            $this->error('Pass --database with the exact configured database name.');
            return self::FAILURE;
        }
        if ($this->option('invoice') !== null && !ctype_digit((string) $this->option('invoice'))) {
            $this->error('--invoice must be a numeric invoice ID.');
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'APPLY: correcting invoice branch_id only.' : 'PREVIEW: no database changes.');
        $query = DB::table('invoices')->where(function ($q) {
            $q->whereNotNull('contract_id')->orWhereNotNull('ingaz_delegation_id');
        })->when($this->option('invoice') !== null, fn ($q) => $q->where('id', $this->option('invoice')));
        $changed = $unchanged = $skipped = 0;
        $runId = date('Ymd-His').'-'.bin2hex(random_bytes(6));
        foreach ($query->orderBy('id')->pluck('id') as $id) {
            try {
                $result = DB::transaction(function () use ($id, $apply, $database, $runId) {
                    $invoice = DB::table('invoices')->where('id', $id)->lockForUpdate()->first();
                    if (!$invoice) {
                        throw new RuntimeException('Invoice no longer exists.');
                    }
                    // Other invoice workflows have their own source of branch ownership.
                    if (!empty($invoice->rental_transfer_contract_id) || !empty($invoice->sponsorship_transfer_id)) {
                        throw new RuntimeException('Invoice also belongs to a rental/sponsorship workflow; review manually.');
                    }
                    $sources = [];
                    foreach (['contracts' => $invoice->contract_id, 'ingaz_delegations' => $invoice->ingaz_delegation_id] as $table => $sourceId) {
                        if (!$sourceId) {
                            continue;
                        }
                        $source = DB::table($table)->where('id', $sourceId)->lockForUpdate()->first();
                        if (!$source || !empty($source->deleted_at) || !$source->branch_id) {
                            throw new RuntimeException("{$table} #{$sourceId} is missing, deleted, or has no branch.");
                        }
                        $sources[] = ['table' => $table, 'id' => $sourceId, 'branch_id' => (int) $source->branch_id];
                    }
                    $branches = array_unique(array_column($sources, 'branch_id'));
                    if (count($branches) !== 1) {
                        throw new RuntimeException('Contract and delegation branches disagree.');
                    }
                    $branchId = (int) reset($branches);
                    if (!DB::table('branches')->where('id', $branchId)->exists()) {
                        throw new RuntimeException("Branch {$branchId} does not exist.");
                    }
                    if ((int) $invoice->branch_id === $branchId) {
                        return null;
                    }
                    $change = ['database' => $database, 'invoice_id' => $id, 'old_branch_id' => $invoice->branch_id,
                        'new_branch_id' => $branchId, 'sources' => $sources];
                    if ($apply) {
                        $dir = storage_path('app/invoice-branch-repairs/'.$runId);
                        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                            throw new RuntimeException('Cannot create audit directory.');
                        }
                        // Save before-images before any update. A file records intent, not proof of commit.
                        $json = json_encode($change, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                        if (file_put_contents($dir.'/'.$id.'.json', $json, LOCK_EX) !== strlen($json)) {
                            throw new RuntimeException('Cannot save audit file; invoice not changed.');
                        }
                        DB::table('invoices')->where('id', $id)->update(['branch_id' => $branchId]);
                    }
                    return $change;
                });
                if ($result === null) {
                    $unchanged++;
                } else {
                    $changed++;
                    $old = $result['old_branch_id'] ?? 'NULL';
                    $this->line("Invoice {$id}: {$old} -> {$result['new_branch_id']}".($apply ? ' UPDATED' : ' planned'));
                }
            } catch (RuntimeException $e) {
                $skipped++;
                $this->warn("Invoice {$id}: SKIPPED - {$e->getMessage()}");
            }
        }
        $this->info("Changes: {$changed}; already correct: {$unchanged}; skipped: {$skipped}.");
        if ($apply && $changed) {
            $this->line('Audit: '.storage_path('app/invoice-branch-repairs/'.$runId));
        }
        return $skipped ? self::FAILURE : self::SUCCESS;
    }
}
