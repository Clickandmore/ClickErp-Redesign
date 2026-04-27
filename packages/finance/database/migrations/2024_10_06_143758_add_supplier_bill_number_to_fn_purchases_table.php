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
        Schema::table('fn_purchases', function (Blueprint $table) {
            $table->string('supplier_bill_number')
                ->after('credit_supplier_id')
                ->default('لا يوجد')
                ->comment('رقم فاتورة المورد');

            $table->double('paid_amount')
                ->default(0)
                ->comment('المبلغ المدفوع من الفاتورة')
                ->after('final_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_purchases', function (Blueprint $table) {
            //
        });
    }
};
