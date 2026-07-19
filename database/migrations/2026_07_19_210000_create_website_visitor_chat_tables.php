<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebsiteVisitorChatTables extends Migration
{
    public function up()
    {
        Schema::create('website_visitors', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('current_url')->nullable();
            $table->string('locale', 10)->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('conversation_started_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('website_visitor_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_visitor_id')
                ->constrained('website_visitors')
                ->cascadeOnDelete();
            $table->string('sender', 20);
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('admin_id')->references('id')->on('admins')->nullOnDelete();
            $table->index(['website_visitor_id', 'id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('website_visitor_messages');
        Schema::dropIfExists('website_visitors');
    }
}
