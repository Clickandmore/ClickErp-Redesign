<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * علامة «قرأتُ حتى» لصندوق إشعارات تطبيق الفريق الميداني.
 *
 * الإشعارات نفسها مسجّلة أصلًا في communication_logs (provider = staff_app) عند كل إرسال،
 * فلا حاجة لتخزين موازٍ لها؛ الناقص فقط حالة القراءة، وسطر واحد لكل فرد يكفي لشارة «غير المقروء».
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hourly_staff_notification_reads')) return;

        Schema::create('hourly_staff_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->string('guard', 10);                 // driver | worker
            $table->unsignedBigInteger('staff_id');
            $table->dateTime('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['guard', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_staff_notification_reads');
    }
};
