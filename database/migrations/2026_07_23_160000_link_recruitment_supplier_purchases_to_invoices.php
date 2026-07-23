<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('fn_purchase_items')
            && Schema::hasColumn('fn_purchase_items', 'stock_id')
        ) {
            Schema::table('fn_purchase_items', function (Blueprint $table) {
                $table->unsignedBigInteger('stock_id')->nullable()->change();
            });
        }

        if (
            Schema::hasTable('invoices')
            && Schema::hasTable('fn_purchases')
            && !Schema::hasColumn('invoices', 'external_office_purchase_id')
        ) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('external_office_purchase_id')
                    ->nullable()
                    ->after('external_office_cost_constraint_id');

                $table->foreign('external_office_purchase_id', 'invoices_external_office_purchase_fk')
                    ->references('id')
                    ->on('fn_purchases')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('invoices')
            && Schema::hasColumn('invoices', 'external_office_purchase_id')
        ) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropForeign('invoices_external_office_purchase_fk');
                $table->dropColumn('external_office_purchase_id');
            });
        }

        if (
            Schema::hasTable('fn_purchase_items')
            && Schema::hasColumn('fn_purchase_items', 'stock_id')
        ) {
            Schema::table('fn_purchase_items', function (Blueprint $table) {
                $table->unsignedBigInteger('stock_id')->nullable(false)->change();
            });
        }
    }
};
