<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->string('authorization_num', 255)->nullable()->comment('رقم التفويض');
            $table->string('contract_border_num', 255)->nullable()->comment('رقم الحدود');
            $table->double('contract_cost')->nullable()->comment('قيمة العقد');
            $table->double('contract_tex')->nullable()->comment('رسوم التأشيرة');
            $table->enum('contract_visa_type', ['تأشيرة عمل', 'تأشيرة عائلية'])->nullable()->comment('نوع التأشيرة');
            $table->date('contract_visa_date')->nullable()->comment('تاريخ التأشيرة');
            $table->string('contract_late_cost', 255)->nullable()->comment('مبلغ التأخير باليوم');
            $table->string('contract_holiday_day', 255)->nullable()->comment('عدد ايام الاجازات');
            $table->text('throwback_reason')->nullable()->comment('سبب المرتجع');
            $table->date('throwback_date')->nullable()->comment('تاريخ الارتجاع');
            $table->date('musand_date')->nullable()->comment('تاريخ الدخول لمساند');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
             $table->dropColumn([
                'authorization_num',
                'contract_border_num',
                'contract_cost',
                'contract_tex',
                'contract_visa_type',
                'contract_visa_date',
                'contract_late_cost',
                'contract_holiday_day',
                'throwback_reason',
                'throwback_date',
                'musand_date',
            ]);
        });
    }
}
