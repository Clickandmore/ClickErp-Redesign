<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'cv_template_output_type')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('cv_template_output_type', 20)
                    ->default('pdf')
                    ->after('cv_template_filename');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'cv_template_output_type')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('cv_template_output_type');
            });
        }
    }
};
