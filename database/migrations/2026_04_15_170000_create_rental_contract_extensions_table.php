<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rental_contract_extensions')) {
            return;
        }

        Schema::create('rental_contract_extensions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rental_contract_id');
            $table->integer('extension_months')->nullable();
            $table->integer('extension_days')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('payment_receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps(); 

            $table->foreign('rental_contract_id')
                ->references('id')
                ->on('rental_contracts')
                ->onDelete('cascade');

            $table->foreign('created_by')
                ->references('id')
                ->on('admins')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_contract_extensions');
    }
};
