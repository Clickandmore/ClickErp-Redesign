<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * طرق الدفع لكل فئة وقناة. لا يوجد جدول طرق دفع عام في النظام؛ طرق الساعات مفاتيح ثابتة
 * (cash, card_on_delivery, bank_transfer, moyasar) لذا يُحفظ المفتاح بدل payment_method_id.
 * الطريقة غير المسجلة للفئة تُعد مسموحة في كل القنوات.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('hourly_category_payment_methods')) return;
        Schema::create('hourly_category_payment_methods', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('service_category_id');
            $t->string('payment_method', 30);
            $t->boolean('web_app')->default(true);
            $t->boolean('employee')->default(true);
            $t->boolean('branch')->default(true);
            $t->timestamps();
            $t->unique(['service_category_id', 'payment_method'], 'hcpm_category_method_unique');
            $t->foreign('service_category_id', 'hcpm_category_fk')->references('id')->on('hourly_service_categories')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_category_payment_methods');
    }
};
