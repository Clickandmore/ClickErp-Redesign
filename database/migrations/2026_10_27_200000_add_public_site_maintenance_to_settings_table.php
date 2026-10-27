<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'public_site_maintenance')) {
                $table->boolean('public_site_maintenance')->default(false)->after('show_public_site');
            }
        });
    }

    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            if (Schema::hasColumn('settings', 'public_site_maintenance')) {
                $table->dropColumn('public_site_maintenance');
            }
        });
    }
};
