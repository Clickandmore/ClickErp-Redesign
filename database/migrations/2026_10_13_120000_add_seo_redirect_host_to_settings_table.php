<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تحويل صيغة www / بدون www إلى النطاق الرسمي للموقع الخارجي بـ 301.
 *
 * كان يُضبط في .env على خادم الموقع (SEO_REDIRECT_HOST)؛ صار حقلًا في
 * إعدادات SEO بجوار النطاق الرسمي ويُقدَّم عبر /client/settings ضمن كتلة seo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('seo_redirect_host')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('seo_redirect_host');
        });
    }
};
