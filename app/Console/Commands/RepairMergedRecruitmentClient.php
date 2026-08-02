<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\auth\Admin;
use App\Models\ingaz\User;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class RepairMergedRecruitmentClient extends Command
{
    protected $signature = 'recruitment-clients:repair-merged
        {user_id : The merged users.id value}
        {--apply : Apply the repair; without it the command is read-only}
        {--admin= : Admin ID recorded on repair-created finance rows}';

    protected $description = 'Split recruitment contracts that were merged into one client by unsafe phone matching';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');

        try {
            $plan = $this->buildPlan($userId);
            $this->renderPlan($plan);

            if (!$this->option('apply')) {
                $this->info('Dry run only. Re-run with --apply after reviewing this plan.');
                return self::SUCCESS;
            }

            $adminId = $this->resolveAdminId();
            $backupPath = $this->writeBackup($plan);
            $this->info("Backup written to storage/app/{$backupPath}");

            DB::transaction(function () use (&$plan, $adminId) {
                $this->applyPlan($plan, $adminId);
            }, 3);

            $this->verifyRepair($plan);
            $this->info('Merged recruitment client repaired and verified successfully.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            report($exception);

            return self::FAILURE;
        }
    }

    private function buildPlan(int $userId): array
    {
        $user = User::query()->find($userId);
        if (!$user) {
            throw new RuntimeException("Client #{$userId} does not exist.");
        }

        $contracts = Contract::query()
            ->where('user_id', $userId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'user_id', 'order_id', 'maid_id', 'created_at']);

        if ($contracts->count() < 2) {
            throw new RuntimeException('The client does not have multiple recruitment contracts to split.');
        }

        $contractActivities = DB::table('activity_log')
            ->where('subject_type', Contract::class)
            ->whereIn('subject_id', $contracts->pluck('id'))
            ->where('event', 'created')
            ->orderBy('id')
            ->get()
            ->keyBy('subject_id');

        $userActivities = DB::table('activity_log')
            ->where('subject_type', User::class)
            ->where('subject_id', $userId)
            ->orderBy('id')
            ->get();

        if ($contractActivities->count() !== $contracts->count() || $userActivities->isEmpty()) {
            throw new RuntimeException('The activity log is incomplete; refusing to infer client ownership.');
        }

        $state = [];
        $activityIndex = 0;
        $groups = [];
        $orderedActivities = $userActivities->values();

        foreach ($contractActivities as $contractId => $contractActivity) {
            while (
                isset($orderedActivities[$activityIndex])
                && $orderedActivities[$activityIndex]->id < $contractActivity->id
            ) {
                $properties = json_decode($orderedActivities[$activityIndex]->properties, true) ?: [];
                $state = array_replace($state, $properties['attributes'] ?? []);
                $activityIndex++;
            }

            $document = trim((string) ($state['national_num'] ?? ''));
            $name = trim((string) ($state['name'] ?? ''));
            if ($document === '' || $name === '') {
                throw new RuntimeException("Could not recover the client identity for contract #{$contractId}.");
            }

            $groups[$document] ??= [
                'document' => $document,
                'state' => $state,
                'contract_ids' => [],
                'user_id' => null,
                'account_id' => null,
                'finance_client_id' => null,
            ];
            $groups[$document]['contract_ids'][] = (int) $contractId;
        }

        $currentDocument = trim((string) $user->national_num);
        if (!isset($groups[$currentDocument])) {
            throw new RuntimeException('The current client identity does not own any recovered contract.');
        }

        $groups[$currentDocument]['user_id'] = $userId;

        foreach ($groups as $document => $group) {
            $existing = User::query()
                ->where('national_num', $document)
                ->where('id', '!=', $userId)
                ->first(['id']);
            if ($existing) {
                throw new RuntimeException(
                    "Recovered identity {$document} already belongs to client #{$existing->id}; manual review is required."
                );
            }
        }

        if (count($groups) < 2) {
            throw new RuntimeException('No distinct historical identities were recovered.');
        }

        return [
            'original_user_id' => $userId,
            'current_document' => $currentDocument,
            'shared_account_id' => $user->account_id ? (int) $user->account_id : null,
            'groups' => $groups,
            'contract_ids' => $contracts->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'order_ids' => $contracts->pluck('order_id')->filter()->map(fn ($id) => (int) $id)->all(),
        ];
    }

    private function renderPlan(array $plan): void
    {
        $rows = [];
        foreach ($plan['groups'] as $group) {
            $rows[] = [
                $group['user_id'] ?: 'new',
                $group['state']['name'],
                $group['document'],
                implode(',', $group['contract_ids']),
            ];
        }

        $this->table(['User', 'Recovered name', 'Identity', 'Contracts'], $rows);
        $this->line('Distinct recovered clients: ' . count($plan['groups']));
    }

    private function applyPlan(array &$plan, int $adminId): void
    {
        $originalUser = User::query()->lockForUpdate()->findOrFail($plan['original_user_id']);

        foreach ($plan['groups'] as $document => &$group) {
            if (!$group['user_id']) {
                $group['user_id'] = $this->createRecoveredUser($plan['original_user_id'], $group);
            }
        }
        unset($group);

        foreach ($plan['groups'] as $group) {
            foreach ($group['contract_ids'] as $contractId) {
                $contract = Contract::query()->lockForUpdate()->findOrFail($contractId);
                $oldUserId = (int) $contract->user_id;

                DB::table('contracts')->where('id', $contractId)->update([
                    'user_id' => $group['user_id'],
                    'updated_at' => now(),
                ]);

                if ($contract->order_id) {
                    DB::table('orders')->where('id', $contract->order_id)->update([
                        'user_id' => $group['user_id'],
                        'updated_at' => now(),
                    ]);
                }

                activity('data_repair')
                    ->performedOn($contract)
                    ->event('client_relinked')
                    ->withProperties([
                        'repair' => 'split_merged_recruitment_client',
                        'old_user_id' => $oldUserId,
                        'new_user_id' => $group['user_id'],
                        'recovered_document' => $group['document'],
                    ])
                    ->log('Recruitment contract client restored from activity history');
            }
        }

        $this->repairFinance($plan, $adminId, $originalUser);
    }

    private function createRecoveredUser(int $sourceUserId, array $group): int
    {
        $state = $group['state'];
        $firstContractId = min($group['contract_ids']);
        $phone = 'data-import-repair-' . $sourceUserId . '-' . $firstContractId;
        $columns = array_flip(DB::getSchemaBuilder()->getColumnListing('users'));

        $payload = Arr::only($state, [
            'client_category_id', 'client_type', 'name', 'first_name', 'last_name',
            'tax_number', 'license_number', 'email', 'user_type', 'status', 'is_blocked',
            'branch_id', 'nationality_id', 'job', 'birthdate', 'marital_status', 'city',
            'address', 'country', 'region', 'beds_num', 'rooms_num', 'family_num',
            'children', 'profession', 'monthly_income',
        ]);

        $payload = array_intersect_key($payload, $columns);
        $payload += [
            'phone_code' => '+966',
            'phone' => $phone,
            'phone2' => null,
            'national_num' => $group['document'],
            'password' => Hash::make(Str::random(64)),
            'register_from' => 'data_repair',
            'created_at' => DB::table('contracts')->where('id', $firstContractId)->value('created_at') ?: now(),
            'updated_at' => now(),
        ];

        if (isset($columns['source_phone'])) {
            $payload['source_phone'] = null;
        }
        if (isset($columns['source_phone2'])) {
            $payload['source_phone2'] = null;
        }
        if (isset($columns['notes'])) {
            $payload['notes'] = "Recovered from merged client #{$sourceUserId}; real phone requires verification.";
        }

        return (int) DB::table('users')->insertGetId($payload);
    }

    private function repairFinance(array &$plan, int $adminId, User $originalUser): void
    {
        $sharedAccountId = $plan['shared_account_id'];
        if (!$sharedAccountId) {
            return;
        }

        $sharedAccount = DB::table('fn_accounts')->lockForUpdate()->find($sharedAccountId);
        if (!$sharedAccount) {
            throw new RuntimeException("Shared finance account #{$sharedAccountId} is missing.");
        }

        $sharedOwnerDocument = null;
        foreach ($plan['groups'] as $document => $group) {
            if ($this->sameName($group['state']['name'] ?? '', $sharedAccount->name ?? '')) {
                $sharedOwnerDocument = $document;
                break;
            }
        }
        if (!$sharedOwnerDocument) {
            throw new RuntimeException('Could not identify the rightful owner of the shared finance account.');
        }

        DB::table('users')->where('id', $plan['original_user_id'])->update(['account_id' => null]);
        DB::table('users')->where('id', $plan['groups'][$sharedOwnerDocument]['user_id'])->update([
            'account_id' => $sharedAccountId,
        ]);
        $plan['groups'][$sharedOwnerDocument]['account_id'] = $sharedAccountId;

        $sharedFinanceClient = DB::table('fn_clients')->where('account_id', $sharedAccountId)->first();
        if ($sharedFinanceClient) {
            $owner = $plan['groups'][$sharedOwnerDocument];
            DB::table('fn_clients')->where('id', $sharedFinanceClient->id)->update(
                $this->financeClientPayload($owner, $sharedAccountId, $adminId, false)
            );
            $plan['groups'][$sharedOwnerDocument]['finance_client_id'] = (int) $sharedFinanceClient->id;
        }

        foreach ($plan['groups'] as $document => &$group) {
            $invoices = DB::table('invoices')
                ->whereIn('contract_id', $group['contract_ids'])
                ->whereNotNull('finance_sale_id')
                ->get();

            if ($invoices->isEmpty()) {
                continue;
            }

            if (!$group['account_id']) {
                $group['account_id'] = $this->createFinanceAccount($group, $sharedAccount, $adminId);
                DB::table('users')->where('id', $group['user_id'])->update(['account_id' => $group['account_id']]);
            }
            if (!$group['finance_client_id']) {
                $group['finance_client_id'] = (int) DB::table('fn_clients')->insertGetId(
                    $this->financeClientPayload($group, $group['account_id'], $adminId, true)
                );
            }

            foreach ($invoices as $invoice) {
                $sale = DB::table('fn_sales')->lockForUpdate()->find($invoice->finance_sale_id);
                if (!$sale) {
                    throw new RuntimeException("Finance sale #{$invoice->finance_sale_id} is missing.");
                }
                if ($sale->zatca_invoice_id) {
                    throw new RuntimeException("Sale #{$sale->id} already has a ZATCA invoice and needs manual correction.");
                }

                DB::table('fn_sales')->where('id', $sale->id)->update([
                    'client_id' => $group['finance_client_id'],
                    'debit_client_id' => $group['account_id'],
                    'updated_at' => now(),
                ]);

                // Statements use the Arabic preposition form "للعقد", so match the
                // stable numeric segment instead of the word boundary.
                $needle = '%#' . $invoice->contract_id . ' / الفاتورة #' . $invoice->id . '%';
                DB::table('fn_bonds')
                    ->where('statement', 'like', $needle)
                    ->where(function ($query) use ($sharedAccountId) {
                        $query->where('debit_account_id', $sharedAccountId)
                            ->orWhere('credit_account_id', $sharedAccountId);
                    })
                    ->get()
                    ->each(function ($bond) use ($group, $sharedAccountId, $invoice) {
                        $changes = ['updated_at' => now(), 'recruitment_contract_id' => $invoice->contract_id];
                        if ((int) $bond->debit_account_id === $sharedAccountId) {
                            $changes['debit_account_id'] = $group['account_id'];
                        }
                        if ((int) $bond->credit_account_id === $sharedAccountId) {
                            $changes['credit_account_id'] = $group['account_id'];
                        }
                        DB::table('fn_bonds')->where('id', $bond->id)->update($changes);
                    });

                DB::table('fn_constraint_details')
                    ->where('account_id', $sharedAccountId)
                    ->where('statement', 'like', $needle)
                    ->update(['account_id' => $group['account_id'], 'updated_at' => now()]);
            }
        }
        unset($group);
    }

    private function createFinanceAccount(array $group, object $template, int $adminId): int
    {
        $lastCode = DB::table('fn_accounts')
            ->where('parent_id', $template->parent_id)
            ->lockForUpdate()
            ->max('code');

        return (int) DB::table('fn_accounts')->insertGetId([
            'name' => $group['state']['name'],
            'operation_type' => $template->operation_type,
            'status' => $template->status,
            'statement_type' => $template->statement_type,
            'level' => $template->level,
            'type' => $template->type,
            'parent_id' => $template->parent_id,
            'code' => ((int) $lastCode) + 1,
            'created_by_id' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function financeClientPayload(
        array $group,
        int $accountId,
        int $adminId,
        bool $withCreatedAt
    ): array {
        $user = DB::table('users')->where('id', $group['user_id'])->first();
        $categoryId = DB::table('fn_client_categories')->value('id');

        $payload = [
            'name' => $user->name,
            'code' => (string) $user->id,
            'address' => $user->address ?: ' ',
            'phone' => ' ',
            'email' => $user->email ?: ' ',
            'city' => $user->city ?: ' ',
            'postal_zone' => ' ',
            'is_company' => 0,
            'tax_number' => $user->national_num,
            'days_credit_period' => 0,
            'credit_limit' => 0,
            'client_category_id' => $categoryId,
            'account_id' => $accountId,
            'status' => 1,
            'updated_at' => now(),
        ];

        if ($withCreatedAt) {
            $payload['created_by_id'] = $adminId;
            $payload['created_at'] = now();
        }

        return $payload;
    }

    private function verifyRepair(array $plan): void
    {
        foreach ($plan['groups'] as $group) {
            $contractCount = DB::table('contracts')
                ->whereIn('id', $group['contract_ids'])
                ->where('user_id', $group['user_id'])
                ->count();
            if ($contractCount !== count($group['contract_ids'])) {
                throw new RuntimeException("Post-repair contract verification failed for {$group['document']}.");
            }

            $wrongOrders = DB::table('orders')
                ->whereIn('id', DB::table('contracts')->whereIn('id', $group['contract_ids'])->pluck('order_id'))
                ->where('user_id', '!=', $group['user_id'])
                ->count();
            if ($wrongOrders > 0) {
                throw new RuntimeException("Post-repair order verification failed for {$group['document']}.");
            }
        }

        if (DB::table('contracts')->where('user_id', $plan['original_user_id'])->count()
            !== count($plan['groups'][$plan['current_document']]['contract_ids'])) {
            throw new RuntimeException('The original client still owns contracts belonging to other identities.');
        }
    }

    private function writeBackup(array $plan): string
    {
        $accountId = $plan['shared_account_id'];
        $invoiceRows = DB::table('invoices')->whereIn('contract_id', $plan['contract_ids'])->get();
        $saleIds = $invoiceRows->pluck('finance_sale_id')->filter()->values();

        $backup = [
            'created_at' => now()->toIso8601String(),
            'database' => DB::connection()->getDatabaseName(),
            'plan' => $plan,
            'users' => DB::table('users')->where('id', $plan['original_user_id'])->get(),
            'contracts' => DB::table('contracts')->whereIn('id', $plan['contract_ids'])->get(),
            'orders' => DB::table('orders')->whereIn('id', $plan['order_ids'])->get(),
            'invoices' => $invoiceRows,
            'finance_account' => $accountId ? DB::table('fn_accounts')->where('id', $accountId)->get() : [],
            'finance_clients' => $accountId ? DB::table('fn_clients')->where('account_id', $accountId)->get() : [],
            'finance_sales' => DB::table('fn_sales')->whereIn('id', $saleIds)->get(),
            'finance_bonds' => $accountId ? DB::table('fn_bonds')->where('debit_account_id', $accountId)
                ->orWhere('credit_account_id', $accountId)->get() : [],
            'constraint_details' => $accountId ? DB::table('fn_constraint_details')->where('account_id', $accountId)->get() : [],
            'activity_log' => DB::table('activity_log')
                ->where(function ($query) use ($plan) {
                    $query->where(function ($contractQuery) use ($plan) {
                        $contractQuery->where('subject_type', Contract::class)
                            ->whereIn('subject_id', $plan['contract_ids']);
                    })->orWhere(function ($userQuery) use ($plan) {
                        $userQuery->where('subject_type', User::class)
                            ->where('subject_id', $plan['original_user_id']);
                    });
                })->orderBy('id')->get(),
        ];

        $path = 'data-repairs/merged-recruitment-client-' . $plan['original_user_id']
            . '-' . now()->format('Ymd-His') . '.json';
        Storage::disk('local')->put(
            $path,
            json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
        @chmod(Storage::disk('local')->path($path), 0600);

        return $path;
    }

    private function resolveAdminId(): int
    {
        $adminId = (int) ($this->option('admin') ?: 0);
        if ($adminId && Admin::query()->whereKey($adminId)->exists()) {
            return $adminId;
        }

        $fallback = (int) Admin::query()->orderBy('id')->value('id');
        if (!$fallback) {
            throw new RuntimeException('No admin exists for finance audit fields.');
        }

        return $fallback;
    }

    private function sameName(string $first, string $second): bool
    {
        $normalize = static fn (string $value) => preg_replace('/\s+/u', ' ', trim($value));

        return $normalize($first) === $normalize($second);
    }
}
