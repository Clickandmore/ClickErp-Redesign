<?php

use App\Enums\ingaz\OrderEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('contracts')
                    ->whereColumn('contracts.order_id', 'orders.id');
            })
            ->update([
                'status' => OrderEnum::Completed->value,
                'status_changed_at' => now(),
                'deadline_at' => null,
            ]);
    }

    public function down(): void
    {
        // The previous status cannot be reconstructed safely.
    }
};
