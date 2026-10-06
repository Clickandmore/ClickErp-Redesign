<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('rental_transfer_contract_id')->nullable()->unique();
            $table->decimal('rental_transfer_paid_amount', 12, 2)->nullable();
            $table->decimal('rental_transfer_remaining_amount', 12, 2)->nullable();
            $table->foreign('rental_transfer_contract_id')->references('id')->on('rental_contracts')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['rental_transfer_contract_id']);
            $table->dropUnique(['rental_transfer_contract_id']);
            $table->dropColumn(['rental_transfer_contract_id', 'rental_transfer_paid_amount', 'rental_transfer_remaining_amount']);
        });
    }
};
