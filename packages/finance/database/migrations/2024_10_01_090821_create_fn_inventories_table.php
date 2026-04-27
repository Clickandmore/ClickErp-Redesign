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
        Schema::create('fn_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->references('id')->on('fn_stocks')->onDelete('cascade');
            $table->date('date');
            $table->string('responsible');
            $table->string('notes', 1000)->default('لا يوجد');
            $table->string('file')->default('no_file');
            $table->double('code');
            $table->string('status')->default("new");
            $table->double('total_purchase_price')->default(0);
            $table->unsignedBigInteger('created_by_id');
            $table->foreign('created_by_id')->references('id')->on('admins')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_inventories');
    }
};
