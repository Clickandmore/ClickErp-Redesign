<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_alert_deliveries')) {
            return;
        }

        Schema::create('notification_alert_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->string('alert_key', 120)->index();
            $table->string('subject_type', 120);
            $table->unsignedBigInteger('subject_id');
            $table->string('channel', 30);
            $table->string('recipient_type', 30);
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->timestamp('event_at')->nullable();
            $table->unsignedInteger('occurrence')->default(0);
            $table->string('status', 30)->default('processing');
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(
                ['alert_key', 'subject_type', 'subject_id'],
                'notification_alert_subject_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_alert_deliveries');
    }
};
