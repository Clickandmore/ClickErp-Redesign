<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTriggerSettingsToWebsiteVisitorSites extends Migration
{
    public function up()
    {
        if (Schema::hasTable('website_visitor_sites')) {
            Schema::table('website_visitor_sites', function (Blueprint $table) {
                if (!Schema::hasColumn('website_visitor_sites', 'launcher_mode')) {
                    $table->string('launcher_mode', 20)->default('always')->after('position');
                }

                if (!Schema::hasColumn('website_visitor_sites', 'trigger_selector')) {
                    $table->string('trigger_selector')->nullable()->after('launcher_mode');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('website_visitor_sites')) {
            $columns = collect([
                'launcher_mode',
                'trigger_selector',
            ])->filter(fn ($column) => Schema::hasColumn('website_visitor_sites', $column))->all();

            if (!empty($columns)) {
                Schema::table('website_visitor_sites', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }
}
