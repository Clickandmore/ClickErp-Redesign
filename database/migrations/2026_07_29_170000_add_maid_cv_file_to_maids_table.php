<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('maids') && !Schema::hasColumn('maids', 'maid_cv_file')) {
            Schema::table('maids', function (Blueprint $table) {
                $table->string('maid_cv_file')->nullable()->after('cv_preview');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('maids') && Schema::hasColumn('maids', 'maid_cv_file')) {
            Schema::table('maids', function (Blueprint $table) {
                $table->dropColumn('maid_cv_file');
            });
        }
    }
};
