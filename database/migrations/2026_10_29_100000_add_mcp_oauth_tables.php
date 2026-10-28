<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OAuth لخادم MCP: يسمح لـ claude.ai وChatGPT وClaude Code بالربط عبر تسجيل دخول
 * الموظف بدل لصق توكن. التوكن الناتج صف عادي في mcp_access_tokens.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('mcp_oauth_clients')) {
            Schema::create('mcp_oauth_clients', function (Blueprint $table) {
                $table->id();
                $table->string('client_id', 80)->unique();
                $table->char('client_secret_hash', 64)->nullable();
                $table->string('client_name', 190)->nullable();
                $table->json('redirect_uris');
                $table->string('token_endpoint_auth_method', 32)->default('none');
                $table->string('registration_ip', 45)->nullable();
                $table->timestamp('revoked_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('mcp_oauth_codes')) {
            Schema::create('mcp_oauth_codes', function (Blueprint $table) {
                $table->id();
                $table->char('code_hash', 64)->unique();
                $table->unsignedBigInteger('client_id')->index();
                $table->unsignedBigInteger('admin_id');
                $table->string('redirect_uri', 2000);
                $table->string('code_challenge', 128);
                $table->string('scope', 190)->nullable();
                $table->timestamp('expires_at');
                $table->timestamp('used_at')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        if (Schema::hasTable('mcp_access_tokens')) {
            Schema::table('mcp_access_tokens', function (Blueprint $table) {
                if (!Schema::hasColumn('mcp_access_tokens', 'oauth_client_id')) {
                    $table->unsignedBigInteger('oauth_client_id')->nullable()->index()->after('admin_id');
                }
                if (!Schema::hasColumn('mcp_access_tokens', 'refresh_token_hash')) {
                    $table->char('refresh_token_hash', 64)->nullable()->unique()->after('token_hash');
                }
                if (!Schema::hasColumn('mcp_access_tokens', 'refresh_expires_at')) {
                    $table->timestamp('refresh_expires_at')->nullable()->after('expires_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('mcp_access_tokens')) {
            Schema::table('mcp_access_tokens', function (Blueprint $table) {
                foreach (['oauth_client_id', 'refresh_token_hash', 'refresh_expires_at'] as $column) {
                    if (Schema::hasColumn('mcp_access_tokens', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('mcp_oauth_codes');
        Schema::dropIfExists('mcp_oauth_clients');
    }
};
