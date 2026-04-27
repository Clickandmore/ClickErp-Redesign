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
        Schema::create('fn_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('main_category_id');
            $table->foreign('main_category_id')->references('id')->on('fn_categories')->onDelete('cascade');
            $table->unsignedBigInteger('sub_category_id')->nullable();
            $table->foreign('sub_category_id')->references('id')->on('fn_categories')->onDelete('cascade');
            $table->string('type');
            $table->string('code_string')->nullable();
            $table->integer('code');
            $table->double('min_order')->default(0);
            $table->unsignedBigInteger('sale_unit_id');
            $table->foreign('sale_unit_id')->references('id')->on('fn_units')->onDelete('cascade');
            $table->double('sale_price')->default(0);
            $table->unsignedBigInteger('purchase_unit_id');
            $table->foreign('purchase_unit_id')->references('id')->on('fn_units')->onDelete('cascade');
            $table->double('purchase_price')->default(0);
            $table->string('details', 1000);
            $table->unsignedBigInteger('sale_account_id');
            $table->foreign('sale_account_id')->references('id')->on('fn_accounts')->onDelete('cascade');
            $table->unsignedBigInteger('stock_account_id');
            $table->foreign('stock_account_id')->references('id')->on('fn_accounts')->onDelete('cascade');
            $table->boolean('stock_tracking')->default(0);
            $table->string('image')->nullable();
            $table->boolean('show_pos')->default(0);
            $table->boolean('show_website')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_items');
    }
};
