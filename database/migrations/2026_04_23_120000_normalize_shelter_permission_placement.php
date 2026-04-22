<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->where('guard_name', 'admin')
            ->where('name', 'like', 'shelter_contract_%')
            ->update([
                'group_name' => 'shelter_contract',
                'section_name' => 'shelter_management',
            ]);

        DB::table('permissions')
            ->where('guard_name', 'admin')
            ->where('name', 'like', 'shelter_workers_%')
            ->update([
                'group_name' => 'shelter_workers',
                'section_name' => 'shelter_management',
            ]);

        DB::table('permissions')
            ->where('guard_name', 'admin')
            ->where('name', 'like', 'shelter_places_%')
            ->update([
                'group_name' => 'shelter_places',
                'section_name' => 'shelter_management',
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->where('guard_name', 'admin')
            ->where('name', 'like', 'shelter_contract_%')
            ->update([
                'group_name' => 'shelter_contracts',
                'section_name' => 'contract_management',
            ]);

        DB::table('permissions')
            ->where('guard_name', 'admin')
            ->where('name', 'like', 'shelter_workers_%')
            ->update([
                'group_name' => 'shelter_workers',
                'section_name' => 'contract_management',
            ]);

        DB::table('permissions')
            ->where('guard_name', 'admin')
            ->where('name', 'like', 'shelter_places_%')
            ->update([
                'group_name' => 'shelter_places',
                'section_name' => 'contract_management',
            ]);
    }
};
