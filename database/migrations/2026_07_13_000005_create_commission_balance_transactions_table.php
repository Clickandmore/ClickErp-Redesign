<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('marketer_id');
            $table->enum('type', ['credit', 'debit']); // credit=add, debit=deduct
            $table->decimal('amount', 14, 2);
            $table->string('reference_type'); // commission_batch | commission_payment
            $table->unsignedBigInteger('reference_id');
            $table->decimal('balance_after', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('marketer_id')->references('id')->on('admins')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_balance_transactions');
    }
};
