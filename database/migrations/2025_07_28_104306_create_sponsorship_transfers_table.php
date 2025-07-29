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
        Schema::create('sponsorship_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('old_sponsor_id'); // الكفيل القديم
            $table->unsignedBigInteger('new_sponsor_id'); // الكفيل الجديد
            $table->integer('trial_days'); // عدد أيام العمل التجريبي
            $table->date('trial_start_date'); // تاريخ بداية العمل التجريبي
            $table->decimal('contract_value', 10, 2); // قيمة العقد (المبلغ الكامل)
            $table->decimal('paid_amount', 10, 2); // المبلغ المدفوع
            $table->decimal('remaining_amount', 10, 2); // المبلغ المتبقي
            $table->text('notes')->nullable(); // ملاحظات إضافية
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending'); // حالة الطلب
            $table->unsignedBigInteger('approved_by')->nullable(); // من وافق على الطلب
            $table->timestamp('approved_at')->nullable(); // تاريخ الموافقة
            $table->text('rejection_reason')->nullable(); // سبب الرفض
            $table->timestamps();

            // Foreign Keys
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
            $table->foreign('old_sponsor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('new_sponsor_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('admins')->onDelete('set null');

            // Indexes
            $table->index(['contract_id', 'status']);
            $table->index(['old_sponsor_id']);
            $table->index(['new_sponsor_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sponsorship_transfers');
    }
};
