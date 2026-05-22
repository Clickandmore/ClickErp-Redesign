<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'dashboard_footer_url')) {
                $table->string('dashboard_footer_url', 500)->nullable()->after('admin_login_background');
            }
        });
    }

    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'dashboard_footer_url')) {
                $table->dropColumn('dashboard_footer_url');
            }
        });
    }
};
