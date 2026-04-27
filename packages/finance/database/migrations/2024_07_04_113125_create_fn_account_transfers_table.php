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
        Schema::create('fn_account_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('debit_account_id')->comment("المدين");
            $table->foreign('debit_account_id')->references('id')->on('fn_accounts')->onDelete('cascade');
            $table->unsignedBigInteger('credit_account_id')->comment("الدائن");
            $table->foreign('credit_account_id')->references('id')->on('fn_accounts')->onDelete('cascade');
            $table->unsignedBigInteger('constraint_id')->nullable();
            $table->foreign('constraint_id')->references('id')->on('fn_constraints')->onDelete('cascade');
            $table->double("value")->default(0);
            $table->date("date");
            $table->string("statement",500)->default("-");
            $table->boolean("is_confirm")->default(false)->comment("حالة الترحيل ");
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
        Schema::dropIfExists('account_transfers');
    }
};
