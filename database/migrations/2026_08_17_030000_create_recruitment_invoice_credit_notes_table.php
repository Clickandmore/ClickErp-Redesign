<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_invoice_credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->foreignId('contract_id')->constrained('contracts')->restrictOnDelete();
            $table->foreignId('finance_sale_id')->nullable()->constrained('fn_sales')->nullOnDelete();
            $table->foreignId('constraint_id')->nullable()->constrained('fn_constraints')->nullOnDelete();
            $table->foreignId('created_by_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->date('issue_date');
            $table->text('reason');
            $table->decimal('taxable_amount', 12, 2)->default(0);
            $table->decimal('non_taxable_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->json('line_items')->nullable();
            $table->boolean('send_to_zatca')->default(true);
            $table->timestamps();

            $table->index(['invoice_id', 'issue_date']);
            $table->index(['contract_id', 'issue_date']);
        });

        Schema::table('fn_sales', function (Blueprint $table) {
            $table->foreignId('original_sale_id')
                ->nullable()
                ->after('zatca_invoice_id')
                ->constrained('fn_sales')
                ->nullOnDelete();
            $table->text('adjustment_reason')->nullable()->after('original_sale_id');
        });
    }

    public function down(): void
    {
        Schema::table('fn_sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('original_sale_id');
            $table->dropColumn('adjustment_reason');
        });

        Schema::dropIfExists('recruitment_invoice_credit_notes');
    }
};
