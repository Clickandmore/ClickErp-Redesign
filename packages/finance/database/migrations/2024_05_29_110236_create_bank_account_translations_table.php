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
        Schema::create('bank_account_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_account_id');
            $table->string('locale')->index();
            $table->string('name');

            $table->unique(['bank_account_id', 'locale']);
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_account_translations');
    }
};
