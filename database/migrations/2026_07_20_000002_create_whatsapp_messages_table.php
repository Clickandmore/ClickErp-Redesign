<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappMessagesTable extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whatsapp_conversation_id');
            $table->foreign('whatsapp_conversation_id', 'wa_messages_conversation_fk')
                ->references('id')
                ->on('whatsapp_conversations')
                ->onDelete('cascade');
            $table->string('meta_message_id', 255)->nullable()->unique();
            $table->string('reply_to_message_id', 255)->nullable()->index();
            $table->string('direction', 16)->index();
            $table->string('type', 32)->default('text');
            $table->longText('body')->nullable();
            $table->string('media_id', 255)->nullable();
            $table->string('media_mime_type', 150)->nullable();
            $table->string('media_filename')->nullable();
            $table->string('status', 32)->default('received')->index();
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('sent_by_admin_id')->nullable()->index();
            $table->longText('provider_payload')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_messages');
    }
}
