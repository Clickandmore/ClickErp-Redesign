<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sends refused for a temporary reason (rate limit, platform down) that the scheduler retries.
        if (!Schema::hasTable('whatsapp_send_retries')) {
            Schema::create('whatsapp_send_retries', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 20)->default('clickchat');
                $table->string('recipient', 64);
                $table->longText('message')->nullable();
                $table->json('context')->nullable();
                $table->unsignedBigInteger('outbound_message_log_id')->nullable()->index();
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->timestamp('next_attempt_at')->nullable();
                $table->string('status', 20)->default('pending');
                $table->text('last_error')->nullable();
                $table->timestamps();
                $table->index(['status', 'next_attempt_at'], 'wa_retry_due_idx');
            });
        }

        // Customers who asked (by replying a stop word) not to receive automated WhatsApp messages.
        if (!Schema::hasTable('whatsapp_opt_outs')) {
            Schema::create('whatsapp_opt_outs', function (Blueprint $table) {
                $table->id();
                $table->string('phone', 32)->unique();
                $table->string('keyword', 60)->nullable();
                $table->string('source', 20)->nullable();
                $table->timestamp('opted_out_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_opt_outs');
        Schema::dropIfExists('whatsapp_send_retries');
    }
};
