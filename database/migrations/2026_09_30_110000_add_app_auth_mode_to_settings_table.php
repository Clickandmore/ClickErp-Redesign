<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'app_auth_mode')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('app_auth_mode', 20)->default('password');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'app_auth_mode')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('app_auth_mode');
            });
        }
    }
};
