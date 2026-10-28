<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * توكنات خادم MCP وسجل كل استدعاء له.
 * التوكن مربوط بموظف (admins)، فيرث المساعد صلاحيات الموظف وفرعه كما هي.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mcp_access_tokens')) {
            Schema::create('mcp_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_id')->index();
                $table->string('name', 120);
                $table->char('token_hash', 64)->unique();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mcp_audit_logs')) {
            Schema::create('mcp_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_id')->nullable()->index();
                $table->unsignedBigInteger('token_id')->nullable()->index();
                $table->string('method', 64);
                $table->string('tool', 120)->nullable();
                $table->json('arguments')->nullable();
                $table->string('status', 16);
                $table->string('error', 500)->nullable();
                $table->unsignedInteger('duration_ms')->default(0);
                $table->string('ip', 45)->nullable();
                $table->timestamp('created_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mcp_audit_logs');
        Schema::dropIfExists('mcp_access_tokens');
    }
};
