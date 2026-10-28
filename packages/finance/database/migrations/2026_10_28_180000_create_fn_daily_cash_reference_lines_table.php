<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * بنود «تقرير حركة صندوق اليومية» مربوطة برقم الحساب لا بكوده، فتبقى على حساباتها بعد أي نقل أو إعادة ترقيم.
 * الجدول يبقى فارغاً حتى يُشغَّل finance:link-daily-cash-reference؛ وما دام فارغاً يعمل التقرير من
 * config/daily_cash_reference.php كما كان، فلا يتغير شيء على الشركات التي لا تشغّل الأمر.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fn_daily_cash_reference_lines')) {
            return;
        }

        Schema::create('fn_daily_cash_reference_lines', function (Blueprint $table) {
            $table->id();
            $table->string('group_key', 50)->index();
            $table->unsignedInteger('sort_order');
            $table->unsignedBigInteger('account_id')->nullable()->index();
            $table->string('reference_code', 32);
            $table->string('label');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fn_daily_cash_reference_lines');
    }
};
