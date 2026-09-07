<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        if (!Schema::hasColumn('settings', 'find_us_mode')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->string('find_us_mode', 20)->default('single')->after('map_link');
            });
        }

        // الوضع الافتراضي رابط واحد حتى لا تتأثر النسخ المرتبطة بالداشبورد حاليًا،
        // أما النسخة التي أضافت فروعًا بالفعل فتبقى على وضع الفروع كما هي.
        if (Schema::hasTable('find_us_locations') && DB::table('find_us_locations')->exists()) {
            DB::table('settings')->update(['find_us_mode' => 'branches']);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'find_us_mode')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('find_us_mode');
            });
        }
    }
};
