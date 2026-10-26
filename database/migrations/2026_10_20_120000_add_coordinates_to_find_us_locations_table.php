<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('find_us_locations')) {
            return;
        }

        Schema::table('find_us_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('find_us_locations', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('map_embed_url');
            }
            if (!Schema::hasColumn('find_us_locations', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('find_us_locations')) {
            return;
        }

        Schema::table('find_us_locations', function (Blueprint $table) {
            foreach (['longitude', 'latitude'] as $column) {
                if (Schema::hasColumn('find_us_locations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
