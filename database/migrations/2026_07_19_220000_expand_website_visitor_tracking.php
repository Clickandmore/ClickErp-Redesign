<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExpandWebsiteVisitorTracking extends Migration
{
    public function up()
    {
        Schema::table('website_visitors', function (Blueprint $table) {
            $table->text('landing_url')->nullable()->after('current_url');
            $table->text('referrer')->nullable()->after('landing_url');
            $table->string('page_title')->nullable()->after('referrer');
            $table->string('timezone', 100)->nullable()->after('locale');
            $table->unsignedSmallInteger('screen_width')->nullable()->after('timezone');
            $table->unsignedSmallInteger('screen_height')->nullable()->after('screen_width');
            $table->timestamp('session_started_at')->nullable()->after('screen_height');
        });

        Schema::create('website_visitor_page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_visitor_id')
                ->constrained('website_visitors')
                ->cascadeOnDelete();
            $table->text('url');
            $table->string('title')->nullable();
            $table->text('referrer')->nullable();
            $table->timestamp('visited_at')->index();
            $table->timestamps();

            $table->index(['website_visitor_id', 'visited_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('website_visitor_page_views');

        Schema::table('website_visitors', function (Blueprint $table) {
            $table->dropColumn([
                'landing_url',
                'referrer',
                'page_title',
                'timezone',
                'screen_width',
                'screen_height',
                'session_started_at',
            ]);
        });
    }
}
