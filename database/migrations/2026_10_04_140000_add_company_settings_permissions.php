<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'company_settings_view' => ['عرض إعدادات الشركة', 'فتح صفحة إعدادات الشركة وعرض بياناتها.'],
            'company_settings_update' => ['تعديل بيانات الشركة والحساب والشعار', 'حفظ بيانات الشركة وهويتها وحسابها المحاسبي وشعارها.'],
            'company_settings_migration_preview' => ['معاينة ترحيل عقود الشركة', 'اختيار سجلات العملاء القديمة ومراجعة العقود قبل الترحيل.'],
            'company_settings_migration_execute' => ['تنفيذ ترحيل عقود الشركة وأرشفة العملاء', 'تنفيذ المعاينة المؤكدة وإسناد العقود للشركة وأرشفة مصادرها.'],
            'company_settings_history_view' => ['عرض سجل ترحيل الشركة', 'عرض العمليات السابقة ومصادرها وأعداد عقودها.'],
        ] as $name => [$label, $description]) {
            DB::table('permissions')->updateOrInsert(['name' => $name, 'guard_name' => 'admin'], [
                'display_name' => $label, 'description' => $description,
                'group_name' => 'company_settings', 'section_name' => 'settings', 'is_active' => true,
            ]);
        }
        // Register independent permissions without changing existing role/user grants.
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve registered permissions and grants for audit history.
    }
};
