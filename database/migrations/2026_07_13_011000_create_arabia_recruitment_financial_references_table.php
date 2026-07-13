<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arabia_recruitment_financial_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->string('source_type', 50);
            $table->string('legacy_reference', 150)->default('');
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('currency', 50)->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(
                ['contract_id', 'source_type', 'legacy_reference'],
                'arabia_rec_financial_reference_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arabia_recruitment_financial_references');
    }
};
