<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('addition_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('unit');
            $table->double('amount')->default(0);
            $table->double('price')->default(0);
            $table->double('discount_per')->default(0);
            $table->double('discount_value')->default(0);
            $table->double('final_price')->default(0);
            $table->foreignId('sales_order_id')->references('id')->on('fn_sales_orders')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addition_items');
    }
};
