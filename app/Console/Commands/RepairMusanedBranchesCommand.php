<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class RepairMusanedBranchesCommand extends Command
{
    protected $signature = 'musaned:repair-branches
        {--database= : Required expected database name (guards against the wrong environment)}
        {--apply : Apply changes; otherwise only preview}
        {--transfer= : Inspect one transfer ID}';

    protected $description = 'Assign unambiguous Musaned transfers and linked accounting records to their document branch.';

    public function handle(): int
    {
        if (!$this->option('database') || $this->option('database') !== DB::connection()->getDatabaseName()) {
            $this->error('Pass --database with the exact name of the currently configured database.');
            return self::FAILURE;
        }

        $this->info($this->option('apply') ? 'APPLY: only NULL branch_id values will change.' : 'PREVIEW: no database changes.');
        $ids = DB::table('musaned_transfers')->when($this->option('transfer'), fn ($q, $id) => $q->where('id', $id))->orderBy('id')->pluck('id');
        $changed = $skipped = 0;
        foreach ($ids as $id) {
            try {
                $count = DB::transaction(function () use ($id) {
                    $plan = $this->plan((int) $id);
                    if ($this->option('apply') && $plan) {
                        // Persist the exact previous values before the first database update.
                        $dir = storage_path('app/musaned-branch-repairs');
                        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                            throw new RuntimeException('Cannot create audit directory.');
                        }
                        $path = $dir.'/'.date('Ymd-His').'-'.$id.'-'.bin2hex(random_bytes(6)).'.json';
                        $json = json_encode(['database' => DB::connection()->getDatabaseName(), 'transfer_id' => $id, 'changes' => $plan], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
                        if (file_put_contents($path, $json, LOCK_EX) === false) {
                            throw new RuntimeException('Cannot save audit file; no updates applied.');
                        }
                        foreach ($plan as $row) {
                            $affected = DB::table($row['table'])->where('id', $row['id'])->whereNull('branch_id')->update(['branch_id' => $row['branch_id']]);
                            if ($affected !== 1) {
                                throw new RuntimeException('Concurrent change; transfer transaction rolled back.');
                            }
                        }
                    }
                    return count($plan);
                });
                $changed += $count;
                $this->line("Transfer {$id}: {$count} records ".($this->option('apply') ? 'updated' : 'to update'));
            } catch (RuntimeException $e) {
                $skipped++;
                $this->warn("Transfer {$id}: SKIPPED - {$e->getMessage()}");
            }
        }
        $this->info("Records: {$changed}; skipped transfers: {$skipped}.");
        return $skipped ? self::FAILURE : self::SUCCESS;
    }

    private function plan(int $id): array
    {
        $transfer = DB::table('musaned_transfers')->where('id', $id)->lockForUpdate()->first();
        if (!$transfer) {
            throw new RuntimeException('Transfer no longer exists.');
        }
        $items = DB::table('musaned_transfer_items')->where('musaned_transfer_id', $id)->lockForUpdate()->get();
        if ($items->isEmpty()) {
            throw new RuntimeException('No linked items.');
        }
        $documents = [];
        $branches = [];
        foreach ($items as $item) {
            $sources = [];
            foreach (['contracts' => $item->contract_id, 'ingaz_delegations' => $item->ingaz_delegation_id, 'invoices' => $item->invoice_id] as $table => $sourceId) {
                if ($sourceId) {
                    $row = $this->document($table, (int) $sourceId);
                    $documents[$table.':'.$row->id] = [$table, $row];
                    if ($row->branch_id) {
                        $sources[] = (int) $row->branch_id;
                    }
                    // Check an invoice/delegation's contract as well, including inconsistent links.
                    if ($table !== 'contracts' && !empty($row->contract_id)) {
                        $contract = $this->document('contracts', (int) $row->contract_id);
                        $documents['contracts:'.$contract->id] = ['contracts', $contract];
                        if ($contract->branch_id) {
                            $sources[] = (int) $contract->branch_id;
                        }
                    }
                }
            }
            $sources = array_unique($sources);
            if (count($sources) !== 1) {
                throw new RuntimeException("Item {$item->id}: missing or conflicting document branches.");
            }
            $branches[] = reset($sources);
        }
        $branches = array_unique($branches);
        if (count($branches) !== 1) {
            throw new RuntimeException('Transfer contains multiple branches; manual split required.');
        }
        $branch = (int) reset($branches);
        if (!DB::table('branches')->where('id', $branch)->exists()) {
            throw new RuntimeException('Source branch does not exist.');
        }
        $rows = [['musaned_transfers', $transfer]];
        foreach ($items as $item) {
            $rows[] = ['musaned_transfer_items', $item];
        }
        $constraints = array_filter([$transfer->settlement_constraint_id, $transfer->bank_transfer_constraint_id]);
        foreach ($documents as [$table, $row]) {
            $rows[] = [$table, $row];
            if ($table === 'invoices') {
                foreach ((array) $row as $column => $value) {
                    if (str_ends_with($column, '_constraint_id') && $value) {
                        $constraints[] = (int) $value;
                    }
                }
            }
            if ($table === 'contracts') {
                foreach (DB::table('fn_bonds')->where('recruitment_contract_id', $row->id)->lockForUpdate()->get() as $bond) {
                    $rows[] = ['fn_bonds', $bond];
                    if ($bond->constraint_id) {
                        $constraints[] = (int) $bond->constraint_id;
                    }
                }
            }
        }
        foreach (array_unique($constraints) as $constraintId) {
            $constraint = $this->document('fn_constraints', (int) $constraintId);
            // Never reassign a constraint shared by a different transfer.
            if (DB::table('musaned_transfers')->where('id', '!=', $id)->where(function ($q) use ($constraintId) {
                $q->where('settlement_constraint_id', $constraintId)->orWhere('bank_transfer_constraint_id', $constraintId);
            })->exists()) {
                throw new RuntimeException("Constraint {$constraintId} is shared by another transfer.");
            }
            $rows[] = ['fn_constraints', $constraint];
            foreach (['fn_constraint_details', 'fn_constraint_logs', 'fn_constraint_files', 'fn_fiscal_year_closures'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id') && Schema::hasColumn($table, 'constraint_id')) {
                    foreach (DB::table($table)->where('constraint_id', $constraintId)->lockForUpdate()->get() as $detail) {
                        $rows[] = [$table, $detail];
                    }
                }
            }
        }
        $plan = [];
        foreach ($rows as [$table, $row]) {
            if ($row->branch_id !== null && (int) $row->branch_id !== $branch) {
                throw new RuntimeException("{$table} #{$row->id}: existing branch conflicts with source branch {$branch}.");
            }
            if ($row->branch_id === null) {
                $plan[$table.':'.$row->id] = ['table' => $table, 'id' => $row->id, 'old_branch_id' => null, 'branch_id' => $branch];
            }
        }
        return array_values($plan);
    }

    private function document(string $table, int $id): object
    {
        $row = DB::table($table)->where('id', $id)->lockForUpdate()->first();
        if (!$row) {
            throw new RuntimeException("Missing {$table} #{$id}.");
        }
        return $row;
    }
}
