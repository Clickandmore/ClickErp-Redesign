<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mcp_subscription_events')) {
            return;
        }

        Schema::create('mcp_subscription_events', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 20);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedSmallInteger('grace_days')->default(0);
            $table->string('notes', 1000)->nullable();
            $table->json('previous')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_subscription_events');
    }
};
