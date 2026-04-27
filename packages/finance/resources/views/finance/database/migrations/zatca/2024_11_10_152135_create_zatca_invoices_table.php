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
        Schema::create('zatca_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->foreign('sale_id')->references('id')->on('fn_sales')->onDelete('cascade');
            $table->string('zatca_status',20)->comment('ex: reported')->nullable();
            $table->text('qr')->nullable()->comment('رمز يأتي من الهيئة');
            $table->text('invoice_hash')->nullable()->comment('Invoice hash');
            $table->string('invoice_zatca_category',10)->comment('ex: 388')->nullable();
            $table->string('invoice_zatca_type_code',10)->comment('0100000')->nullable();
            $table->text('pih')->nullable()->comment('Previous invoice hash');
            $table->string('uuid',1000)->nullable();
            $table->string('xml_file',1000)->nullable();
            $table->text('zatca_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zatca_invoices');
    }
};
