<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تطبيق الفريق الميداني (سائق + عاملة): الدخول برمز تحقق، أجهزة الإشعارات، مشاوير السائق،
 * حالة تنفيذ المحطة، رمز التسليم للعميل، حالة العاملة في الزيارة، وطلبات الإجازة من التطبيق.
 *
 * كل شيء إضافة فقط: جداول جديدة وأعمدة nullable أو بقيم افتراضية تحفظ السلوك القائم كما هو.
 * hourly_route_stops كانت تحفظ ترتيب مهام السائق فقط، وتصبح الآن هوية المحطة وحالتها أيضًا
 * (نفس الصف: زيارة + دور)، وحذفها عند تحويل المهمة لسائق آخر يبقى هو السلوك المقصود.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hourly_staff_devices')) {
            Schema::create('hourly_staff_devices', function (Blueprint $t) {
                $t->id();
                $t->string('guard', 10);                 // driver | worker
                $t->unsignedBigInteger('staff_id');
                $t->string('fcm_token', 500);
                $t->string('platform', 10)->nullable();  // android | ios
                $t->string('app_version', 30)->nullable();
                $t->dateTime('last_used_at')->nullable();
                $t->timestamps();
                $t->index(['guard', 'staff_id']);
                $t->index(['guard', 'fcm_token']);
            });
        }

        if (!Schema::hasTable('hourly_staff_otps')) {
            Schema::create('hourly_staff_otps', function (Blueprint $t) {
                $t->id();
                $t->string('guard', 10);
                $t->string('phone', 20);
                $t->unsignedBigInteger('staff_id')->nullable();
                $t->string('code_hash');
                $t->dateTime('expires_at');
                $t->unsignedTinyInteger('attempts')->default(0);
                $t->dateTime('consumed_at')->nullable();
                $t->timestamps();
                $t->unique(['guard', 'phone']);
            });
        }

        if (!Schema::hasTable('hourly_driver_trips')) {
            Schema::create('hourly_driver_trips', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('hourly_driver_id');
                $t->date('route_date');
                $t->string('role', 10);                  // delivery | pickup
                $t->string('status', 15)->default('accepted');
                $t->dateTime('planned_start_at')->nullable();
                $t->dateTime('offered_at')->nullable();
                $t->dateTime('expires_at')->nullable();
                $t->dateTime('accepted_at')->nullable();
                $t->dateTime('started_at')->nullable();
                $t->dateTime('completed_at')->nullable();
                $t->string('reject_reason', 200)->nullable();
                $t->timestamps();
                $t->unique(['hourly_driver_id', 'route_date', 'role']);
                $t->index(['status', 'route_date']);
            });
        }

        if (Schema::hasTable('hourly_route_stops')) {
            Schema::table('hourly_route_stops', function (Blueprint $t) {
                if (!Schema::hasColumn('hourly_route_stops', 'hourly_driver_trip_id')) $t->unsignedBigInteger('hourly_driver_trip_id')->nullable()->index();
                if (!Schema::hasColumn('hourly_route_stops', 'status')) $t->string('status', 15)->default('pending')->index();
                if (!Schema::hasColumn('hourly_route_stops', 'arrived_at')) $t->dateTime('arrived_at')->nullable();
                if (!Schema::hasColumn('hourly_route_stops', 'completed_at')) $t->dateTime('completed_at')->nullable();
                if (!Schema::hasColumn('hourly_route_stops', 'cash_collected')) $t->decimal('cash_collected', 12, 2)->nullable();
                if (!Schema::hasColumn('hourly_route_stops', 'fail_reason')) $t->string('fail_reason', 200)->nullable();
            });
        }

        if (Schema::hasTable('hourly_drivers')) {
            Schema::table('hourly_drivers', function (Blueprint $t) {
                if (!Schema::hasColumn('hourly_drivers', 'is_online')) $t->boolean('is_online')->default(false)->index();
                if (!Schema::hasColumn('hourly_drivers', 'last_shift_at')) $t->dateTime('last_shift_at')->nullable();
            });
        }

        // رمز التسليم: يولّده الخادم للزيارة، ويراه العميل في تفاصيل الزيارة، ويطلبه السائق عند التسليم.
        if (Schema::hasTable('hourly_visits') && !Schema::hasColumn('hourly_visits', 'delivery_code')) {
            Schema::table('hourly_visits', fn (Blueprint $t) => $t->string('delivery_code', 10)->nullable());
        }

        // حالة تنفيذ كل عاملة في الزيارة؛ null = تتبع حالة الزيارة كما كان قبل التطبيق.
        if (Schema::hasTable('hourly_visit_workers')) {
            Schema::table('hourly_visit_workers', function (Blueprint $t) {
                if (!Schema::hasColumn('hourly_visit_workers', 'status')) $t->string('status', 20)->nullable();
                if (!Schema::hasColumn('hourly_visit_workers', 'arrived_at')) $t->dateTime('arrived_at')->nullable();
                if (!Schema::hasColumn('hourly_visit_workers', 'finished_at')) $t->dateTime('finished_at')->nullable();
            });
        }

        // الإجازة من لوحة التحكم تبقى معتمدة (approved) وفاعلة؛ طلب التطبيق يدخل pending وis_active=false
        // فلا يدخل محرك الإتاحة (HourlyWorkerAssignment يفحص is_active) إلا بعد اعتماد الإدارة.
        if (Schema::hasTable('hourly_worker_leaves')) {
            Schema::table('hourly_worker_leaves', function (Blueprint $t) {
                if (!Schema::hasColumn('hourly_worker_leaves', 'status')) $t->string('status', 10)->default('approved')->index();
                if (!Schema::hasColumn('hourly_worker_leaves', 'type')) $t->string('type', 10)->default('leave');
                if (!Schema::hasColumn('hourly_worker_leaves', 'source')) $t->string('source', 15)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_staff_devices');
        Schema::dropIfExists('hourly_staff_otps');
        Schema::dropIfExists('hourly_driver_trips');
        if (Schema::hasTable('hourly_route_stops')) {
            Schema::table('hourly_route_stops', fn (Blueprint $t) => $t->dropColumn(['hourly_driver_trip_id', 'status', 'arrived_at', 'completed_at', 'cash_collected', 'fail_reason']));
        }
        if (Schema::hasTable('hourly_drivers')) {
            Schema::table('hourly_drivers', fn (Blueprint $t) => $t->dropColumn(['is_online', 'last_shift_at']));
        }
        if (Schema::hasTable('hourly_visits')) {
            Schema::table('hourly_visits', fn (Blueprint $t) => $t->dropColumn('delivery_code'));
        }
        if (Schema::hasTable('hourly_visit_workers')) {
            Schema::table('hourly_visit_workers', fn (Blueprint $t) => $t->dropColumn(['status', 'arrived_at', 'finished_at']));
        }
        if (Schema::hasTable('hourly_worker_leaves')) {
            Schema::table('hourly_worker_leaves', fn (Blueprint $t) => $t->dropColumn(['status', 'type', 'source']));
        }
    }
};
