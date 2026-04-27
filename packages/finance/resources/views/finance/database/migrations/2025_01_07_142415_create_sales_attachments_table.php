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
        Schema::create('sales_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->references('id')->on('fn_sales')->onDelete('cascade');
            $table->string('file')->default('no image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_attachments');
    }
};
