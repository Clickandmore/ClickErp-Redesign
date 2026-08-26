<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('contract_infos')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('contracts')
                    ->whereColumn('contracts.id', 'contract_infos.contract_id');
            })
            ->delete();
    }

    public function down(): void
    {
        // Deleted orphan rows cannot be associated with a contract on rollback.
    }
};
