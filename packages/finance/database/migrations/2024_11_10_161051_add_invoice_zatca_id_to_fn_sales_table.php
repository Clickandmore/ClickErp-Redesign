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
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::table('fn_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('zatca_invoice_id')->nullable();
            $table->foreign('zatca_invoice_id')->after('created_by_id')->references('id')->on('zatca_invoices')->onDelete('cascade');
        });
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_sales', function (Blueprint $table) {
            //
        });
    }
};
