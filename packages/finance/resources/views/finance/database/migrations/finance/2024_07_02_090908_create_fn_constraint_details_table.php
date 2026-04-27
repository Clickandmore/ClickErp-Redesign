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
        Schema::create('fn_constraint_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('constraint_id');
            $table->date('date')->default(\Illuminate\Support\Carbon::now());
            $table->enum('type',['debtor','creditor']);
            $table->unsignedBigInteger('account_id');
            $table->foreign('account_id')->references('id')->on('fn_accounts')->onDelete('cascade');
            $table->foreign('constraint_id')->references('id')->on('fn_constraints')->onDelete('cascade');
            $table->double('debtor_value')->default(0);
            $table->double('creditor_value')->default(0);
            $table->string('statement',500)->default('-');
            $table->unsignedBigInteger('year_id')->nullable();
            $table->foreign('year_id')->references('id')->on('fn_years')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_constraint_details');
    }
};
