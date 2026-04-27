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
        Schema::create('fn_stock_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('show_price_in_additional_permit')
                ->default(false)
                ->comment('اعداد ظهور السعر في اذن الاضافة');

            $table->boolean('create_additional_permit_with_purchase_bill')
                ->default(false)
                ->comment('انشاء اذن اضافة مع فاتورة المشتريات');

            $table->boolean('more_than_additional_permit_for_bill')
                ->default(false)
                ->comment('تواجد اكثر من اذن اضافة لفاتورة المشتريات');

            $table->boolean('create_additional_permit_with_return_bill')
                ->default(false)
                ->comment('انشاء اذن اضافة مع فاتورة المرتجع');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_settings');
    }
};
