<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إعدادات الفئة: صف واحد لكل فئة وعمود JSON يحفظ القيم المخصصة فقط (المفتاح الغائب أو null يرث العام).
 * - hourly_packages.tax_rate يصبح قابلًا للفراغ ليرث العنصر ضريبة الفئة؛ القيم الحالية تبقى كما هي.
 * - hourly_visits.reschedule_count لتطبيق سقف إعادة الجدولة.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('hourly_category_settings')) {
            Schema::create('hourly_category_settings', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('service_category_id')->unique();
                $t->json('settings')->nullable();
                $t->unsignedBigInteger('updated_by')->nullable();
                $t->timestamps();
                $t->foreign('service_category_id', 'hcs_category_fk')->references('id')->on('hourly_service_categories')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('hourly_packages') && Schema::hasColumn('hourly_packages', 'tax_rate')) {
            Schema::table('hourly_packages', fn (Blueprint $t) => $t->decimal('tax_rate', 5, 2)->nullable()->default(null)->change());
        }

        if (Schema::hasTable('hourly_visits') && !Schema::hasColumn('hourly_visits', 'reschedule_count')) {
            Schema::table('hourly_visits', fn (Blueprint $t) => $t->unsignedInteger('reschedule_count')->default(0));
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hourly_visits') && Schema::hasColumn('hourly_visits', 'reschedule_count')) {
            Schema::table('hourly_visits', fn (Blueprint $t) => $t->dropColumn('reschedule_count'));
        }
        // tax_rate يبقى قابلًا للفراغ: العناصر التي ترث ضريبة الفئة يجب ألا تُكسر عند التراجع.
        Schema::dropIfExists('hourly_category_settings');
    }
};
