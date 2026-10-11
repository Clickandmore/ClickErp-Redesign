<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * رابط ملف العاملة على منصة مساند (اختياري). يظهر على الموقع الخارجي كزر «مساند»
 * في السيرة الذاتية، ويُستخدم رابط صفحة المكتب على مساند بديلًا عند غيابه.
 */
class AddMusanedLinkToMaidsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('maids') || Schema::hasColumn('maids', 'musaned_link')) {
            return;
        }

        Schema::table('maids', function (Blueprint $table) {
            $table->string('musaned_link', 2000)->nullable()->after('video_link');
        });
    }

    public function down()
    {
        if (Schema::hasColumn('maids', 'musaned_link')) {
            Schema::table('maids', function (Blueprint $table) {
                $table->dropColumn('musaned_link');
            });
        }
    }
}
