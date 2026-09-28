<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * مواقع GPS المرسلة من تطبيقات الساعات، وصلاحيتا «إدارة المسار» و«تتبع GPS».
 * user_type يميّز الجهة لأن العاملة والسائق والعميل في جداول مختلفة بأرقام متداخلة.
 */
return new class extends Migration {
    private const PERMISSIONS = [
        'hourly_routing_view' => ['إدارة مسار سائقي الساعات', 'عرض مهام التوصيل والاستلام لكل سائق وخرائط المسار والتصدير'],
        'hourly_gps_view' => ['تتبع GPS لنظام الساعات', 'عرض آخر مواقع العاملات والسائقين والعملاء على الخريطة'],
    ];

    public function up(): void
    {
        Schema::create('location_pings', function (Blueprint $table) {
            $table->id();
            $table->string('user_type', 16);
            $table->unsignedBigInteger('user_id');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->dateTime('recorded_at');
            $table->timestamp('created_at')->nullable();
            $table->index(['user_type', 'user_id', 'recorded_at']);
            $table->index(['recorded_at', 'city_id']);
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
        Schema::dropIfExists('location_pings');
        Permission::whereIn('name', array_keys(self::PERMISSIONS))->where('guard_name', 'admin')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
