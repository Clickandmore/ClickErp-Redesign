<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSeoFieldsToSettingsTranslationsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('settings_translations')) {
            return;
        }

        Schema::table('settings_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('settings_translations', 'seo_article_tags')) {
                $table->text('seo_article_tags')->nullable()->after('footer_text');
            }
            if (!Schema::hasColumn('settings_translations', 'meta_title')) {
                $table->string('meta_title')->nullable()->after('seo_article_tags');
            }
            if (!Schema::hasColumn('settings_translations', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
            if (!Schema::hasColumn('settings_translations', 'meta_keywords')) {
                $table->text('meta_keywords')->nullable()->after('meta_description');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('settings_translations')) {
            return;
        }

        Schema::table('settings_translations', function (Blueprint $table) {
            $columns = [
                'seo_article_tags',
                'meta_title',
                'meta_description',
                'meta_keywords',
            ];

            $existingColumns = array_filter($columns, fn ($column) => Schema::hasColumn('settings_translations', $column));
            if ($existingColumns) {
                $table->dropColumn($existingColumns);
            }
        });
    }
}
