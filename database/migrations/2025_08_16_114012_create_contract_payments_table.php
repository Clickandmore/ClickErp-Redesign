<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('remaining_amount', 10, 2)->default(0);
            $table->datetime('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contract_payments');
    }
}
