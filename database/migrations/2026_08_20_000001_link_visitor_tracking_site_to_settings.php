<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LinkVisitorTrackingSiteToSettings extends Migration
{
    public function up()
    {
        if (Schema::hasTable('website_visitor_sites') && !Schema::hasColumn('website_visitor_sites', 'deleted_at')) {
            Schema::table('website_visitor_sites', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'website_visitor_site_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->foreignId('website_visitor_site_id')
                    ->nullable()
                    ->constrained('website_visitor_sites')
                    ->nullOnDelete();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'website_visitor_site_id')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropConstrainedForeignId('website_visitor_site_id');
            });
        }

        if (Schema::hasTable('website_visitor_sites') && Schema::hasColumn('website_visitor_sites', 'deleted_at')) {
            Schema::table('website_visitor_sites', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
}
