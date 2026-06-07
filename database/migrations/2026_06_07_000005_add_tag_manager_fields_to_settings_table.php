<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTagManagerFieldsToSettingsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'tag_manager_head')) {
                $table->text('tag_manager_head')->nullable();
            }

            if (!Schema::hasColumn('settings', 'tag_manager_footer')) {
                $table->text('tag_manager_footer')->nullable();
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
                Schema::hasColumn('settings', 'tag_manager_head') ? 'tag_manager_head' : null,
                Schema::hasColumn('settings', 'tag_manager_footer') ? 'tag_manager_footer' : null,
            ]);

            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
}
