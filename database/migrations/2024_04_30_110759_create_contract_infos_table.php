<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
            $table->string('visa_number')->comment('رقم التأشيرة')->default('');
            $table->date('visa_hijry_date')->comment('تاريخ التأشيرة الهجري')->default(null);
            $table->date('visa_milad_date')->comment('تاريخ التأشيرة الميلادي')->default(null);
            $table->string('working_hours_num')->comment('نوع string علشان لو هيدخل مثلا 3 ساعات')->default('');
            $table->double('sponsorship_price')->comment('مبلغ نقل الكفالة')->nullable();
            $table->double('salary_after_sponsorship')->comment('الراتب بعد الكفالة')->nullable();
            $table->double('monthly_cost')->comment('التكلفة الشهرية')->nullable();
            $table->integer('contract_duration')->comment('مدة العقد')->nullable();
            $table->double('contract_value')->comment('قيمة العقد')->nullable();
            $table->date('contract_date')->comment('تاريخ العقد')->nullable();
            $table->date('ingaz_from')->comment('الانجاز من')->default(null);
            $table->date('ingaz_to')->comment('الانجاز الي')->default(null);
            $table->foreignId('previous_sponsorship')->comment('الكفيل السابق')
                ->nullable()
                ->constrained('recruitment_offices')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->string('msaned_number')->default('')->comment('رقم مساند');
            $table->string('tafwed_number')->default('')->comment('رقم التفويض');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contract_infos');
    }
}
