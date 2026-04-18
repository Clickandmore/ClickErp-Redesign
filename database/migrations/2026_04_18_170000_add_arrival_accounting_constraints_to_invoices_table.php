<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasArrivalRevenueConstraint = Schema::hasColumn('invoices', 'arrival_revenue_constraint_id');
        $hasExternalOfficeCostConstraint = Schema::hasColumn('invoices', 'external_office_cost_constraint_id');

        if ($hasArrivalRevenueConstraint && $hasExternalOfficeCostConstraint) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) use ($hasArrivalRevenueConstraint, $hasExternalOfficeCostConstraint) {
            if (!$hasArrivalRevenueConstraint) {
                $table->unsignedBigInteger('arrival_revenue_constraint_id')
                    ->nullable()
                    ->after('paid_at');
                $table->foreign('arrival_revenue_constraint_id', 'invoices_arrival_revenue_constraint_fk')
                    ->references('id')
                    ->on('fn_constraints')
                    ->nullOnDelete();
            }

            if (!$hasExternalOfficeCostConstraint) {
                $table->unsignedBigInteger('external_office_cost_constraint_id')
                    ->nullable()
                    ->after('arrival_revenue_constraint_id');
                $table->foreign('external_office_cost_constraint_id', 'invoices_external_office_cost_constraint_fk')
                    ->references('id')
                    ->on('fn_constraints')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        $hasArrivalRevenueConstraint = Schema::hasColumn('invoices', 'arrival_revenue_constraint_id');
        $hasExternalOfficeCostConstraint = Schema::hasColumn('invoices', 'external_office_cost_constraint_id');

        if (!$hasArrivalRevenueConstraint && !$hasExternalOfficeCostConstraint) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) use ($hasArrivalRevenueConstraint, $hasExternalOfficeCostConstraint) {
            if ($hasExternalOfficeCostConstraint) {
                $table->dropForeign('invoices_external_office_cost_constraint_fk');
                $table->dropColumn('external_office_cost_constraint_id');
            }

            if ($hasArrivalRevenueConstraint) {
                $table->dropForeign('invoices_arrival_revenue_constraint_fk');
                $table->dropColumn('arrival_revenue_constraint_id');
            }
        });
    }
};
