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
        Schema::create('fn_item_collect_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_collect_id')->constrained('fn_item_collects')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('fn_items')->onDelete('cascade');
            $table->foreignId('stock_id')->constrained('fn_stocks')->onDelete('cascade');
            $table->integer('order_amount')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_item_collect_details');
    }
};
