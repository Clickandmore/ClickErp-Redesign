<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * السيرة التي يراها العميل في الموقع الخارجي والـ API:
 * generated = المولّدة من القالب (السلوك الحالي)، uploaded = ملف السيرة الذي رفعته الشركة.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('settings', 'cv_display_source')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->string('cv_display_source', 16)->default('generated');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('settings', 'cv_display_source')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('cv_display_source');
        });
    }
};
