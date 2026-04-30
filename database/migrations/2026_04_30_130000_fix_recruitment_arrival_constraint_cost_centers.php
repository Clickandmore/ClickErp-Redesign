<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const GENERAL_COST_CENTER_TYPE = 'fn_cost_centers';

    public function up(): void
    {
        if (
            !Schema::hasTable('invoices')
            || !Schema::hasTable('contracts')
            || !Schema::hasTable('fn_constraint_details')
            || !Schema::hasTable('fn_cost_centers')
            || !Schema::hasTable('fn_accounts')
            || !Schema::hasColumn('invoices', 'arrival_revenue_constraint_id')
            || !Schema::hasColumn('invoices', 'external_office_cost_constraint_id')
        ) {
            return;
        }

        $invoices = DB::table('invoices')
            ->join('contracts', 'contracts.id', '=', 'invoices.contract_id')
            ->where(function ($query) {
                $query->whereNotNull('invoices.arrival_revenue_constraint_id')
                    ->orWhereNotNull('invoices.external_office_cost_constraint_id');
            })
            ->select([
                'invoices.id',
                'invoices.contract_id',
                'invoices.arrival_revenue_constraint_id',
                'invoices.external_office_cost_constraint_id',
                'contracts.maid_id',
                'contracts.required_maid_nationality_id',
            ])
            ->get();

        foreach ($invoices as $invoice) {
            $nationalityName = $this->resolveNationalityName($invoice);
            if ($nationalityName === '') {
                continue;
            }

            $costCenterId = $this->ensureRecruitmentContractCostCenter((int) $invoice->contract_id, $nationalityName);

            if ($invoice->arrival_revenue_constraint_id) {
                DB::table('fn_constraint_details')
                    ->where('constraint_id', $invoice->arrival_revenue_constraint_id)
                    ->update(['cost_center_id' => $costCenterId]);
            }

            if ($invoice->external_office_cost_constraint_id) {
                $costOfSalesAccountId = $this->resolveCostOfSalesAccountId($nationalityName);
                if (!$costOfSalesAccountId) {
                    continue;
                }

                DB::table('fn_constraint_details')
                    ->where('constraint_id', $invoice->external_office_cost_constraint_id)
                    ->update(['cost_center_id' => null]);

                DB::table('fn_constraint_details')
                    ->where('constraint_id', $invoice->external_office_cost_constraint_id)
                    ->where('account_id', $costOfSalesAccountId)
                    ->where('type', 'debtor')
                    ->update(['cost_center_id' => $costCenterId]);
            }
        }
    }

    public function down(): void
    {
        // Irreversible data correction.
    }

    private function resolveNationalityName(object $invoice): string
    {
        $nationalityId = null;

        if ($invoice->maid_id && Schema::hasTable('maids')) {
            $nationalityId = DB::table('maids')
                ->where('id', $invoice->maid_id)
                ->value('nationality_id');
        }

        $nationalityId = $nationalityId ?: $invoice->required_maid_nationality_id;
        if (!$nationalityId || !Schema::hasTable('nationalities_translations')) {
            return '';
        }

        $translation = DB::table('nationalities_translations')
            ->where('nationality_id', $nationalityId)
            ->orderByRaw("locale = 'ar' desc")
            ->select(['sub_title', 'title'])
            ->first();

        return trim((string) ($translation?->sub_title ?: $translation?->title ?: ''));
    }

    private function ensureRecruitmentContractCostCenter(int $contractId, string $nationalityName): int
    {
        $rootId = $this->ensureRecruitmentContractsRootCostCenter();

        $costCenterId = DB::table('fn_cost_centers')
            ->where('parent_id', $rootId)
            ->where('table_name', self::GENERAL_COST_CENTER_TYPE)
            ->where('table_related_id', $contractId)
            ->value('id');

        if ($costCenterId) {
            return (int) $costCenterId;
        }

        return (int) DB::table('fn_cost_centers')->insertGetId([
            'title' => 'عقد استقدام #' . $contractId . ' - ' . $nationalityName,
            'parent_id' => $rootId,
            'table_name' => self::GENERAL_COST_CENTER_TYPE,
            'table_related_id' => $contractId,
            'code' => $this->nextCostCenterCode(),
            'created_by_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureRecruitmentContractsRootCostCenter(): int
    {
        $costCenterId = DB::table('fn_cost_centers')
            ->whereNull('parent_id')
            ->where('title', 'عقود الاستقدام')
            ->value('id');

        if ($costCenterId) {
            return (int) $costCenterId;
        }

        return (int) DB::table('fn_cost_centers')->insertGetId([
            'title' => 'عقود الاستقدام',
            'parent_id' => null,
            'table_name' => self::GENERAL_COST_CENTER_TYPE,
            'table_related_id' => 0,
            'code' => $this->nextCostCenterCode(),
            'created_by_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function nextCostCenterCode(): string
    {
        return (string) (((int) DB::table('fn_cost_centers')->max('code')) + 1);
    }

    private function resolveCostOfSalesAccountId(string $nationalityName): ?int
    {
        return $this->resolveAccountByCandidates(
            [
                'تكاليف مبيعات (' . $nationalityName . ')',
                'تكاليف مبيعات ' . $nationalityName,
                'تكلفة مبيعات ' . $nationalityName,
            ],
            ['تكاليف مبيعات الاستقدام', 'تكاليف مبيعات'],
            $nationalityName
        );
    }

    private function resolveAccountByCandidates(array $directCandidates, array $rootCandidates, string $nationalityName): ?int
    {
        foreach ($directCandidates as $candidate) {
            $accountId = DB::table('fn_accounts')->where('name', $candidate)->value('id');
            if ($accountId) {
                return (int) $accountId;
            }
        }

        foreach ($rootCandidates as $rootName) {
            $rootId = DB::table('fn_accounts')->where('name', $rootName)->value('id');
            if (!$rootId) {
                continue;
            }

            $accountIds = $this->accountAndChildrenIds((int) $rootId);
            $accountId = DB::table('fn_accounts')
                ->whereIn('id', $accountIds)
                ->where(function ($query) {
                    $query->where('name', 'like', 'تكاليف مبيعات (%')
                        ->orWhere('name', 'like', 'تكاليف مبيعات %')
                        ->orWhere('name', 'like', 'تكلفة مبيعات %');
                })
                ->where('name', 'like', '%' . $nationalityName . '%')
                ->orderBy('id')
                ->value('id');

            if ($accountId) {
                return (int) $accountId;
            }
        }

        return null;
    }

    private function accountAndChildrenIds(int $rootId): array
    {
        $ids = [$rootId];
        $pending = [$rootId];

        while ($pending) {
            $children = DB::table('fn_accounts')
                ->whereIn('parent_id', $pending)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $pending = array_values(array_diff($children, $ids));
            $ids = array_values(array_unique(array_merge($ids, $children)));
        }

        return $ids;
    }
};
