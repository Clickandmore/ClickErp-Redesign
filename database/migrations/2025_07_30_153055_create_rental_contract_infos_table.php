<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalContractInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // التحقق من وجود الجدول قبل إنشائه
        if (!Schema::hasTable('rental_contract_infos')) {
            Schema::create('rental_contract_infos', function (Blueprint $table) {
                $table->id();

                // ربط العقد
                $table->unsignedBigInteger('rental_contract_id')->comment('معرف عقد التأجير');
                $table->foreign('rental_contract_id')->references('id')->on('rental_contracts')->onDelete('cascade');

                // معلومات العمل والراتب
                $table->integer('working_hours_per_day')->nullable()->comment('عدد ساعات العمل يومياً');
                $table->decimal('hourly_rate', 8, 2)->nullable()->comment('أجرة الساعة');
                $table->decimal('monthly_salary', 10, 2)->nullable()->comment('الراتب الشهري');

                // معلومات نقل الكفالة والتكلفة
                $table->decimal('sponsorship_transfer_amount', 10, 2)->nullable()->comment('مبلغ نقل الكفالة');
                $table->decimal('salary_after_sponsorship', 10, 2)->nullable()->comment('الراتب بعد نقل الكفالة');
                $table->decimal('monthly_cost', 10, 2)->nullable()->comment('التكلفة الشهرية');

                // معلومات مدة العقد
                $table->integer('contract_duration_months')->nullable()->comment('مدة العقد بالشهور');
                $table->integer('contract_duration_days')->nullable()->comment('مدة العقد بالأيام');
                $table->decimal('contract_value', 12, 2)->nullable()->comment('قيمة العقد المحسوبة');

                // تواريخ الإنجاز
                $table->date('ingaz_from')->nullable()->comment('الإنجاز من');
                $table->date('ingaz_to')->nullable()->comment('الإنجاز إلى');

                // معلومات إضافية
                $table->text('notes')->nullable()->comment('ملاحظات');
                $table->json('additional_terms')->nullable()->comment('شروط إضافية');

                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rental_contract_infos');
    }
}
