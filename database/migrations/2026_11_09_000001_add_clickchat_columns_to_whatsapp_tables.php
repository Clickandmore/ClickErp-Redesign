<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets the existing WhatsApp inbox tables hold messages from Click Live Chat
 * next to Meta ones. Platform ids are numeric and unique per platform.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_conversations', 'provider')) {
                $table->string('provider', 20)->default('meta')->after('phone_number_id');
            }
            if (!Schema::hasColumn('whatsapp_conversations', 'platform_conversation_id')) {
                $table->unsignedBigInteger('platform_conversation_id')->nullable()->index('wa_conv_platform_conv_idx');
            }
            if (!Schema::hasColumn('whatsapp_conversations', 'channel_account_id')) {
                $table->unsignedBigInteger('channel_account_id')->nullable();
            }
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_messages', 'provider')) {
                $table->string('provider', 20)->default('meta')->after('whatsapp_conversation_id');
            }
            if (!Schema::hasColumn('whatsapp_messages', 'platform_message_id')) {
                $table->unsignedBigInteger('platform_message_id')->nullable()->unique('wa_msg_platform_msg_unique');
            }
            if (!Schema::hasColumn('whatsapp_messages', 'channel_account_id')) {
                $table->unsignedBigInteger('channel_account_id')->nullable();
            }
            if (!Schema::hasColumn('whatsapp_messages', 'reference')) {
                $table->string('reference', 120)->nullable()->index('wa_msg_reference_idx');
            }
            if (!Schema::hasColumn('whatsapp_messages', 'media_url')) {
                $table->string('media_url', 500)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropUnique('wa_msg_platform_msg_unique');
            $table->dropIndex('wa_msg_reference_idx');
            $table->dropColumn(['provider', 'platform_message_id', 'channel_account_id', 'reference', 'media_url']);
        });

        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->dropIndex('wa_conv_platform_conv_idx');
            $table->dropColumn(['provider', 'platform_conversation_id', 'channel_account_id']);
        });
    }
};
