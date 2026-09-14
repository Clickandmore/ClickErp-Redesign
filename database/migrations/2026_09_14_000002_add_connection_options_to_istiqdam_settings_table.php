<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('istiqdam_settings', function (Blueprint $table) {
            $table->string('environment', 20)->default('staging')->after('establishment_name');
            $table->string('base_url', 1000)->default('https://gw-apic.qiwa.info')->after('environment');
            $table->string('oauth_url', 1000)->default('https://gw-apic.qiwa.info/takamol/staging/takamol-oauth/oauth2/token')->after('base_url');
            $table->string('scope')->default('IntegrationScope')->after('api_key');
            $table->unsignedSmallInteger('timeout')->default(30)->after('scope');
            $table->unsignedSmallInteger('connect_timeout')->default(10)->after('timeout');
            $table->unsignedInteger('token_cache_ttl')->default(3500)->after('connect_timeout');
            $table->boolean('logging_enabled')->default(true)->after('token_cache_ttl');
        });
    }

    public function down(): void
    {
        Schema::table('istiqdam_settings', function (Blueprint $table) {
            $table->dropColumn([
                'environment', 'base_url', 'oauth_url', 'scope', 'timeout',
                'connect_timeout', 'token_cache_ttl', 'logging_enabled',
            ]);
        });
    }
};
