<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'tiktok_tag')) {
                $table->text('tiktok_tag')->nullable();
            }

            if (!Schema::hasColumn('settings', 'snapchat_tag')) {
                $table->text('snapchat_tag')->nullable();
            }

            if (!Schema::hasColumn('settings', 'zoho_tag')) {
                $table->text('zoho_tag')->nullable();
            }

            if (!Schema::hasColumn('settings', 'live_chat_tag')) {
                $table->text('live_chat_tag')->nullable();
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('settings', 'tiktok_tag') ? 'tiktok_tag' : null,
                Schema::hasColumn('settings', 'snapchat_tag') ? 'snapchat_tag' : null,
                Schema::hasColumn('settings', 'zoho_tag') ? 'zoho_tag' : null,
                Schema::hasColumn('settings', 'live_chat_tag') ? 'live_chat_tag' : null,
            ]);

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
