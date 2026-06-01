<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'rental_type')) {
                $table->string('rental_type', 30)->default('monthly')->after('nationality_id');
            }

            if (!Schema::hasColumn('packages', 'duration_unit')) {
                $table->string('duration_unit', 30)->default('month')->after('rental_type');
            }

            if (!Schema::hasColumn('packages', 'duration_count')) {
                $table->unsignedInteger('duration_count')->default(1)->after('duration_unit');
            }

            if (!Schema::hasColumn('packages', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('duration_count');
            }

            if (!Schema::hasColumn('packages', 'working_hours_per_day')) {
                $table->unsignedTinyInteger('working_hours_per_day')->nullable()->after('sort_order');
            }

            if (!Schema::hasColumn('packages', 'is_show')) {
                $table->boolean('is_show')->default(true)->after('working_hours_per_day');
            }

            if (!Schema::hasColumn('packages', 'description')) {
                $table->text('description')->nullable()->after('is_show');
            }
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            foreach (['description', 'is_show', 'working_hours_per_day', 'sort_order', 'duration_count', 'duration_unit', 'rental_type'] as $column) {
                if (Schema::hasColumn('packages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
