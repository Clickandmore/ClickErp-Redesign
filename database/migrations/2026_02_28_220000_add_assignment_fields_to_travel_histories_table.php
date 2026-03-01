<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('travel_histories')) {
            return;
        }

        Schema::table('travel_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('travel_histories', 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')->nullable()->after('flight_number');
            }

            if (!Schema::hasColumn('travel_histories', 'assigned_by')) {
                $table->unsignedBigInteger('assigned_by')->nullable()->after('assigned_to');
            }

            if (!Schema::hasColumn('travel_histories', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable()->after('assigned_by');
            }

            if (!Schema::hasColumn('travel_histories', 'assignment_note')) {
                $table->text('assignment_note')->nullable()->after('assigned_at');
            }
        });

        Schema::table('travel_histories', function (Blueprint $table) {
            try {
                $table->foreign('assigned_to')
                    ->references('id')
                    ->on('admins')
                    ->onDelete('set null');
            } catch (\Throwable $e) {
                // Ignore when constraint already exists.
            }

            try {
                $table->foreign('assigned_by')
                    ->references('id')
                    ->on('admins')
                    ->onDelete('set null');
            } catch (\Throwable $e) {
                // Ignore when constraint already exists.
            }

            try {
                $table->index('assigned_to');
            } catch (\Throwable $e) {
                // Ignore when index already exists.
            }

            try {
                $table->index('assigned_by');
            } catch (\Throwable $e) {
                // Ignore when index already exists.
            }

            try {
                $table->index('assigned_at');
            } catch (\Throwable $e) {
                // Ignore when index already exists.
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('travel_histories')) {
            return;
        }

        Schema::table('travel_histories', function (Blueprint $table) {
            try {
                $table->dropForeign(['assigned_to']);
            } catch (\Throwable $e) {
                // Ignore missing constraint.
            }

            try {
                $table->dropForeign(['assigned_by']);
            } catch (\Throwable $e) {
                // Ignore missing constraint.
            }

            try {
                $table->dropIndex(['assigned_to']);
            } catch (\Throwable $e) {
                // Ignore missing index.
            }

            try {
                $table->dropIndex(['assigned_by']);
            } catch (\Throwable $e) {
                // Ignore missing index.
            }

            try {
                $table->dropIndex(['assigned_at']);
            } catch (\Throwable $e) {
                // Ignore missing index.
            }

            $dropColumns = [];

            if (Schema::hasColumn('travel_histories', 'assigned_to')) {
                $dropColumns[] = 'assigned_to';
            }

            if (Schema::hasColumn('travel_histories', 'assigned_by')) {
                $dropColumns[] = 'assigned_by';
            }

            if (Schema::hasColumn('travel_histories', 'assigned_at')) {
                $dropColumns[] = 'assigned_at';
            }

            if (Schema::hasColumn('travel_histories', 'assignment_note')) {
                $dropColumns[] = 'assignment_note';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
