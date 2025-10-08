<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nationalities', function (Blueprint $table) {
            if (!Schema::hasColumn('nationalities', 'is_show')) {
                $table->boolean('is_show')->default(true)->after('monthly_salary');
            }
        });

        Schema::table('nationalities_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('nationalities_translations', 'description')) {
                $table->text('description')->nullable()->after('sub_title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nationalities', function (Blueprint $table) {
            if (Schema::hasColumn('nationalities', 'is_show')) {
                $table->dropColumn('is_show');
            }
        });

        Schema::table('nationalities_translations', function (Blueprint $table) {
            if (Schema::hasColumn('nationalities_translations', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
