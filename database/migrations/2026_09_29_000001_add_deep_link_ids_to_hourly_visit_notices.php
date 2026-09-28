<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * معرّفا الطلب والزيارة على إشعار العميل، ليفتح التطبيق الشاشة الصحيحة عند الضغط.
 * يبقيان فارغين حين يجمع الإشعار أكثر من زيارة أو أكثر من طلب لنفس العميل.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hourly_visit_notices')) return;
        Schema::table('hourly_visit_notices', function (Blueprint $t) {
            if (!Schema::hasColumn('hourly_visit_notices', 'order_id')) $t->unsignedBigInteger('order_id')->nullable()->after('user_id');
            if (!Schema::hasColumn('hourly_visit_notices', 'visit_id')) $t->unsignedBigInteger('visit_id')->nullable()->after('order_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('hourly_visit_notices')) return;
        Schema::table('hourly_visit_notices', function (Blueprint $t) {
            foreach (['order_id', 'visit_id'] as $column) {
                if (Schema::hasColumn('hourly_visit_notices', $column)) $t->dropColumn($column);
            }
        });
    }
};
