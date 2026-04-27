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
        Schema::create('fn_conversion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversion_id')->references('id')->on('fn_conversions')->onDelete('cascade');
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')->references('id')->on('fn_items')->onDelete('cascade');
            $table->double('purchase_price')->default(0);
            $table->double('available_amount')->default(0);
            $table->double('transfer_amount')->default(0);
            $table->string('notes', 1000)->default('لا يوجد');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_conversion_items');
    }
};
