<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maid_cv_shares', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->index();
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->string('admin_name');
            $table->string('client_name');
            $table->string('phone', 30);
            $table->string('provider', 30);
            $table->string('sender_key')->nullable();
            $table->string('status', 30)->default('draft');
            $table->text('message');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('maid_cv_share_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('share_id')->constrained('maid_cv_shares')->cascadeOnDelete();
            $table->unsignedBigInteger('maid_id')->nullable()->index();
            $table->json('snapshot')->nullable();
            $table->string('file_path')->nullable();
            $table->string('filename')->nullable();
            $table->string('status', 30)->default('pending');
            $table->string('provider_message_id')->nullable()->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maid_cv_share_messages');
        Schema::dropIfExists('maid_cv_shares');
    }
};
