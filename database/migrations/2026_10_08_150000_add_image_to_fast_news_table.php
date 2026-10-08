<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * صورة اختيارية لكل خبر في شريط "آخر الأخبار" بالمواقع الخارجية.
 */
class AddImageToFastNewsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('fast_news') || Schema::hasColumn('fast_news', 'image')) {
            return;
        }

        Schema::table('fast_news', function (Blueprint $table) {
            $table->string('image')->nullable()->after('id');
        });
    }

    public function down()
    {
        if (Schema::hasTable('fast_news') && Schema::hasColumn('fast_news', 'image')) {
            Schema::table('fast_news', function (Blueprint $table) {
                $table->dropColumn('image');
            });
        }
    }
}
