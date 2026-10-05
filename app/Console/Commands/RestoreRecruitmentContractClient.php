<?php

namespace App\Console\Commands;

use App\Models\Contract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class RestoreRecruitmentContractClient extends Command
{
    protected $signature = 'recruitment-contracts:restore-client
        {contract : Contract ID whose owner has been verified against the source document}
        {client : Existing correct users.id; no client is created or overwritten}
        {--database= : Required expected database name (does not switch connections)}
        {--from-client= : Required expected previous users.id}
        {--identity= : Required expected target national ID or tax number}
        {--reason= : Required explanation recorded in the audit and backup}
        {--apply : Apply the reviewed plan; default is a dry run}';

    protected $description = 'Restore one recruitment contract, its order and attributable finance rows to an existing client';

    public function handle(): int
    {
        try {
            if (!$this->option('database') || DB::connection()->getDatabaseName() !== $this->option('database')) {
                throw new RuntimeException('Database mismatch. Supply --database with the exact current database name.');
            }
            foreach (['from-client', 'identity', 'reason'] as $option) {
                if (trim((string) $this->option($option)) === '') {
                    throw new RuntimeException("--{$option} is required.");
                }
            }
            foreach ([$this->argument('contract'), $this->argument('client'), $this->option('from-client')] as $id) {
                if (!ctype_digit((string) $id) || (int) $id < 1) {
                    throw new RuntimeException('Contract and client IDs must be positive integers.');
                }
            }

            // Build the plan under the same locks used for application. Never apply a stale plan.
            DB::transaction(function () {
                $plan = $this->plan();
                $this->table(['Contract', 'Previous client', 'Correct client', 'Correct account'], [[
                    $plan['contract']->id,
                    $plan['source']->id . ': ' . $plan['source']->name,
                    $plan['target']->id . ': ' . $plan['target']->name,
                    $plan['target']->account_id ?: 'none',
                ]]);
                $this->line('Order: ' . ($plan['order']->id ?? 'none'));
                foreach (['invoices', 'sales', 'bonds', 'details'] as $key) {
                    $this->line($key . ': ' . $plan[$key]->pluck('id')->implode(', '));
                }
                if (!$this->option('apply')) {
                    $this->info('Dry run only. No records changed. Add --apply to apply this plan.');
                    return;
                }
                if ($this->isComplete($plan)) {
                    $this->info('Already restored. No records changed.');
                    return;
                }

                $backup = $this->backup($plan);
                $this->apply($plan);
                if (!$this->isComplete($plan)) {
                    throw new RuntimeException('Verification failed; all database changes have been rolled back.');
                }
                activity('data_repair')
                    ->performedOn(Contract::withoutGlobalScopes()->findOrFail($plan['contract']->id))
                    ->event('client_restored')
                    ->withProperties([
                        'old_user_id' => $plan['source']->id,
                        'new_user_id' => $plan['target']->id,
                        'reason' => $this->option('reason'),
                        'backup' => $backup,
                        'invoice_ids' => $plan['invoices']->pluck('id')->all(),
                        'bond_ids' => $plan['bonds']->pluck('id')->all(),
                        'constraint_detail_ids' => $plan['details']->pluck('id')->all(),
                    ])->log('Recruitment contract owner restored by console command');
                $this->line('Backup: storage/app/' . $backup);
            });
            if ($this->option('apply')) {
                $this->info('Restoration committed and verified. Other contracts and client profile data were not modified.');
            }
            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }

    private function plan(): array
    {
        $contractId = (int) $this->argument('contract');
        $sourceId = (int) $this->option('from-client');
        $targetId = (int) $this->argument('client');
        if ($sourceId === $targetId) {
            throw new RuntimeException('Previous and correct clients must be different.');
        }
        $contract = DB::table('contracts')->where('id', $contractId)->lockForUpdate()->first();
        $clients = DB::table('users')->whereIn('id', [$sourceId, $targetId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $source = $clients->get($sourceId);
        $target = $clients->get($targetId);
        if (!$contract || !$source || !$target) {
            throw new RuntimeException('Contract or client not found.');
        }
        if (!in_array((int) $contract->user_id, [$sourceId, $targetId], true) || !empty($contract->company_profile_id)) {
            throw new RuntimeException('Unexpected current owner or company-owned contract; refusing repair.');
        }
        if (!empty($target->deleted_at) || !empty($target->archived_company_profile_id)) {
            throw new RuntimeException('Correct client is deleted or archived.');
        }
        $identity = ($target->client_type ?? 'person') === 'company' ? $target->tax_number : $target->national_num;
        if (trim((string) $identity) !== trim((string) $this->option('identity'))) {
            throw new RuntimeException('Correct client identity does not match --identity.');
        }
        $order = $contract->order_id
            ? DB::table('orders')->where('id', $contract->order_id)->lockForUpdate()->first()
            : null;
        if ($contract->order_id && (!$order || !in_array((int) $order->user_id, [$sourceId, $targetId], true))) {
            throw new RuntimeException('Order is missing or belongs to an unexpected client.');
        }
        if ($order && DB::table('contracts')->where('order_id', $order->id)->where('id', '!=', $contractId)->exists()) {
            throw new RuntimeException('Order is shared by another contract; manual reconciliation is required.');
        }
        foreach (['recruitment_contract_refunds', 'recruitment_invoice_credit_notes'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->where('contract_id', $contractId)->exists()) {
                throw new RuntimeException('Refunds or credit notes exist; manual finance reconciliation is required.');
            }
        }

        $invoices = DB::table('invoices')->where('contract_id', $contractId)->orderBy('id')->lockForUpdate()->get();
        foreach ($invoices as $invoice) {
            if (!empty($invoice->client_id) && !in_array((int) $invoice->client_id, [$sourceId, $targetId], true)) {
                throw new RuntimeException('Invoice belongs to an unexpected direct client.');
            }
        }
        $sales = DB::table('fn_sales')->whereIn('id', $invoices->pluck('finance_sale_id')->filter())->orderBy('id')->lockForUpdate()->get();
        if ($sales->count() !== $invoices->pluck('finance_sale_id')->filter()->unique()->count()) {
            throw new RuntimeException('A linked finance sale is missing.');
        }
        foreach ($sales as $sale) {
            if (!empty($sale->zatca_invoice_id)) {
                throw new RuntimeException('A linked sale has a ZATCA document; refusing to rewrite issued invoice ownership.');
            }
            if (!in_array((int) $sale->debit_client_id, [(int) $source->account_id, (int) $target->account_id], true)) {
                throw new RuntimeException('Sale uses an unexpected debtor account.');
            }
            if (DB::table('invoices')->where('finance_sale_id', $sale->id)->whereNotIn('id', $invoices->pluck('id'))->exists()) {
                throw new RuntimeException('Sale is shared with another contract.');
            }
        }

        $accounts = array_values(array_filter([(int) $source->account_id, (int) $target->account_id]));
        $bonds = DB::table('fn_bonds')->where(function ($q) use ($accounts, $contractId) {
            $q->whereIn('debit_account_id', $accounts)->orWhereIn('credit_account_id', $accounts)
                ->orWhere('recruitment_contract_id', $contractId);
        })->orderBy('id')->lockForUpdate()->get()->filter(function ($bond) use ($contractId) {
            $matches = $this->mentionsContract($bond->statement, $contractId);
            if ($matches && !empty($bond->recruitment_contract_id) && (int) $bond->recruitment_contract_id !== $contractId) {
                throw new RuntimeException('Bond statement conflicts with its contract link.');
            }
            $belongs = (int) $bond->recruitment_contract_id === $contractId || $matches;
            if ($belongs) {
                $this->assertUnambiguousStatement($bond->statement, $contractId);
            }
            return $belongs;
        })->values();

        $constraintIds = $bonds->pluck('constraint_id')->merge($sales->pluck('constraint_id'));
        foreach ($invoices as $invoice) {
            foreach ((array) $invoice as $column => $value) {
                if (str_ends_with($column, '_constraint_id') && $value) {
                    if (DB::table('invoices')->where($column, $value)->whereNotIn('id', $invoices->pluck('id'))->exists()) {
                        throw new RuntimeException('Invoice journal is shared with another invoice.');
                    }
                    $constraintIds->push($value);
                }
            }
        }
        $details = DB::table('fn_constraint_details')->whereIn('account_id', $accounts)
            ->orderBy('id')->lockForUpdate()->get()->filter(function ($detail) use ($constraintIds, $contractId) {
                $belongs = $constraintIds->contains($detail->constraint_id) || $this->mentionsContract($detail->statement, $contractId);
                if ($belongs) {
                    $this->assertUnambiguousStatement($detail->statement, $contractId);
                }
                return $belongs;
            })->values();

        $financeClient = null;
        if ($sales->isNotEmpty() || $bonds->isNotEmpty() || $details->isNotEmpty()) {
            if (!$source->account_id || !$target->account_id || (int) $source->account_id === (int) $target->account_id) {
                throw new RuntimeException('Separate source and correct finance accounts are required.');
            }
            $accountRows = DB::table('fn_accounts')->whereIn('id', $accounts)->orderBy('id')->lockForUpdate()->get();
            if ($accountRows->count() !== 2) {
                throw new RuntimeException('A finance account is missing.');
            }
            $financeClients = DB::table('fn_clients')->where('account_id', $target->account_id)->lockForUpdate()->get();
            if ($financeClients->count() !== 1) {
                throw new RuntimeException('Correct account must have exactly one finance client.');
            }
            $financeClient = $financeClients->first();
            if (preg_replace('/\s+/u', ' ', trim($financeClient->name)) !== preg_replace('/\s+/u', ' ', trim($target->name))) {
                throw new RuntimeException('Correct finance client name does not match the correct customer.');
            }
            $targetAccount = $accountRows->firstWhere('id', $target->account_id);
            if (preg_replace('/\s+/u', ' ', trim($targetAccount->name)) !== preg_replace('/\s+/u', ' ', trim($target->name))) {
                throw new RuntimeException('Correct finance account name does not match the correct customer.');
            }
        }
        return compact('contract', 'source', 'target', 'order', 'invoices', 'sales', 'bonds', 'details', 'financeClient');
    }

    private function mentionsContract(?string $statement, int $contractId): bool
    {
        // Exact numeric boundary prevents matching contract #5140 while repairing #514.
        return preg_match('/(?:للعقد|العقد)\s*#' . $contractId . '(?=\s|\/|$)/u', (string) $statement) === 1;
    }

    private function assertUnambiguousStatement(?string $statement, int $contractId): void
    {
        preg_match_all('/(?:للعقد|العقد)\s*#(\d+)/u', (string) $statement, $matches);
        foreach ($matches[1] as $mentionedId) {
            if ((int) $mentionedId !== $contractId) {
                throw new RuntimeException('A finance row references another contract; refusing ambiguous reassignment.');
            }
        }
    }

    private function apply(array $plan): void
    {
        $target = $plan['target'];
        $sourceAccount = (int) $plan['source']->account_id;
        $this->updateRow('contracts', $plan['contract']->id, ['user_id' => $target->id]);
        if ($plan['order']) {
            $this->updateRow('orders', $plan['order']->id, ['user_id' => $target->id]);
        }
        foreach ($plan['invoices'] as $invoice) {
            if ($invoice->client_id) {
                $this->updateRow('invoices', $invoice->id, ['client_id' => $target->id]);
            }
        }
        foreach ($plan['sales'] as $sale) {
            $this->updateRow('fn_sales', $sale->id, ['client_id' => $plan['financeClient']->id, 'debit_client_id' => $target->account_id]);
        }
        foreach ($plan['bonds'] as $bond) {
            $changes = ['recruitment_contract_id' => $plan['contract']->id];
            foreach (['debit_account_id', 'credit_account_id'] as $column) {
                if ((int) $bond->{$column} === $sourceAccount) {
                    $changes[$column] = $target->account_id;
                }
            }
            $this->updateRow('fn_bonds', $bond->id, $changes);
        }
        foreach ($plan['details'] as $detail) {
            if ((int) $detail->account_id === $sourceAccount) {
                $this->updateRow('fn_constraint_details', $detail->id, ['account_id' => $target->account_id]);
            }
        }
    }

    private function updateRow(string $table, int $id, array $changes): void
    {
        DB::table($table)->where('id', $id)->update($changes + ['updated_at' => now()]);
    }

    private function isComplete(array $plan): bool
    {
        if ((int) DB::table('contracts')->where('id', $plan['contract']->id)->value('user_id') !== (int) $plan['target']->id) return false;
        if ($plan['order'] && (int) DB::table('orders')->where('id', $plan['order']->id)->value('user_id') !== (int) $plan['target']->id) return false;
        foreach ($plan['invoices'] as $invoice) {
            if ($invoice->client_id && (int) DB::table('invoices')->where('id', $invoice->id)->value('client_id') !== (int) $plan['target']->id) return false;
        }
        foreach ($plan['sales'] as $sale) {
            $row = DB::table('fn_sales')->find($sale->id);
            if ((int) $row->client_id !== (int) $plan['financeClient']->id || (int) $row->debit_client_id !== (int) $plan['target']->account_id) return false;
        }
        foreach ($plan['bonds'] as $bond) {
            $row = DB::table('fn_bonds')->find($bond->id);
            if ((int) $row->recruitment_contract_id !== (int) $plan['contract']->id) return false;
            foreach (['debit_account_id', 'credit_account_id'] as $column) {
                if ((int) $bond->{$column} === (int) $plan['source']->account_id && (int) $row->{$column} !== (int) $plan['target']->account_id) return false;
            }
        }
        foreach ($plan['details'] as $detail) {
            if ((int) DB::table('fn_constraint_details')->where('id', $detail->id)->value('account_id') !== (int) $plan['target']->account_id) return false;
        }
        return true;
    }

    private function backup(array $plan): string
    {
        $snapshot = [
            'database' => DB::connection()->getDatabaseName(),
            'created_at' => now()->toIso8601String(),
            'reason' => $this->option('reason'),
            'old_user_id' => $plan['source']->id,
            'new_user_id' => $plan['target']->id,
        ];
        foreach (['contract', 'order', 'invoices', 'sales', 'bonds', 'details'] as $key) {
            $snapshot[$key] = $plan[$key];
        }
        $path = 'data-repairs/contract-client-' . $plan['contract']->id . '-' . Str::uuid() . '.json';
        if (!Storage::disk('local')->put($path, json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
            throw new RuntimeException('Could not write backup; refusing to apply repair.');
        }
        @chmod(Storage::disk('local')->path($path), 0600);
        return $path;
    }
}
