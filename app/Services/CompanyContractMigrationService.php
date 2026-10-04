<?php

namespace App\Services;

use App\Models\CompanyProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CompanyContractMigrationService
{
    public function preview(CompanyProfile $company, array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);
        $users = DB::table('users')->whereIn('id', $ids)->orderBy('id')->get();
        if ($ids === [] || $users->count() !== count($ids) || $users->contains(fn ($user) => $user->archived_company_profile_id !== null)) {
            throw ValidationException::withMessages(['source_user_ids' => 'اختر سجلات موجودة لم يتم ترحيلها من قبل.']);
        }
        $contracts = DB::table('contracts')->whereIn('user_id', $ids)->orderBy('id')->get();
        if ($contracts->contains(fn ($contract) => $contract->company_profile_id !== null)) {
            throw ValidationException::withMessages(['source_user_ids' => 'توجد عقود مرتبطة بشركة بالفعل. راجع السجلات المختارة.']);
        }
        $contractIds = $contracts->pluck('id')->all();
        $transfers = Schema::hasColumn('sponsorship_transfers', 'old_company_profile_id')
            ? DB::table('sponsorship_transfers')->whereIn('old_sponsor_id', $ids)->orderBy('id')->get()
            : collect();
        if ($transfers->contains(fn ($transfer) => $transfer->old_company_profile_id !== null)) {
            throw ValidationException::withMessages(['source_user_ids' => 'توجد عمليات نقل خدمات مرتبطة بشركة بالفعل.']);
        }
        $related = [];
        foreach (['orders' => 'user_id', 'rental_contracts' => 'user_id', 'travel_histories' => 'user_id', 'invoices' => 'client_id'] as $table => $column) {
            if (Schema::hasColumn($table, $column)) {
                $related[$table] = DB::table($table)->whereIn($column, $ids)->count();
            }
        }
        if (Schema::hasColumn('invoices', 'contract_id')) {
            $related['contract_invoices'] = DB::table('invoices')->whereIn('contract_id', $contractIds)->count();
        }
        $accountIds = $users->pluck('account_id')->filter()->unique()->all();
        if (Schema::hasColumn('fn_constraint_details', 'account_id')) {
            $related['journal_lines'] = DB::table('fn_constraint_details')->whereIn('account_id', $accountIds)->count();
        }
        if (Schema::hasColumn('fn_bonds', 'debit_account_id')) {
            $related['bonds'] = DB::table('fn_bonds')->where(function ($query) use ($accountIds) {
                $query->whereIn('debit_account_id', $accountIds)->orWhereIn('credit_account_id', $accountIds);
            })->count();
        }
        if (Schema::hasTable('sponsorship_transfers')) {
            $related['sponsorship_transfers'] = DB::table('sponsorship_transfers')->where(function ($query) use ($ids) {
                $query->whereIn('old_sponsor_id', $ids)->orWhereIn('new_sponsor_id', $ids);
            })->count();
        }
        $preview = [
            'database' => DB::connection()->getDatabaseName(),
            'company_profile_id' => $company->id,
            'company_updated_at' => (string) $company->updated_at,
            'source_user_ids' => $ids,
            'sources' => $users->map(fn ($user) => [
                'id' => $user->id, 'name' => $user->name, 'account_id' => $user->account_id ?? null,
                'updated_at' => $user->updated_at ?? null, 'deleted_at' => $user->deleted_at,
            ])->all(),
            'contracts' => $contracts->map(fn ($contract) => [
                'id' => $contract->id, 'user_id' => $contract->user_id,
                'status' => $contract->status, 'branch_id' => $contract->branch_id ?? null,
                'updated_at' => $contract->updated_at ?? null,
            ])->all(),
            'related' => $related,
            'transfers' => $transfers->map(fn ($transfer) => [
                'id' => $transfer->id, 'contract_id' => $transfer->contract_id,
                'old_sponsor_id' => $transfer->old_sponsor_id,
                'updated_at' => $transfer->updated_at ?? null,
            ])->all(),
        ];
        $preview['fingerprint'] = hash('sha256', json_encode($preview));

        return $preview;
    }

    public function migrate(CompanyProfile $company, array $ids, string $fingerprint, ?int $adminId): int
    {
        return DB::transaction(function () use ($company, $ids, $fingerprint, $adminId) {
            $company = CompanyProfile::query()->lockForUpdate()->findOrFail($company->id);
            DB::table('users')->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            DB::table('contracts')->whereIn('user_id', $ids)->orderBy('id')->lockForUpdate()->get();
            if (Schema::hasColumn('sponsorship_transfers', 'old_company_profile_id')) {
                DB::table('sponsorship_transfers')->whereIn('old_sponsor_id', $ids)->orderBy('id')->lockForUpdate()->get();
            }
            $preview = $this->preview($company, $ids);
            if (!hash_equals($preview['fingerprint'], $fingerprint)) {
                throw ValidationException::withMessages(['migration' => 'تغيرت البيانات منذ المعاينة. أعد المعاينة قبل الترحيل.']);
            }
            $sources = collect($preview['sources'])->keyBy('id');
            foreach ($preview['contracts'] as $contract) {
                DB::table('contracts')->where('id', $contract['id'])->update([
                    'company_profile_id' => $company->id,
                    'legacy_company_user_id' => $contract['user_id'],
                    'company_finance_account_id' => $sources[$contract['user_id']]['account_id'],
                    'user_id' => null,
                    'updated_at' => now(),
                ]);
            }
            foreach ($preview['transfers'] as $transfer) {
                DB::table('sponsorship_transfers')->where('id', $transfer['id'])->update([
                    'old_company_profile_id' => $company->id,
                    'old_company_finance_account_id' => $sources[$transfer['old_sponsor_id']]['account_id'],
                ]);
            }
            // Archive only the explicitly reviewed sources. Keep all historical rows,
            // order ownership, invoice IDs, ledger accounts, and journal entries intact.
            DB::table('users')->whereIn('id', $ids)->update([
                'archived_company_profile_id' => $company->id,
                'deleted_at' => now(), 'updated_at' => now(),
            ]);
            $migrationId = DB::table('company_contract_migrations')->insertGetId([
                'company_profile_id' => $company->id, 'admin_id' => $adminId,
                'source_user_ids' => json_encode($preview['source_user_ids']),
                'contract_ids' => json_encode(array_column($preview['contracts'], 'id')),
                'preview' => json_encode($preview, JSON_UNESCAPED_UNICODE),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            return $migrationId;
        });
    }
}
