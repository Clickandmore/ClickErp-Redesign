<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvoiceMusanedAndSupplierCostOptions extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('musaned_commission_amount', 14, 2)->nullable();
            $table->decimal('musaned_commission_tax', 14, 2)->nullable();
            $table->unsignedBigInteger('musaned_commission_constraint_id')->nullable()->index();
        });
        Schema::table('maids', fn (Blueprint $table) => $table->decimal('supplier_cost_usd', 14, 2)->nullable());
        Schema::table('contract_external_office_dispatches', function (Blueprint $table) {
            $table->decimal('supplier_total_cost_usd', 14, 2)->nullable();
            $table->string('supplier_recognition_mode', 20)->default('full');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn(['musaned_commission_amount', 'musaned_commission_tax', 'musaned_commission_constraint_id']));
        Schema::table('maids', fn (Blueprint $table) => $table->dropColumn('supplier_cost_usd'));
        Schema::table('contract_external_office_dispatches', fn (Blueprint $table) => $table->dropColumn(['supplier_total_cost_usd', 'supplier_recognition_mode']));
    }
}
