<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('istiqdam_settings', function (Blueprint $table) {
            $table->string('outbound_public_ip', 45)
                ->nullable()
                ->default('130.110.98.217')
                ->after('oauth_url');
        });
    }

    public function down(): void
    {
        Schema::table('istiqdam_settings', function (Blueprint $table) {
            $table->dropColumn('outbound_public_ip');
        });
    }
};
