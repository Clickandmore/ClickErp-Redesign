<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'sponsorship_transfer_id')) {
                $table->foreignId('sponsorship_transfer_id')
                    ->nullable()
                    ->unique()
                    ->after('client_id')
                    ->constrained('sponsorship_transfers')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('invoices', 'line_items')) {
                $table->json('line_items')->nullable()->after('general_description');
            }

            if (!Schema::hasColumn('invoices', 'invoice_constraint_id')) {
                $table->unsignedBigInteger('invoice_constraint_id')->nullable()->after('paid_at');
                $table->foreign('invoice_constraint_id', 'invoices_invoice_constraint_fk')
                    ->references('id')
                    ->on('fn_constraints')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'invoice_constraint_id')) {
                $table->dropForeign('invoices_invoice_constraint_fk');
                $table->dropColumn('invoice_constraint_id');
            }

            if (Schema::hasColumn('invoices', 'line_items')) {
                $table->dropColumn('line_items');
            }

            if (Schema::hasColumn('invoices', 'sponsorship_transfer_id')) {
                $table->dropConstrainedForeignId('sponsorship_transfer_id');
            }
        });
    }
};
