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
        Schema::create('transfer_service_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sponsorship_transfer_id')->comment('معرف طلب نقل الكفالة');
            $table->unsignedBigInteger('contract_id')->comment('معرف العقد الأصلي');
            $table->unsignedBigInteger('old_sponsor_id')->comment('الكفيل القديم');
            $table->unsignedBigInteger('new_sponsor_id')->comment('الكفيل الجديد');
            $table->unsignedBigInteger('maid_id')->comment('العاملة');

            // بيانات العقد
            $table->string('contract_number')->unique()->comment('رقم عقد نقل الخدمات');
            $table->decimal('contract_value', 10, 2)->comment('قيمة العقد');
            $table->decimal('paid_amount', 10, 2)->default(0)->comment('المبلغ المدفوع');
            $table->decimal('remaining_amount', 10, 2)->comment('المبلغ المتبقي');
            $table->integer('trial_days')->comment('عدد أيام التجربة');
            $table->date('trial_start_date')->comment('تاريخ بداية التجربة');
            $table->date('contract_start_date')->comment('تاريخ بداية العقد');
            $table->date('contract_end_date')->nullable()->comment('تاريخ انتهاء العقد');

            // شروط العقد
            $table->json('contract_terms')->nullable()->comment('شروط العقد');
            $table->text('special_conditions')->nullable()->comment('شروط خاصة');
            $table->text('notes')->nullable()->comment('ملاحظات');

            // حالة العقد
            $table->enum('status', ['draft', 'sent', 'signed', 'active', 'completed', 'cancelled'])->default('draft')->comment('حالة العقد');

            // تواريخ مهمة
            $table->timestamp('sent_at')->nullable()->comment('تاريخ الإرسال');
            $table->timestamp('signed_at')->nullable()->comment('تاريخ التوقيع');
            $table->timestamp('activated_at')->nullable()->comment('تاريخ التفعيل');
            $table->timestamp('completed_at')->nullable()->comment('تاريخ الإكمال');
            $table->timestamp('cancelled_at')->nullable()->comment('تاريخ الإلغاء');

            // من قام بالإجراءات
            $table->unsignedBigInteger('created_by')->comment('من أنشأ العقد');
            $table->unsignedBigInteger('sent_by')->nullable()->comment('من أرسل العقد');
            $table->unsignedBigInteger('signed_by')->nullable()->comment('من سجل التوقيع');
            $table->unsignedBigInteger('activated_by')->nullable()->comment('من فعل العقد');
            $table->unsignedBigInteger('completed_by')->nullable()->comment('من أكمل العقد');
            $table->unsignedBigInteger('cancelled_by')->nullable()->comment('من ألغى العقد');

            // سبب الإلغاء
            $table->text('cancellation_reason')->nullable()->comment('سبب الإلغاء');

            $table->timestamps();

            // Foreign keys
            $table->foreign('sponsorship_transfer_id')->references('id')->on('sponsorship_transfers')->onDelete('cascade');
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
            $table->foreign('old_sponsor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('new_sponsor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('maid_id')->references('id')->on('maids')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('sent_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('signed_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('activated_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('completed_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('cancelled_by')->references('id')->on('admins')->onDelete('set null');

            // Indexes
            $table->index(['sponsorship_transfer_id', 'status']);
            $table->index(['contract_id']);
            $table->index(['old_sponsor_id']);
            $table->index(['new_sponsor_id']);
            $table->index(['maid_id']);
            $table->index(['status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfer_service_contracts');
    }
};
