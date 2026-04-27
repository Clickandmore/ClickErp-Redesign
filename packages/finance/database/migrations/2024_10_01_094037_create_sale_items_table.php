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
        Schema::create('fn_sale_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')
                ->comment('الفاتورة')
                ->references('id')
                ->on('fn_sales')
                ->onDelete('cascade');

            $table->foreignId('item_id')
                ->references('id')
                ->on('fn_items')
                ->onDelete('cascade');

            $table->double('price')->default(0)->comment('سعر البيع وهو = اخر سعر موجود للمنتج في المخزن');

            $table->double('purchase_price')->comment('سعر الشراء')->default(0);

            $table->double('amount')->comment('الكمية')->default(0);

            $table->double('discount_per')->default(0);

            $table->double('discount_value')->default(0);


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
        Schema::dropIfExists('sale_items');
    }
};
