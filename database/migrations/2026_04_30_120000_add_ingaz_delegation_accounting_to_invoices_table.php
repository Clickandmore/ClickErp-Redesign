<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'contract_id')) {
                $table->unsignedBigInteger('contract_id')->nullable()->change();
            }

            if (!Schema::hasColumn('invoices', 'ingaz_delegation_id')) {
                $table->foreignId('ingaz_delegation_id')
                    ->nullable()
                    ->after('contract_id')
                    ->constrained('ingaz_delegations')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('invoices', 'ingaz_delegation_cost_constraint_id')) {
                $table->unsignedBigInteger('ingaz_delegation_cost_constraint_id')
                    ->nullable()
                    ->after('external_office_cost_constraint_id');
                $table->foreign('ingaz_delegation_cost_constraint_id', 'invoices_ingaz_delegation_cost_constraint_fk')
                    ->references('id')
                    ->on('fn_constraints')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'ingaz_delegation_cost_constraint_id')) {
                $table->dropForeign('invoices_ingaz_delegation_cost_constraint_fk');
                $table->dropColumn('ingaz_delegation_cost_constraint_id');
            }

            if (Schema::hasColumn('invoices', 'ingaz_delegation_id')) {
                $table->dropConstrainedForeignId('ingaz_delegation_id');
            }
        });
    }
};
