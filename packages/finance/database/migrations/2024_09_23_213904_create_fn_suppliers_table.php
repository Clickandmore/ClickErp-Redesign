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
        Schema::create('fn_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('address')->default('no address');
            $table->string('phone');
            $table->string('email')->default('no email');
            $table->bigInteger('tax_number')->default(0);
            $table->string('tax_image')->default('no image');
            $table->bigInteger('commercial_register_number')->default(0);
            $table->string('commercial_register_image')->default("no image");
            $table->double('credit_limit')->default(0);
            $table->foreignId('account_id')->references('id')->on('fn_accounts')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_suppliers');
    }
};
