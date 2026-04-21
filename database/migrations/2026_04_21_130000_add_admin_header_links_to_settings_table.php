<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'header_link_1_title')) {
                $table->string('header_link_1_title')->nullable()->default('مساند برو')->after('map_link');
            }

            if (!Schema::hasColumn('settings', 'header_link_1_url')) {
                $table->string('header_link_1_url', 500)->nullable()->default('https://pros.musaned.com.sa/login')->after('header_link_1_title');
            }

            if (!Schema::hasColumn('settings', 'header_link_2_title')) {
                $table->string('header_link_2_title')->nullable()->default('مساند توثيق')->after('header_link_1_url');
            }

            if (!Schema::hasColumn('settings', 'header_link_2_url')) {
                $table->string('header_link_2_url', 500)->nullable()->default('https://tawtheeq.musaned.com.sa/')->after('header_link_2_title');
            }

            if (!Schema::hasColumn('settings', 'live_chat_title')) {
                $table->string('live_chat_title')->nullable()->default('اللايف شات')->after('header_link_2_url');
            }

            if (!Schema::hasColumn('settings', 'live_chat_url')) {
                $table->string('live_chat_url', 500)->nullable()->default('https://salesiq.zoho.sa/platinumeastern/liveview')->after('live_chat_title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $columns = [
                'header_link_1_title',
                'header_link_1_url',
                'header_link_2_title',
                'header_link_2_url',
                'live_chat_title',
                'live_chat_url',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
