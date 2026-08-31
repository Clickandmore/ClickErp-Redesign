<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ingaz_delegations')) {
            return;
        }

        foreach ([
            'active_ingaz_number_key',
            'active_msaned_contract_number_key',
            'active_visa_number_key',
        ] as $column) {
            if (!Schema::hasColumn('ingaz_delegations', $column)) {
                continue;
            }

            DB::table('ingaz_delegations')
                ->where(function ($query) use ($column) {
                    $query->whereRaw("TRIM(`{$column}`) = ''")
                        ->orWhereRaw("TRIM(`{$column}`) REGEXP '^0+$'");
                })
                ->update([$column => null]);
        }
    }

    public function down(): void
    {
        // القيم الصفرية كانت placeholders غير صالحة ولا يجب إعادتها إلى المفاتيح الفريدة.
    }
};
