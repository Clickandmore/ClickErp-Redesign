<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTriggerSettingsToWebsiteVisitorSites extends Migration
{
    public function up()
    {
        Schema::table('website_visitor_sites', function (Blueprint $table) {
            $table->string('launcher_mode', 20)->default('always')->after('position');
            $table->string('trigger_selector')->nullable()->after('launcher_mode');
        });
    }

    public function down()
    {
        Schema::table('website_visitor_sites', function (Blueprint $table) {
            $table->dropColumn(['launcher_mode', 'trigger_selector']);
        });
    }
}
