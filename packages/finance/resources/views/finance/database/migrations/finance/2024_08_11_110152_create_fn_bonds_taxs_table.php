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
        Schema::create('fn_bonds_taxs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bond_id');
            $table->foreign('bond_id')->references('id')->on('fn_bonds')->onDelete('cascade');
            $table->double('tax_rate');
            $table->double('tax_amount');
            $table->unsignedBigInteger('tax_setting_id');
            $table->foreign('tax_setting_id')->references('id')->on('fn_tax_settings')->onDelete('cascade');
            $table->unsignedBigInteger('tax_account_id');
            $table->foreign('tax_account_id')->references('id')->on('fn_accounts')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fn_bonds_taxs');
    }
};
