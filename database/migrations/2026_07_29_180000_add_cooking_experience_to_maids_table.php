<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('maids') && !Schema::hasColumn('maids', 'cooking_experience')) {
            Schema::table('maids', function (Blueprint $table) {
                $table->string('cooking_experience')->nullable()->after('experience_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('maids') && Schema::hasColumn('maids', 'cooking_experience')) {
            Schema::table('maids', function (Blueprint $table) {
                $table->dropColumn('cooking_experience');
            });
        }
    }
};
