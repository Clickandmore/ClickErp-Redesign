<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * نداء النجدة للسائق إلى جانب العاملة على نفس الجدول والشاشة.
 *
 * الجدول بُني للعاملة (maid_id)، فنضيف حارسًا يميّز صاحب البلاغ ومعرّف السائق،
 * ونجعل maid_id قابلًا للفراغ لبلاغات السائقين. الصفوف القائمة تبقى guard = worker
 * بالقيمة الافتراضية، فلا تتأثر شاشة اللوحة ولا مسارات العاملة.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hourly_worker_sos_alerts')) return;

        Schema::table('hourly_worker_sos_alerts', function (Blueprint $table) {
            if (!Schema::hasColumn('hourly_worker_sos_alerts', 'guard')) $table->string('guard', 10)->default('worker')->index();
            if (!Schema::hasColumn('hourly_worker_sos_alerts', 'hourly_driver_id')) $table->unsignedBigInteger('hourly_driver_id')->nullable()->index();
        });

        // بلاغ السائق لا يحمل maid_id؛ التغيير يحتاج doctrine/dbal وهو مثبَّت في المشروع.
        if (Schema::hasColumn('hourly_worker_sos_alerts', 'maid_id')) {
            Schema::table('hourly_worker_sos_alerts', fn (Blueprint $table) => $table->unsignedBigInteger('maid_id')->nullable()->change());
        }

        DB::table('hourly_worker_sos_alerts')->whereNull('guard')->update(['guard' => 'worker']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('hourly_worker_sos_alerts')) return;
        DB::table('hourly_worker_sos_alerts')->where('guard', 'driver')->delete();
        Schema::table('hourly_worker_sos_alerts', function (Blueprint $table) {
            $table->dropColumn(['guard', 'hourly_driver_id']);
        });
    }
};
