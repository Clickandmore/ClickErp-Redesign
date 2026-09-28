<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * إدارة المسار: ترتيب مهام السائق بالسحب (display_order لكل مهمة = زيارة + دور)، وصلاحيات التحويل والترتيب والسعة.
 * تتبع GPS: السرعة والاتجاه وإصدار التطبيق ونوع الجهاز مع كل موقع.
 */
return new class extends Migration {
    private const PERMISSIONS = [
        'hourly_routing_transfer' => ['تحويل مهام السائقين', 'تحويل مهمة أو عدة مهام لسائق آخر من إدارة المسار'],
        'hourly_routing_reorder' => ['ترتيب مهام السائق', 'تغيير ترتيب مهام السائق بالسحب في إدارة المسار'],
        'hourly_routing_capacity' => ['تعديل سعة مركبة السائق', 'تعديل عدد مقاعد مركبة السائق من إدارة المسار'],
    ];

    public function up(): void
    {
        // الترتيب يخص السائق الحالي للمهمة؛ إذا تغيّر السائق يسقط السطر ولا يُعتمد.
        Schema::create('hourly_route_stops', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hourly_visit_id');
            $table->string('role', 10);
            $table->unsignedBigInteger('hourly_driver_id');
            $table->date('route_date');
            $table->unsignedInteger('display_order');
            $table->timestamps();
            $table->unique(['hourly_visit_id', 'role']);
            $table->index(['hourly_driver_id', 'route_date']);
        });

        Schema::table('location_pings', function (Blueprint $table) {
            $table->decimal('speed', 6, 2)->nullable()->after('accuracy');
            $table->unsignedSmallInteger('heading')->nullable()->after('speed');
            $table->string('app_version', 30)->nullable()->after('heading');
            $table->string('device_type', 40)->nullable()->after('app_version');
        });

        foreach (self::PERMISSIONS as $name => [$display, $description]) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin'], [
                'group_name' => 'hourly_routing', 'section_name' => 'hourly_rental', 'display_name' => $display, 'description' => $description,
            ]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_route_stops');
        Schema::table('location_pings', fn (Blueprint $table) => $table->dropColumn(['speed', 'heading', 'app_version', 'device_type']));
        Permission::whereIn('name', array_keys(self::PERMISSIONS))->where('guard_name', 'admin')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
