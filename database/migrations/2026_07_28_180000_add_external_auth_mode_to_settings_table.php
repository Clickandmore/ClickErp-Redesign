<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'external_auth_mode')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('external_auth_mode', 20)
                    ->default('password')
                    ->after('show_public_site');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'external_auth_mode')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('external_auth_mode');
            });
        }
    }
};
