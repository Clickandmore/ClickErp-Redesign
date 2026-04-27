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
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_id'); // معرف فاتورة الشراء
            $table->unsignedBigInteger('return_purchase_id'); // معرف فاتورة المردود

            // إضافة المفاتيح الأجنبية
            $table->foreign('purchase_id')->references('id')->on('fn_purchases')->onDelete('cascade');
            $table->foreign('return_purchase_id')->references('id')->on('fn_purchases')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_returns');
    }
};
