<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سجل حركة العاملة اليومية بين الإيواء ومواقع العمل.
 *
 * منفصل تمامًا عن contract_infos.is_outside_shelter: ذلك العمود حالة تعاقدية
 * طويلة الأمد (تسليم عقد إيجار أو نقل كفالة) تحكم قواعد الاستقدام والضمان،
 * وقلبه لكل زيارة ساعات كان سيُفسد تقارير الأنظمة الأخرى. هذا الجدول يحمل
 * الخروج والعودة المؤقتين وحدهما.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('worker_daily_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maid_id');
            $table->unsignedBigInteger('shelter_place_id')->nullable();
            // مصدر الحركة: زيارة ساعات أو تسجيل يدوي من الإدارة.
            $table->string('source', 30)->default('hourly_visit');
            $table->string('reference_type', 60)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('out_at');
            $table->timestamp('in_at')->nullable();
            // out = خارج الإيواء الآن · returned = عادت · overdue = تأخّرت عن موعد العودة.
            // الإطفاء الآمن للموديول يقرأ هذا العمود ليمنع الإطفاء وعاملات خارج الإيواء.
            $table->string('status', 20)->default('out');
            $table->string('out_by_type', 20)->nullable();
            $table->unsignedBigInteger('out_by_id')->nullable();
            $table->string('in_by_type', 20)->nullable();
            $table->unsignedBigInteger('in_by_id')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['maid_id', 'out_at']);
            $table->index('status');
            // الحركات المفتوحة (لم تُسجَّل عودتها بعد) هي استعلام الجرد اليومي.
            $table->index(['in_at', 'maid_id']);
            // حركة واحدة لكل عاملة لكل مرجع، فإعادة إرسال إجراء السائق لا تُكرّر صفًا.
            $table->unique(['source', 'reference_type', 'reference_id', 'maid_id'], 'worker_daily_movements_reference_unique');

            $table->foreign('maid_id')->references('id')->on('maids')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('worker_daily_movements');
    }
};
