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
        Schema::create('deportation_procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->foreignId('maid_id')->constrained('maids')->onDelete('cascade');

            // معلومات الراتب
            $table->boolean('salary_received')->default(false)->comment('هل تم استلام الراتب');
            $table->date('salary_received_date')->nullable()->comment('تاريخ استلام الراتب');

            // الوثائق
            $table->boolean('has_passport')->default(false)->comment('هل معها جواز السفر');
            $table->boolean('has_deportation_letter')->default(false)->comment('خطاب الترحيل');
            $table->boolean('has_settlement_session')->default(false)->comment('جلسة التسوية');
            $table->boolean('has_final_exit')->default(false)->comment('خروج نهائي');

            // الملفات والملاحظات
            $table->text('attachments')->nullable()->comment('المرفقات - JSON');
            $table->text('notes')->nullable()->comment('ملاحظات');

            // الحالة
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])
                ->default('pending')
                ->comment('حالة الإجراءات');

            // التواريخ والمسؤول
            $table->timestamp('started_at')->nullable()->comment('تاريخ بدء الإجراءات');
            $table->timestamp('completed_at')->nullable()->comment('تاريخ اكتمال الإجراءات');
            $table->foreignId('created_by')->nullable()->constrained('admins')->onDelete('set null');
            $table->foreignId('completed_by')->nullable()->constrained('admins')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('contract_id');
            $table->index('maid_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deportation_procedures');
    }
};
