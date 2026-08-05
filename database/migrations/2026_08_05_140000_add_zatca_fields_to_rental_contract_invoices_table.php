<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contract_invoices', function (Blueprint $table) {
            $table->boolean('send_to_zatca')->default(false)->after('service_description');
            $table->unsignedBigInteger('finance_sale_id')->nullable()->after('send_to_zatca');

            $table->foreign('finance_sale_id', 'rental_invoices_finance_sale_fk')
                ->references('id')
                ->on('fn_sales')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rental_contract_invoices', function (Blueprint $table) {
            $table->dropForeign('rental_invoices_finance_sale_fk');
            $table->dropColumn(['send_to_zatca', 'finance_sale_id']);
        });
    }
};
