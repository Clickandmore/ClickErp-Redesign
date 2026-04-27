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
        Schema::table('fn_sales', function (Blueprint $table) {
            $table->string('zatca_status',20)->after('file')->comment('ex: reported')->nullable();
            $table->text('qr')->after('file')->nullable();
            $table->text('pih')->after('file')->nullable();
            $table->string('invoice_zatca_category',10)->after('file')->comment('ex: 388')->nullable();
            $table->string('invoice_zatca_type_code',10)->after('file')->comment('0100000')->nullable();
            $table->string('hash_invoice',1000)->after('file')->nullable();
            $table->string('uuid',1000)->after('file')->nullable();
            $table->string('xml_file',1000)->after('file')->nullable();
            $table->text('error_message')->after('file')->nullable();
        });
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
