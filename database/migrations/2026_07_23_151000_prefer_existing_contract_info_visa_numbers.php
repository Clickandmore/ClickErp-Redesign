<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('contracts') || ! Schema::hasTable('contract_infos')) {
            return;
        }

        DB::table('contracts')
            ->join('contract_infos', 'contract_infos.contract_id', '=', 'contracts.id')
            ->whereNotNull('contracts.arabia_order_number')
            ->whereNotNull('contract_infos.visa_number')
            ->where('contract_infos.visa_number', '!=', '')
            ->whereRaw('COALESCE(contracts.visa_number, "") <> contract_infos.visa_number')
            ->update([
                'contracts.visa_number' => DB::raw('contract_infos.visa_number'),
                'contracts.updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Existing contract detail values remain authoritative.
    }
};
