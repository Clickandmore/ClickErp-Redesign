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
            $table->dropColumn([
                'zatca_status', 'qr','pih','invoice_zatca_category',
                'invoice_zatca_type_code','hash_invoice','uuid','xml_file','error_message'
            ]);
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
