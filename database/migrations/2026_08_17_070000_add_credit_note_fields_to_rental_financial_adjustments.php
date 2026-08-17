<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_financial_adjustments', function (Blueprint $table) {
            $table->foreignId('finance_sale_id')->nullable()->after('constraint_id')->constrained('fn_sales')->nullOnDelete();
            $table->date('issue_date')->nullable()->after('context');
            $table->decimal('taxable_amount', 12, 2)->nullable()->after('amount');
            $table->decimal('non_taxable_amount', 12, 2)->nullable()->after('taxable_amount');
            $table->decimal('vat_amount', 12, 2)->nullable()->after('non_taxable_amount');
            $table->json('line_items')->nullable()->after('vat_amount');
            $table->boolean('send_to_zatca')->default(false)->after('line_items');
            $table->index(['rental_contract_id', 'context', 'issue_date'], 'rental_fin_adj_credit_note_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rental_financial_adjustments', function (Blueprint $table) {
            $table->dropIndex('rental_fin_adj_credit_note_idx');
            $table->dropConstrainedForeignId('finance_sale_id');
            $table->dropColumn(['issue_date', 'taxable_amount', 'non_taxable_amount', 'vat_amount', 'line_items', 'send_to_zatca']);
        });
    }
};
