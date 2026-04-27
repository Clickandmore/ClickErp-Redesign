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
        Schema::create('fn_double_account_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('double_account_id');
            $table->foreign('double_account_id')->references('id')->on('fn_double_accounts')->onDelete('cascade');
            $table->unsignedBigInteger('account_id');
            $table->foreign('account_id')->references('id')->on('fn_accounts')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_double_account_details');
    }
};
