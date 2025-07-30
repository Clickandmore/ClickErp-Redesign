<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRentalContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rental_contracts', function (Blueprint $table) {
            $table->id();

            // العلاقات الأساسية
            $table->unsignedBigInteger('user_id')->comment('معرف العميل');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('maid_id')->nullable()->comment('معرف العاملة');
            $table->foreign('maid_id')->references('id')->on('maids')->onDelete('set null');

            $table->unsignedBigInteger('marketer_id')->nullable()->comment('معرف المسوق');
            $table->foreign('marketer_id')->references('id')->on('admins')->onDelete('set null');

            $table->unsignedBigInteger('order_id')->nullable()->comment('معرف الطلب المرتبط');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');

            // معلومات العقد الأساسية
            $table->string('status')->default('new')->comment('حالة العقد');
            $table->date('contract_date')->nullable()->comment('تاريخ العقد');
            $table->string('contract_number')->nullable()->unique()->comment('رقم العقد');

            // معلومات الضمان
            $table->enum('warranty_status', ['not_applicable', 'active', 'expired'])->default('not_applicable')->comment('حالة الضمان');
            $table->date('warranty_start_date')->nullable()->comment('تاريخ بداية الضمان');
            $table->date('warranty_end_date')->nullable()->comment('تاريخ انتهاء الضمان');

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
        Schema::dropIfExists('rental_contracts');
    }
}
