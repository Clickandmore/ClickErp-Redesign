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
        Schema::create('fn_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('code');
            $table->foreignId('supplier_id')->references('id')->on('fn_suppliers')->onDelete('cascade');
            $table->date('date');
            $table->string('status')->default('new');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_purchase_orders');
    }
};
