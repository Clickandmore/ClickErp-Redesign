<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappConversationsTable extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 32)->unique();
            $table->string('wa_id', 64)->nullable()->index();
            $table->string('profile_name')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('phone_number_id', 100)->nullable()->index();
            $table->unsignedInteger('unread_count')->default(0);
            $table->string('last_direction', 16)->nullable();
            $table->text('last_message_preview')->nullable();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('customer_service_window_expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
}
