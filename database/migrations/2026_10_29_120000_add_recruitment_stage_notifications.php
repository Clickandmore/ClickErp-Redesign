<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('recruitment_contract_stage_steps', function (Blueprint $table) {
            $table->boolean('is_required')->default(true);
            $table->boolean('notify_sms')->default(false);
            $table->boolean('notify_email')->default(false);
            $table->text('notification_template')->nullable();
        });
        Schema::create('recruitment_stage_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id')->index();
            $table->string('event_key', 100);
            $table->string('channel', 10);
            $table->string('recipient')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->string('status', 20)->default('pending')->index();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['contract_id', 'event_key', 'channel'], 'recruitment_stage_notification_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_stage_notifications');
        Schema::table('recruitment_contract_stage_steps', function (Blueprint $table) {
            $table->dropColumn(['is_required', 'notify_sms', 'notify_email', 'notification_template']);
        });
    }
};
