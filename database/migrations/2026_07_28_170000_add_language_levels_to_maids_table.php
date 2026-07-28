<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('maids') && !Schema::hasColumn('maids', 'language_levels')) {
            Schema::table('maids', function (Blueprint $table) {
                $table->json('language_levels')->nullable()->after('percentage');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('maids') && Schema::hasColumn('maids', 'language_levels')) {
            Schema::table('maids', function (Blueprint $table) {
                $table->dropColumn('language_levels');
            });
        }
    }
};
