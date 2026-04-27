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
        Schema::create('fn_purchase_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_id')
                ->comment('الفاتورة')
                ->references('id')
                ->on('fn_purchases')
                ->onDelete('cascade');

            $table->foreignId('item_id')
                ->references('id')
                ->on('fn_items')
                ->onDelete('cascade');

            $table->double('price')->default(0);

            $table->double('tax_per')->default(0);
            $table->double('tax_value')->default(0);
            $table->unsignedBigInteger('tax_id')->nullable();

            $table->double('amount')->comment('الكمية')->default(0);

            $table->double('discount_per')->default(0);

            $table->double('discount_value')->default(0);

            $table->string('notes',1000)->default('لا يوجد');

            $table->double('final_total')
                ->comment('الاجمالي');

            $table->string('status');

            $table->foreignId('stock_id')
                ->comment('المخزن')
                ->references('id')
                ->on('fn_stocks')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
