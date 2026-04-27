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
        Schema::table('fn_stock_settings', function (Blueprint $table) {

            $table->boolean('sync_with_zatca')
                ->default(false)
                ->comment('مزامنة مع هيئة الزكاة');

            $table->boolean('multiple_stocks_in_bill_sales')
                ->default(false)
                ->comment('تعدد المخازن في فاتورة مبيعات');

            $table->boolean('multiple_stocks_in_bill_purchase')
                ->default(false)
                ->comment('تعدد المخازن في فاتورة الشراء');

            $table->boolean('auto_create_stock_movement_with_bill_sale')
                ->default(false)
                ->comment('انشاء اذن صرف مع فاتورة المبيعات');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_stock_settings', function (Blueprint $table) {
            //
        });
    }
};
