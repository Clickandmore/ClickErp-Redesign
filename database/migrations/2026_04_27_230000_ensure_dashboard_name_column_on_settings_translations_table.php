<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('settings_translations', 'dashboard_name')) {
                $table->string('dashboard_name')->nullable()->after('website_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings_translations', function (Blueprint $table) {
            if (Schema::hasColumn('settings_translations', 'dashboard_name')) {
                $table->dropColumn('dashboard_name');
            }
        });
    }
};
