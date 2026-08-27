<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('musaned_section_metrics')
            && !Schema::hasColumn('musaned_section_metrics', 'icon')) {
            Schema::table('musaned_section_metrics', function (Blueprint $table) {
                $table->string('icon', 2048)->nullable()->after('value');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('musaned_section_metrics')
            && Schema::hasColumn('musaned_section_metrics', 'icon')) {
            Schema::table('musaned_section_metrics', function (Blueprint $table) {
                $table->dropColumn('icon');
            });
        }
    }
};
