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
            !Schema::hasTable('fn_constraints')
            || !Schema::hasTable('fn_constraint_details')
            || !Schema::hasTable('fn_cost_centers')
            || !Schema::hasTable('rental_contracts')
            || !Schema::hasColumn('fn_constraint_details', 'cost_center_id')
            || !Schema::hasColumn('rental_contracts', 'legacy_order_id')
        ) {
            return;
        }

        DB::transaction(function () {
            $rentalRootId = $this->ensureRentalContractsRootCostCenter();
            $costCenters = [];

            $details = DB::table('fn_constraint_details as details')
                ->join('fn_constraints as constraints', 'constraints.id', '=', 'details.constraint_id')
                ->join('fn_cost_centers as cost_centers', 'cost_centers.id', '=', 'details.cost_center_id')
                ->where('cost_centers.title', 'like', 'طلب استقدام %')
                ->whereNull('details.deleted_at')
                ->where(function ($query) {
                    $query->where('details.statement', 'like', '%طلب تأجير رقم الطلب%')
                        ->orWhere('details.statement', 'like', '%طلب تاجير رقم الطلب%');
                })
                ->where(function ($query) {
                    $query->where('constraints.statement', 'like', 'فاتورة:%')
                        ->orWhere('constraints.statement', 'like', 'إلغاء الفاتورة فاتورة:%')
                        ->orWhere('constraints.statement', 'like', 'اشعار دائن:%')
                        ->orWhere('constraints.statement', 'like', 'إشعار دائن:%');
                })
                ->select(['details.id', 'details.statement'])
                ->orderBy('details.id')
                ->get();

            foreach ($details as $detail) {
                $requestNumber = $this->extractRentalRequestNumber((string) $detail->statement);
                if (!$requestNumber) {
                    continue;
                }

                $costCenters[$requestNumber] ??= $this->resolveRentalRequestCostCenter(
                    $requestNumber,
                    $rentalRootId
                );

                if (!$costCenters[$requestNumber]) {
                    continue;
                }

                DB::table('fn_constraint_details')
                    ->where('id', $detail->id)
                    ->update([
                        'cost_center_id' => $costCenters[$requestNumber],
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    public function down(): void
    {
        // Irreversible data correction. Restoring recruitment cost centers on
        // rental invoices would deliberately recreate the import error.
    }

    private function extractRentalRequestNumber(string $statement): ?int
    {
        if (!preg_match('/طلب\s+ت[أا]جير\s+رقم\s+الطلب\s*[:：#-]?\s*(\d+)/u', $statement, $matches)) {
            return null;
        }

        $requestNumber = (int) ($matches[1] ?? 0);

        return $requestNumber > 0 ? $requestNumber : null;
    }

    private function resolveRentalRequestCostCenter(int $requestNumber, int $rootId): ?int
    {
        $contractId = DB::table('rental_contracts')
            ->where('legacy_order_id', $requestNumber)
            ->value('id');

        if (!$contractId) {
            return null;
        }

        $costCenter = DB::table('fn_cost_centers')
            ->where('parent_id', $rootId)
            ->where('table_name', 'rental_contracts')
            ->where('table_related_id', $contractId)
            ->first();

        $title = 'طلب تأجير ' . $requestNumber;
        if ($costCenter) {
            if ($costCenter->title !== $title) {
                DB::table('fn_cost_centers')
                    ->where('id', $costCenter->id)
                    ->update(['title' => $title, 'updated_at' => now()]);
            }

            return (int) $costCenter->id;
        }

        return (int) DB::table('fn_cost_centers')->insertGetId([
            'title' => $title,
            'parent_id' => $rootId,
            'table_name' => 'rental_contracts',
            'table_related_id' => $contractId,
            'code' => 'RENTAL-' . $requestNumber,
            'created_by_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureRentalContractsRootCostCenter(): int
    {
        $costCenterId = DB::table('fn_cost_centers')
            ->whereNull('parent_id')
            ->where('title', 'عقود التأجير')
            ->value('id');

        if ($costCenterId) {
            return (int) $costCenterId;
        }

        return (int) DB::table('fn_cost_centers')->insertGetId([
            'title' => 'عقود التأجير',
            'parent_id' => null,
            'table_name' => self::GENERAL_COST_CENTER_TYPE,
            'table_related_id' => 0,
            'code' => 'RENTAL',
            'created_by_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
