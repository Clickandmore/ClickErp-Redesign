<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dev_superadmin_settings')) {
            return;
        }

        DB::table('dev_superadmin_settings')->insertOrIgnore([
                'key' => 'product_profile',
                'value' => 'recruitment',
                'created_at' => now(),
                'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('dev_superadmin_settings')) {
            DB::table('dev_superadmin_settings')->where('key', 'product_profile')->delete();
        }
    }
};
