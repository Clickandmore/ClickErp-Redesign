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
        Schema::create('fn_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code');
            $table->string('address');
            $table->string('phone');
            $table->string('email');
            $table->string('tax_number');
            $table->string('days_credit_period');
            $table->string('credit_limit');
            $table->foreignId('client_category_id')->references('id')->on('fn_client_categories')->onDelete('cascade');
            $table->unsignedBigInteger('account_id')->nullable();
            $table->foreign('account_id')->references('id')->on('fn_accounts')->onDelete('cascade');
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_clients');
    }
};
