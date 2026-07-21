<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebsiteVisitorSitesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('website_visitor_sites')) {
            Schema::create('website_visitor_sites', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('public_key', 64)->unique();
                $table->json('allowed_domains');
                $table->string('widget_title')->default('تحدث معنا');
                $table->text('welcome_message')->nullable();
                $table->string('primary_color', 20)->default('#277ccd');
                $table->string('position', 10)->default('right');
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('website_visitors') && !Schema::hasColumn('website_visitors', 'website_visitor_site_id')) {
            Schema::table('website_visitors', function (Blueprint $table) {
                $table->foreignId('website_visitor_site_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('website_visitor_sites')
                    ->nullOnDelete();
                $table->index(['website_visitor_site_id', 'last_seen_at'], 'website_visitors_site_seen_idx');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('website_visitors') && Schema::hasColumn('website_visitors', 'website_visitor_site_id')) {
            Schema::table('website_visitors', function (Blueprint $table) {
                $table->dropIndex('website_visitors_site_seen_idx');
                $table->dropConstrainedForeignId('website_visitor_site_id');
            });
        }

        Schema::dropIfExists('website_visitor_sites');
    }
}
