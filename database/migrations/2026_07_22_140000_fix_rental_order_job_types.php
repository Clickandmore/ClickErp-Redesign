<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('orders')
            || !Schema::hasTable('rental_contracts')
            || !Schema::hasTable('job_types')
            || !DB::table('job_types')->where('id', 2)->exists()
        ) {
            return;
        }

        DB::table('orders')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('rental_contracts')
                    ->whereColumn('rental_contracts.order_id', 'orders.id');
            })
            ->update([
                'job_type_id' => 2,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // The previous incorrect type cannot be restored safely.
    }
};
