<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('location_pings') && !Schema::hasColumn('location_pings', 'hourly_visit_id')) {
            Schema::table('location_pings', function (Blueprint $table) {
                $table->unsignedBigInteger('hourly_visit_id')->nullable()->after('user_id');
                $table->index(['hourly_visit_id', 'recorded_at'], 'location_pings_visit_recorded_index');
            });
        }

        if (!Schema::hasTable('pages')) return;

        $marker = 'data-hourly-location-privacy';
        $page = DB::table('pages')->where('slug', 'privacy')->first(['id', 'content_ar']);
        if (!$page || str_contains((string) $page->content_ar, $marker)) return;

        $paragraph = '<h3 '.$marker.'="1">تتبع موقع فريق الزيارات بالساعة</h3>'
            .'<p>أثناء مراحل انتقال الزيارة بالساعة، نجمع موقع السائق أو العاملة المكلّفة ونعرِض الموقع للعميل صاحب الزيارة فقط لغرض متابعة الوصول. يتوقف العرض عند انتهاء المرحلة أو الزيارة، ولا يُعرض رقم جوال العاملة للعميل. تُحذف نقاط الموقع التفصيلية بعد 30 يومًا.</p>';
        $content = (string) $page->content_ar;
        $anchor = '<h2>7. حقوقك</h2>';
        $content = str_contains($content, $anchor)
            ? str_replace($anchor, $paragraph."\n".$anchor, $content)
            : rtrim($content)."\n".$paragraph;
        DB::table('pages')->where('id', $page->id)->update([
            'content_ar' => $content,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('location_pings') && Schema::hasColumn('location_pings', 'hourly_visit_id')) {
            Schema::table('location_pings', function (Blueprint $table) {
                $table->dropIndex('location_pings_visit_recorded_index');
                $table->dropColumn('hourly_visit_id');
            });
        }
    }
};
