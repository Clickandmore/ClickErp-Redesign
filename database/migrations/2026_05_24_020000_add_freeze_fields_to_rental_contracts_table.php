<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('rental_contracts', 'status_before_freeze')) {
                $table->string('status_before_freeze')->nullable()->after('status');
            }

            if (!Schema::hasColumn('rental_contracts', 'freeze_reason')) {
                $table->text('freeze_reason')->nullable()->after('status_before_freeze');
            }

            if (!Schema::hasColumn('rental_contracts', 'frozen_at')) {
                $table->timestamp('frozen_at')->nullable()->after('freeze_reason');
            }

            if (!Schema::hasColumn('rental_contracts', 'resumed_at')) {
                $table->timestamp('resumed_at')->nullable()->after('frozen_at');
            }

            if (!Schema::hasColumn('rental_contracts', 'frozen_by')) {
                $table->unsignedBigInteger('frozen_by')->nullable()->after('resumed_at');
                $table->foreign('frozen_by')->references('id')->on('admins')->nullOnDelete();
            }

            if (!Schema::hasColumn('rental_contracts', 'resumed_by')) {
                $table->unsignedBigInteger('resumed_by')->nullable()->after('frozen_by');
                $table->foreign('resumed_by')->references('id')->on('admins')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('rental_contracts', 'frozen_by')) {
                $table->dropForeign(['frozen_by']);
            }

            if (Schema::hasColumn('rental_contracts', 'resumed_by')) {
                $table->dropForeign(['resumed_by']);
            }

            $columns = array_filter([
                Schema::hasColumn('rental_contracts', 'status_before_freeze') ? 'status_before_freeze' : null,
                Schema::hasColumn('rental_contracts', 'freeze_reason') ? 'freeze_reason' : null,
                Schema::hasColumn('rental_contracts', 'frozen_at') ? 'frozen_at' : null,
                Schema::hasColumn('rental_contracts', 'resumed_at') ? 'resumed_at' : null,
                Schema::hasColumn('rental_contracts', 'frozen_by') ? 'frozen_by' : null,
                Schema::hasColumn('rental_contracts', 'resumed_by') ? 'resumed_by' : null,
            ]);

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
