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
        Schema::create('fn_sales_orders', function (Blueprint $table) {
            $table->id();
            $table->double('code');
            $table->date('date');
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('fn_clients')->onDelete('cascade');
            $table->unsignedBigInteger('created_by_id');
            $table->foreign('created_by_id')->references('id')->on('admins')->onDelete('cascade');
            $table->double('total_price')->default(0);
            $table->double('total_discount_value')->default(0);
            $table->double('final_total')->default(0);
            $table->string('status')->default("new");
            $table->string('notes', 1000)->default('لا يوجد');
            $table->string('file')->default('no_file');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_sales_orders');
    }
};
