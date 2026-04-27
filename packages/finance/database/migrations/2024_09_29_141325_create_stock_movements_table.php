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
        Schema::create('fn_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->string('type',255);
            $table->string('code');
            $table->date('date');
            $table->unsignedBigInteger('fn_stock_id');
            $table->foreign('fn_stock_id')->references('id')->on('fn_stocks')->onDelete('cascade');
            $table->string('note',1000)->default('لا يوجد')->nullable();
            $table->string('status')->default('new');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
