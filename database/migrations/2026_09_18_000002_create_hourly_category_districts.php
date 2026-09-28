<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * مناطق خدمة الفئة: أي أحياء تُخدم في هذه الفئة، وبأي فريق، وهل هي نشطة.
 * ويُحفظ في الطلب فئته وحيه حتى تُقرأ القواعد والتقارير دون إعادة الاستنتاج.
 */
return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('hourly_category_district')) {
            Schema::create('hourly_category_district', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('service_category_id');
                $t->unsignedBigInteger('district_id');
                $t->unsignedBigInteger('team_id')->nullable();
                $t->boolean('is_active')->default(true);
                $t->timestamps();
                $t->unique(['service_category_id', 'district_id'], 'hcd_category_district_unique');
                $t->index('district_id');
                $t->foreign('service_category_id', 'hcd_category_fk')->references('id')->on('hourly_service_categories')->cascadeOnDelete();
                $t->foreign('district_id', 'hcd_district_fk')->references('id')->on('hourly_service_districts')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('hourly_rental_orders')) {
            Schema::table('hourly_rental_orders', function (Blueprint $t) {
                if (!Schema::hasColumn('hourly_rental_orders', 'service_category_id')) $t->unsignedBigInteger('service_category_id')->nullable()->index();
                if (!Schema::hasColumn('hourly_rental_orders', 'service_district_id')) $t->unsignedBigInteger('service_district_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hourly_rental_orders')) {
            Schema::table('hourly_rental_orders', function (Blueprint $t) {
                foreach (['service_category_id', 'service_district_id'] as $column) {
                    if (Schema::hasColumn('hourly_rental_orders', $column)) { $t->dropIndex([$column]); $t->dropColumn($column); }
                }
            });
        }
        Schema::dropIfExists('hourly_category_district');
    }
};
