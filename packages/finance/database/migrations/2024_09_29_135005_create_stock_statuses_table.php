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
        Schema::create('fn_stock_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fn_stock_id');
            $table->foreign('fn_stock_id')->references('id')->on('fn_stocks')->onDelete('cascade');
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')->references('id')->on('fn_items')->onDelete('cascade');
            $table->double('price');
            $table->integer('amount')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_statuses');
    }
};
