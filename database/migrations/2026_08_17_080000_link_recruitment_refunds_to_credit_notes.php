<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recruitment_contract_refunds', function (Blueprint $table) {
            $table->foreignId('credit_note_id')
                ->nullable()
                ->after('refund_constraint_id')
                ->constrained('recruitment_invoice_credit_notes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('recruitment_contract_refunds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('credit_note_id');
        });
    }
};
