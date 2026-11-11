<?php

use App\Models\Permission;
use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * تقسيم صلاحيات وحدة «الرسائل والتواصل».
 *
 * الأدوار التي تملك notifications_control تبقى قادرة على الإرسال والسجل والقوالب والتنبيهات
 * دون منح جديد (MessagingPermissions يقبل الصلاحية القديمة)، لكنها لم تعد تفتح «إعدادات الربط».
 * إعدادات الربط تُمنح هنا لدور Administrator فقط، ويمكن منحها لغيره من شاشة الصلاحيات.
 */
return new class extends Migration {
    private const DEFINITIONS = [
        PermKeys::MESSAGING_SEND => ['إرسال الرسائل (SMS وواتساب) ومحادثات واتساب', 'إرسال SMS وواتساب، الرد في محادثات واتساب، وإعادة إرسال رسالة فاشلة.'],
        PermKeys::MESSAGING_LOG_VIEW => ['عرض سجل الرسائل', 'عرض كل الرسائل الصادرة من النظام وحالتها عند المزود.'],
        PermKeys::MESSAGING_TEMPLATES_MANAGE => ['إدارة قوالب الرسائل', 'إنشاء وتعديل قوالب SMS وواتساب وربطها بقوالب المزود.'],
        PermKeys::MESSAGING_ALERTS_MANAGE => ['إدارة إعدادات التنبيهات الآلية', 'قواعد التنبيه الآلي ومهام الجدولة وتنبيهات تواصل معنا وحجوزات الموقع الخارجي.'],
        PermKeys::MESSAGING_INTEGRATIONS_MANAGE => ['إدارة إعدادات الربط مع مزودي الرسائل', 'مفاتيح وبيانات الاتصال بمزودي البريد وSMS وواتساب (Click Live Chat وMeta وUltraMsg وWiders).'],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $created = [];
        foreach (self::DEFINITIONS as $name => [$label, $description]) {
            $created[$name] = Permission::withoutGlobalScopes()->updateOrCreate(
                ['name' => $name, 'guard_name' => 'admin'],
                $this->attributes($label, $description)
            );
        }

        if (Schema::hasTable('roles') && Schema::hasTable('role_has_permissions')) {
            Role::where('guard_name', 'admin')
                ->where('name', 'Administrator')
                ->get()
                ->each(fn (Role $role) => $role->givePermissionTo(array_values($created)));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // نحافظ على التعيينات عند التراجع عن كود التطبيق.
    }

    private function attributes(string $label, string $description): array
    {
        $columns = [
            'display_name' => $label,
            'description' => $description,
            'group_name' => 'messaging',
            'section_name' => 'messages',
            'is_active' => true,
        ];

        return array_filter(
            $columns,
            fn (string $column) => Schema::hasColumn('permissions', $column),
            ARRAY_FILTER_USE_KEY
        );
    }
};
