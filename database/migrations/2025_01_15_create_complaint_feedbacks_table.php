<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('complaint_feedbacks')) {
            Schema::create('complaint_feedbacks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('complaint_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('token', 64)->unique(); // رمز فريد للتقييم

                // التقييم العام
                $table->enum('overall_rating', ['very_satisfied', 'satisfied', 'neutral', 'dissatisfied', 'very_dissatisfied'])->nullable();

                // تقييمات تفصيلية
                $table->integer('response_speed')->nullable()->comment('سرعة الاستجابة 1-5');
                $table->integer('solution_quality')->nullable()->comment('جودة الحل 1-5');
                $table->integer('employee_professionalism')->nullable()->comment('احترافية الموظف 1-5');
                $table->integer('communication_quality')->nullable()->comment('جودة التواصل 1-5');

                // هل تنصح بخدماتنا؟
                $table->enum('would_recommend', ['yes', 'maybe', 'no'])->nullable();

                // ملاحظات إضافية
                $table->text('comments')->nullable();

                // معلومات الإرسال
                $table->string('phone')->nullable();
                $table->timestamp('sms_sent_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent')->nullable();

                $table->timestamps();

                // الفهارس
                $table->foreign('complaint_id')->references('id')->on('complaints')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->index(['complaint_id', 'submitted_at']);
                $table->index('token');
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
        Schema::dropIfExists('complaint_feedbacks');
    }
};
