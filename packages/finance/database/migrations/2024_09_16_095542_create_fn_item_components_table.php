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
        Schema::create('fn_item_components', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->foreign('item_id')->references('id')->on('fn_items')->onDelete('cascade');
            $table->unsignedBigInteger('component_item_id');
            $table->foreign('component_item_id')->references('id')->on('fn_items')->onDelete('cascade');
            $table->double('amount')->default(0);
            $table->double('one_purchase_price')->default(0);
            $table->double('total_purchase_price')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_item_components');
    }
};
