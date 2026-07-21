<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExpandWebsiteVisitorTracking extends Migration
{
    public function up()
    {
        if (Schema::hasTable('website_visitors')) {
            Schema::table('website_visitors', function (Blueprint $table) {
                if (!Schema::hasColumn('website_visitors', 'landing_url')) {
                    $table->text('landing_url')->nullable()->after('current_url');
                }

                if (!Schema::hasColumn('website_visitors', 'referrer')) {
                    $table->text('referrer')->nullable()->after('landing_url');
                }

                if (!Schema::hasColumn('website_visitors', 'page_title')) {
                    $table->string('page_title')->nullable()->after('referrer');
                }

                if (!Schema::hasColumn('website_visitors', 'timezone')) {
                    $table->string('timezone', 100)->nullable()->after('locale');
                }

                if (!Schema::hasColumn('website_visitors', 'screen_width')) {
                    $table->unsignedSmallInteger('screen_width')->nullable()->after('timezone');
                }

                if (!Schema::hasColumn('website_visitors', 'screen_height')) {
                    $table->unsignedSmallInteger('screen_height')->nullable()->after('screen_width');
                }

                if (!Schema::hasColumn('website_visitors', 'session_started_at')) {
                    $table->timestamp('session_started_at')->nullable()->after('screen_height');
                }
            });
        }

        if (!Schema::hasTable('website_visitor_page_views')) {
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
    }

    public function down()
    {
        Schema::dropIfExists('website_visitor_page_views');

        if (Schema::hasTable('website_visitors')) {
            $columns = collect([
                'landing_url',
                'referrer',
                'page_title',
                'timezone',
                'screen_width',
                'screen_height',
                'session_started_at',
            ])->filter(fn ($column) => Schema::hasColumn('website_visitors', $column))->all();

            if (!empty($columns)) {
                Schema::table('website_visitors', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }
}
