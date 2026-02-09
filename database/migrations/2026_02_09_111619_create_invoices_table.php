<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id()->comment('المعرف');
            $table->foreignId('contract_id')
                ->constrained('contracts')
                ->onDelete('cascade')
                ->comment('معرف العقد');

            $table->string('insurance_company')->nullable()->comment('شركة التأمين');

            $table->decimal('direct_recruitment_cost', 10, 2)->default(0)->comment('تكاليف الاستقدام المباشر');
            $table->decimal('external_recruitment_cost', 10, 2)->default(0)->comment('تكاليف الاستقدام الخارجية');
            $table->decimal('government_cost', 10, 2)->default(0)->comment('تكاليف حكومية');
            $table->decimal('internal_ticket_cost', 10, 2)->default(0)->comment('تكلفة التذكرة الداخلية');

            $table->unsignedInteger('visa_recruited_count')->default(0)->comment('عدد المستقدمين في التأشيرة');
            $table->unsignedInteger('direct_recruitment_count')->default(0)->comment('عدد تكاليف الاستقدام المباشر');
            $table->unsignedInteger('external_recruitment_count')->default(0)->comment('عدد تكاليف الاستقدام الخارجية');
            $table->unsignedInteger('insured_count')->default(0)->comment('عدد المؤمن عليهم');
            $table->unsignedInteger('government_cost_count')->default(0)->comment('عدد تكاليف حكومية');
            $table->unsignedInteger('internal_ticket_count')->default(0)->comment('عدد تكلفة التذكرة الداخلية');

            $table->decimal('subtotal', 10, 2)->default(0)->comment('المجموع');
            $table->decimal('discount', 10, 2)->default(0)->comment('خصم');
            $table->decimal('vat_amount', 10, 2)->default(0)->comment('ضريبة القيمة المضافة');
            $table->decimal('total_due', 10, 2)->default(0)->comment('المطلوب من العميل');
            $table->timestamp('created_at')->nullable()->comment('تاريخ الإنشاء');
            $table->timestamp('updated_at')->nullable()->comment('تاريخ التعديل');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}