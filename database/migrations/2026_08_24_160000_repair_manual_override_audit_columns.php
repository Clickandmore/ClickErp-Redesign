<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Repair installations where the feature code was deployed before its
     * schema migration, or where a partially applied migration was recorded.
     */
    public function up(): void
    {
        if (!Schema::hasTable('rental_financial_adjustments')) {
            return;
        }

        $columns = [
            'calculated_amount' => Schema::hasColumn('rental_financial_adjustments', 'calculated_amount'),
            'manual_override_reason' => Schema::hasColumn('rental_financial_adjustments', 'manual_override_reason'),
            'manual_override_attachment_path' => Schema::hasColumn('rental_financial_adjustments', 'manual_override_attachment_path'),
            'manual_override_by' => Schema::hasColumn('rental_financial_adjustments', 'manual_override_by'),
            'manual_override_approved_by' => Schema::hasColumn('rental_financial_adjustments', 'manual_override_approved_by'),
            'manual_override_approved_at' => Schema::hasColumn('rental_financial_adjustments', 'manual_override_approved_at'),
        ];

        Schema::table('rental_financial_adjustments', function (Blueprint $table) use ($columns) {
            if (!$columns['calculated_amount']) {
                $table->decimal('calculated_amount', 12, 2)->nullable()->after('amount');
            }
            if (!$columns['manual_override_reason']) {
                $table->string('manual_override_reason', 1000)->nullable()->after('calculated_amount');
            }
            if (!$columns['manual_override_attachment_path']) {
                $table->string('manual_override_attachment_path')->nullable()->after('manual_override_reason');
            }
            if (!$columns['manual_override_by']) {
                $table->unsignedBigInteger('manual_override_by')->nullable()->after('manual_override_attachment_path');
            }
            if (!$columns['manual_override_approved_by']) {
                $table->unsignedBigInteger('manual_override_approved_by')->nullable()->after('manual_override_by');
            }
            if (!$columns['manual_override_approved_at']) {
                $table->timestamp('manual_override_approved_at')->nullable()->after('manual_override_approved_by');
            }
        });
    }

    public function down(): void
    {
        // A repair migration must not remove audit evidence during rollback.
    }
};
