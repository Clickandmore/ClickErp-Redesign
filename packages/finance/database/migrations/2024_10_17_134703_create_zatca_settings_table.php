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
        Schema::create('zatca_settings', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->string('custom_id')->nullable();
            $table->string('model')->nullable();
            $table->string('CRN_number')->nullable();
            $table->string('OTP')->nullable(); // just until complete
            $table->string('VAT_name')->nullable();
            $table->string('VAT_number')->nullable();
            $table->string('city')->nullable();
            $table->string('city_subdivision')->nullable();
            $table->string('street')->nullable();
            $table->string('plot_identification')->nullable();
            $table->string('building')->nullable();
            $table->string('postal_zone')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('branch_industry')->nullable();
            $table->string('cancelation_type')->nullable();
            $table->string('canceled_invoice_number')->nullable();
            $table->boolean('production')->default(false);
            $table->text('private_key')->nullable();
            $table->text('csr')->nullable();
            $table->date('csr_expire_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zatca_settings');
    }
};
