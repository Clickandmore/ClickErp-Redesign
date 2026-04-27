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
        Schema::create('fn_sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->references('id')->on('fn_sales_orders')->onDelete('cascade');
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')->references('id')->on('fn_items')->onDelete('cascade');
            $table->double('sales_price')->default(0);
            $table->double('sales_amount')->default(0);
            $table->double('discount_per')->default(0);
            $table->double('discount_value')->default(0);
            $table->double('total_sales_price')->default(0);
            $table->double('final_total')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_sales_order_items');
    }
};
