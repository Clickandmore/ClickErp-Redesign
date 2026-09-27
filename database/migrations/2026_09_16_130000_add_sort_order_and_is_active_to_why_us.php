<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ترتيب وتفعيل لبطاقات "لماذا نحن" حتى تُدار من لوحة التحكم.
 * الصفوف الحالية تأخذ ترتيبها حسب id فلا يتغير ما يظهر في الموقع.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('why_us', 'sort_order')) {
            Schema::table('why_us', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('icon')->index();
            });

            DB::table('why_us')->orderBy('id')->pluck('id')->each(function ($id, $index) {
                DB::table('why_us')->where('id', $id)->update(['sort_order' => $index + 1]);
            });
        }

        if (!Schema::hasColumn('why_us', 'is_active')) {
            Schema::table('why_us', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('sort_order')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('why_us', 'is_active')) {
            Schema::table('why_us', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('why_us', 'sort_order')) {
            Schema::table('why_us', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
