<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'external_order_mode')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('external_order_mode', 20)
                    ->default('authenticated')
                    ->after('external_auth_mode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'external_order_mode')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('external_order_mode');
            });
        }
    }
};
