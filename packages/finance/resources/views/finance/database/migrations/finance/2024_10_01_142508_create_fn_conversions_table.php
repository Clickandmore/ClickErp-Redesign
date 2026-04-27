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
        Schema::create('fn_conversions', function (Blueprint $table) {
            $table->id();
            $table->double('code');
            $table->date('date');
            $table->string('status')->default("new");
            $table->string('notes', 1000)->default('لا يوجد');
            $table->string('file')->default('no_file');
            $table->unsignedBigInteger('from_stock_id');
            $table->foreign('from_stock_id')->references('id')->on('fn_stocks')->onDelete('cascade');
            $table->unsignedBigInteger('to_stock_id');
            $table->foreign('to_stock_id')->references('id')->on('fn_stocks')->onDelete('cascade');
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
        Schema::dropIfExists('fn_conversions');
    }
};
