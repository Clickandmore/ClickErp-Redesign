<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * حالة كل حقل في نموذج إضافة/تعديل السيرة (كالمعتاد / اختياري / مخفي)،
 * تُدار من شاشة إعدادات السير الذاتية. القيمة الفارغة = السلوك الحالي لكل الحقول.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('settings', 'maid_form_fields')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->text('maid_form_fields')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('settings', 'maid_form_fields')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('maid_form_fields');
        });
    }
};
