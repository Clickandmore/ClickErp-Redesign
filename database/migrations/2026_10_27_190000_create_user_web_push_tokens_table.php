<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Browser push tokens for the customer website. Kept apart from user_firebase_tokens so the mobile app's push path is untouched.
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('user_web_push_tokens')) return;
        Schema::create('user_web_push_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('token', 500)->unique();
            $table->string('user_agent')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_web_push_tokens');
    }
};
