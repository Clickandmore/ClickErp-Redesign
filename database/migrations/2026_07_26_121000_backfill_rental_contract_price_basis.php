<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('rental_contract_infos')
            || !Schema::hasColumn('rental_contract_infos', 'package_price_basis')
        ) {
            return;
        }

        DB::table('rental_contract_infos')
            ->whereNull('package_price_basis')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $net = (float) ($row->package_price_after_discount ?? 0);
                    $gross = (float) ($row->package_total_with_tax ?? 0);
                    $isExplicitNetPackage = !empty($row->package_id)
                        && $net > 0
                        && abs($gross - round($net * 1.15, 2)) < 0.05;

                    DB::table('rental_contract_infos')
                        ->where('id', $row->id)
                        ->update([
                            'package_price_basis' => $isExplicitNetPackage ? 'net' : 'gross',
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (
            Schema::hasTable('rental_contract_infos')
            && Schema::hasColumn('rental_contract_infos', 'package_price_basis')
        ) {
            DB::table('rental_contract_infos')->update(['package_price_basis' => null]);
        }
    }
};
