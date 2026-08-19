<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings') && !Schema::hasColumn('settings', 'rental_order_mode')) {
            Schema::table('settings', function (Blueprint $table) {
                // 'full' هو تسلسل الإيجار الحالي (باقة ثم عنوان ثم تأكيد) ويبقى الافتراضي.
                $table->string('rental_order_mode', 20)
                    ->default('full')
                    ->after('external_order_mode');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settings') && Schema::hasColumn('settings', 'rental_order_mode')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('rental_order_mode');
            });
        }
    }
};
