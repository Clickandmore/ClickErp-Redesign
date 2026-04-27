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
        Schema::create('fn_stock_movement_purchases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bill_id')
                ->comment('الفاتورة')
                ->references('id')
                ->on('fn_purchases')
                ->onDelete('cascade');

            $table->foreignId('stock_movement_id')
                ->comment('اذن الاضافة')
                ->references('id')
                ->on('fn_stock_movements')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movement_purchases');
    }
};
