<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('why_choose_us', function (Blueprint $table) {
            // Target of the section's call-to-action button.
            $table->string('cta_url')->nullable()->after('is_active');
        });

        Schema::table('why_choose_us_translations', function (Blueprint $table) {
            $table->string('cta_label')->nullable()->after('description');
        });

        Schema::table('why_choose_us_features', function (Blueprint $table) {
            // 'feature' = the three cards beside the copy.
            // 'highlight' = the short items in the strip over the image.
            $table->string('type', 20)->default('feature')->after('why_choose_us_id')->index();
        });

        Schema::table('why_choose_us_feature_translations', function (Blueprint $table) {
            // Highlights are a label only, so the description becomes optional.
            $table->text('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('why_choose_us_feature_translations', function (Blueprint $table) {
            $table->text('description')->nullable(false)->change();
        });

        Schema::table('why_choose_us_features', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('why_choose_us_translations', function (Blueprint $table) {
            $table->dropColumn('cta_label');
        });

        Schema::table('why_choose_us', function (Blueprint $table) {
            $table->dropColumn('cta_url');
        });
    }
};
