<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الإنهاء المبكر للزيارة: سجل لكل عاملة تعلن «انتهيت» قبل اكتمال مدة الخدمة من وقت وصولها.
 * العميل يؤكد أو يعترض خلال مهلة، ثم تقرر الإدارة يدويًا ما يلزم؛ لا حركة مالية تلقائية.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('hourly_early_finishes', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('order_id')->index();
            $t->unsignedBigInteger('visit_id')->index();
            $t->unsignedBigInteger('worker_visit_id')->unique();
            $t->unsignedBigInteger('maid_id')->index();
            $t->dateTime('started_at');
            $t->dateTime('finished_at');
            $t->boolean('started_from_schedule')->default(false);
            $t->unsignedInteger('planned_minutes');
            $t->unsignedInteger('worked_minutes');
            $t->string('reason', 30)->default('unspecified');
            $t->text('worker_note')->nullable();
            // awaiting_customer → confirmed (أغلق بلا مراجعة) | disputed / بلا رد بعد المهلة → resolved بقرار الإدارة
            $t->string('status', 30)->default('awaiting_customer')->index();
            $t->dateTime('respond_until');
            $t->dateTime('customer_responded_at')->nullable();
            $t->text('customer_note')->nullable();
            $t->string('decision', 30)->nullable();
            $t->text('decision_note')->nullable();
            $t->unsignedBigInteger('reviewed_by')->nullable();
            $t->dateTime('reviewed_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_early_finishes');
    }
};
