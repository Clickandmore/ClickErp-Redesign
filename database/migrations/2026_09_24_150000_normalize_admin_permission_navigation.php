<?php

use App\Support\AdminPermissionNavigation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $columns = array_flip(Schema::getColumnListing('permissions'));
        if (!isset($columns['section_name'], $columns['group_name'])) {
            return;
        }

        DB::table('permissions')
            ->where('guard_name', 'admin')
            ->select(['id', 'name', 'section_name', 'group_name', 'display_name'])
            ->orderBy('id')
            ->chunkById(200, function ($permissions) use ($columns) {
                foreach ($permissions as $permission) {
                    $placement = AdminPermissionNavigation::placementForPermission(
                        (string) $permission->name,
                        $permission->section_name,
                        $permission->group_name
                    );

                    $values = [
                        'section_name' => $placement['section'],
                        'group_name' => $placement['group'],
                    ];

                    $knownDisplayNames = $this->knownDisplayNames();

                    if (isset($knownDisplayNames[$permission->name])) {
                        $values['display_name'] = $knownDisplayNames[$permission->name];
                    } elseif (
                        isset($columns['display_name'])
                        && trim((string) $permission->display_name) === ''
                    ) {
                        $values['display_name'] = $this->displayName((string) $permission->name);
                    }

                    if (isset($columns['updated_at'])) {
                        $values['updated_at'] = now();
                    }

                    DB::table('permissions')->where('id', $permission->id)->update($values);
                }
            }, 'id');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // تطبيع بيانات وصفية فقط. إرجاع التصنيفات الخاطئة أو الفارغة سيعيد الخلل.
    }

    private function displayName(string $name): string
    {
        $normalized = str_replace(['.', '-'], '_', $name);
        $special = [
            'complaints_read' => 'عرض الشكاوى',
            'full_contract' => 'عرض العقد الكامل',
            'set_perms' => 'تعيين الصلاحيات للأدوار',
            'developers_perms' => 'إدارة صلاحيات المطور',
        ];

        if (isset($special[$normalized])) {
            return $special[$normalized];
        }

        $actions = [
            'change_status' => 'تغيير حالة', 'toggle_status' => 'تفعيل أو تعطيل',
            'force_delete' => 'حذف نهائي', 'trashed_view' => 'عرض المحذوف',
            'create' => 'إضافة', 'view' => 'عرض', 'read' => 'عرض',
            'edit' => 'تعديل', 'update' => 'تعديل', 'delete' => 'حذف',
            'control' => 'إدارة', 'manage' => 'إدارة', 'assign' => 'تعيين',
            'activate' => 'تفعيل', 'deactivate' => 'إلغاء تفعيل',
            'restore' => 'استعادة', 'export' => 'تصدير', 'import' => 'استيراد',
            'approve' => 'اعتماد', 'reject' => 'رفض', 'send' => 'إرسال',
        ];
        $resources = [
            'blogs' => 'المدونة', 'cancel_contract' => 'طلبات إلغاء العقود',
            'find_us' => 'قسم أين تجدنا', 'guidelines' => 'الإرشادات',
            'home_hero' => 'واجهة الصفحة الرئيسية', 'home_sections' => 'أقسام الصفحة الرئيسية',
            'musaned_section' => 'قسم مساند', 'rec_offices' => 'مكاتب الاستقدام',
            'recruitment_offices' => 'مكاتب الاستقدام',
            'recruitment_requirements' => 'متطلبات الاستقدام',
            'relegions' => 'الأديان', 'religions' => 'الأديان', 'roles' => 'الأدوار',
            'stat' => 'الإحصائيات', 'users' => 'المستخدمين',
            'why_choose_us' => 'قسم لماذا تختارنا', 'why_us' => 'قسم لماذا نحن',
        ];

        foreach ($actions as $suffix => $action) {
            if (str_ends_with($normalized, '_' . $suffix)) {
                $resource = substr($normalized, 0, -strlen('_' . $suffix));

                return $action . ' ' . ($resources[$resource] ?? str_replace('_', ' ', $resource));
            }
        }

        return 'صلاحية ' . str_replace('_', ' ', $normalized);
    }

    /** @return array<string, string> */
    private function knownDisplayNames(): array
    {
        return [
            'complaint_response' => 'الرد على الشكاوى',
            'partenr_view' => 'عرض الشركاء',
            'partenr_create' => 'إضافة شريك',
            'partenr_edit' => 'تعديل شريك',
            'partenr_delete' => 'حذف شريك',
            'partenr_control' => 'إدارة الشركاء',
            'post_arrival_refund_view' => 'عرض مستندات الاسترداد بعد الوصول',
            'post_arrival_refund_create' => 'إنشاء مستند استرداد بعد الوصول',
            'post_arrival_refund_post' => 'اعتماد مستند استرداد بعد الوصول',
            'post_arrival_refund_payout' => 'صرف استرداد بعد الوصول',
            'post_arrival_refund_reverse' => 'عكس بند استرداد بعد الوصول',
        ];
    }
};
