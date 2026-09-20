<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إعدادات فهرسة الموقع الخارجي (canonical و sitemap.xml).
 *
 * كانت هذه القيم تُضبط في ملف .env على خادم الموقع، فلا يستطيع صاحب الموقع
 * تعديلها ولا يراها أحد من لوحة التحكم. صارت الآن حقولًا في الإعدادات تُقدَّم
 * عبر /client/settings ضمن كتلة seo، ويقرأها الموقع الخارجي مع رجوع إلى
 * إعداداته المحلية إن كانت فارغة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('seo_canonical_base', 255)->nullable();
            $table->boolean('seo_sitemap_worker_pages')->default(1);
            $table->unsignedInteger('seo_sitemap_worker_limit')->default(2000);
            $table->boolean('seo_sitemap_offer_pages')->default(1);
            $table->unsignedSmallInteger('seo_sitemap_cache_minutes')->default(360);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'seo_canonical_base',
                'seo_sitemap_worker_pages',
                'seo_sitemap_worker_limit',
                'seo_sitemap_offer_pages',
                'seo_sitemap_cache_minutes',
            ]);
        });
    }
};
