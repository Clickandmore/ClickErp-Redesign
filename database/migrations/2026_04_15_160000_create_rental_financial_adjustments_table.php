<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rental_financial_adjustments')) {
            return;
        }

        Schema::create('rental_financial_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rental_contract_id')->nullable();
            $table->unsignedBigInteger('maid_id')->nullable();
            $table->enum('type', ['income', 'expense']);
            $table->string('label', 150);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('notes', 500)->nullable();
            $table->string('context', 50)->nullable()->default('maid');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->unsignedBigInteger('settled_by')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();

            $table->foreign('rental_contract_id', 'rental_fin_adj_contract_fk')
                ->references('id')
                ->on('rental_contracts')
                ->onDelete('cascade');

            $table->foreign('maid_id', 'rental_fin_adj_maid_fk')
                ->references('id')
                ->on('maids')
                ->onDelete('cascade');

            $table->foreign('created_by', 'rental_fin_adj_creator_fk')
                ->references('id')
                ->on('admins')
                ->onDelete('set null');

            $table->foreign('settled_by', 'rental_fin_adj_settled_by_fk')
                ->references('id')
                ->on('admins')
                ->onDelete('set null');

            $table->index(['rental_contract_id', 'type'], 'rental_fin_adj_contract_type_idx');
            $table->index(['maid_id', 'type'], 'rental_fin_adj_maid_type_idx');
            $table->index(['maid_id', 'context'], 'rental_fin_adj_maid_context_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_financial_adjustments');
    }
};
