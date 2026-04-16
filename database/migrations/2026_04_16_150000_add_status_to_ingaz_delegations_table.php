<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingaz_delegations', function (Blueprint $table) {
            if (!Schema::hasColumn('ingaz_delegations', 'status')) {
                $table->string('status', 30)
                    ->default('under_process')
                    ->after('external_office_phone');
                $table->index('status');
            }
        });

        DB::table('ingaz_delegations')->update([
            'status' => DB::raw("
                CASE
                    WHEN ingaz_number IS NOT NULL
                     AND ingaz_number != ''
                     AND msaned_contract_number IS NOT NULL
                     AND msaned_contract_number != ''
                    THEN 'executed'
                    ELSE 'under_process'
                END
            "),
        ]);
    }

    public function down(): void
    {
        Schema::table('ingaz_delegations', function (Blueprint $table) {
            if (Schema::hasColumn('ingaz_delegations', 'status')) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            }
        });
    }
};
