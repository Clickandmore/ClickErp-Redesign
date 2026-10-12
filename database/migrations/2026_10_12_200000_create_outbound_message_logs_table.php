<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('outbound_message_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('admin_id')->nullable()->index();
            $table->string('channel', 30)->index();
            $table->string('provider', 80)->nullable()->index();
            $table->string('recipient', 255)->index();
            $table->string('recipient_name')->nullable();
            $table->string('source', 255)->nullable()->index();
            $table->string('purpose', 40)->default('message')->index();
            $table->text('message')->nullable();
            $table->string('status', 30)->default('pending')->index();
            $table->text('error')->nullable();
            $table->string('provider_message_id')->nullable()->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('context')->nullable();
            $table->json('provider_response')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('legacy_key')->nullable()->unique();
            $table->timestamps();
            $table->index(['created_at', 'channel', 'status'], 'outbound_message_filter_idx');
        });
    }

    public function down(): void { Schema::dropIfExists('outbound_message_logs'); }
};
