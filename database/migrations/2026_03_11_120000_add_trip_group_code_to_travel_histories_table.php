<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('travel_histories')) {
            return;
        }

        Schema::table('travel_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('travel_histories', 'trip_group_code')) {
                $table->string('trip_group_code', 80)
                    ->nullable()
                    ->after('order_id');
                $table->index('trip_group_code', 'travel_histories_trip_group_code_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('travel_histories')) {
            return;
        }

        Schema::table('travel_histories', function (Blueprint $table) {
            if (Schema::hasColumn('travel_histories', 'trip_group_code')) {
                try {
                    $table->dropIndex('travel_histories_trip_group_code_idx');
                } catch (\Throwable $e) {
                    // Ignore if the index does not exist.
                }

                $table->dropColumn('trip_group_code');
            }
        });
    }
};
