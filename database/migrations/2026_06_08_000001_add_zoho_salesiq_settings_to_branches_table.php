<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('branches') || Schema::hasColumn('branches', 'zoho_salesiq_settings')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
            $column = $table->json('zoho_salesiq_settings')->nullable();

            if (Schema::hasColumn('branches', 'working_hours')) {
                $column->after('working_hours');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('branches') || !Schema::hasColumn('branches', 'zoho_salesiq_settings')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn('zoho_salesiq_settings');
        });
    }
};
