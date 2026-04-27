<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('fn_stock_settings', function (Blueprint $table) {
            $table->boolean('create_additional_permit_with_back_purchase_bill')
                  ->default(false)
                  ->after('create_additional_permit_with_purchase_bill')
                  ->comment('هل تريد انشاء اذن صرف مع فاتورة مردود المشتريات؟');
        });
    }

    public function down()
    {
        Schema::table('fn_stock_settings', function (Blueprint $table) {
            $table->dropColumn('create_additional_permit_with_back_purchase_bill');
        });
    }
};
