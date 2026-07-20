<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\PermKeys;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MaidSidebarPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            [
                'name' => PermKeys::MAIDS_VIEW,
                'display_name' => 'عرض السير الذاتية',
                'description' => 'إمكانية عرض قائمة السير الذاتية',
                'group_name' => 'maids',
                'section_name' => 'cv_management',
            ],
            [
                'name' => PermKeys::MAIDS_CREATE,
                'display_name' => 'إضافة سيرة ذاتية',
                'description' => 'إمكانية إضافة سيرة ذاتية جديدة',
                'group_name' => 'maids',
                'section_name' => 'cv_management',
            ],
            [
                'name' => PermKeys::MAIDS_EDIT,
                'display_name' => 'تعديل السير الذاتية',
                'description' => 'إمكانية تعديل السير الذاتية',
                'group_name' => 'maids',
                'section_name' => 'cv_management',
            ],
            [
                'name' => PermKeys::MAIDS_DELETE,
                'display_name' => 'حذف السير الذاتية',
                'description' => 'إمكانية حذف السير الذاتية',
                'group_name' => 'maids',
                'section_name' => 'cv_management',
            ],
            [
                'name' => PermKeys::MAIDS_EXPORT,
                'display_name' => 'تصدير السير الذاتية',
                'description' => 'إمكانية تصدير السير الذاتية',
                'group_name' => 'maids',
                'section_name' => 'cv_management',
            ],
            [
                'name' => PermKeys::MAIDS_EXPORT_PDF,
                'display_name' => 'تصدير السير الذاتية بصيغة PDF',
                'description' => 'إمكانية تصدير السير الذاتية بصيغة PDF',
                'group_name' => 'maids',
                'section_name' => 'cv_management',
            ],
            [
                'name' => PermKeys::MAIDS_EXPORT_EXCEL,
                'display_name' => 'تصدير السير الذاتية بصيغة Excel',
                'description' => 'إمكانية تصدير السير الذاتية بصيغة Excel',
                'group_name' => 'maids',
                'section_name' => 'cv_management',
            ],
            [
                'name' => PermKeys::MAIDS_SHOW,
                'display_name' => 'عرض تفاصيل السيرة الذاتية',
                'description' => 'إمكانية عرض تفاصيل السيرة الذاتية',
                'group_name' => 'maids',
                'section_name' => 'cv_management',
            ],
            [
                'name' => PermKeys::MAIDS_TOGGLE_STATUS,
                'display_name' => 'تغيير حالة الظهور',
                'description' => 'إمكانية تغيير حالة ظهور السيرة الذاتية',
                'group_name' => 'maids',
                'section_name' => 'cv_management',
            ],
            [
                'name' => PermKeys::MAIDS_BULK_VISIBILITY,
                'display_name' => 'إظهار وإخفاء جميع السير',
                'description' => 'إمكانية إظهار أو إخفاء جميع السير الذاتية دفعة واحدة',
                'group_name' => 'maids',
                'section_name' => 'cv_management',
            ],
            [
                'name' => PermKeys::MAIDS_TOGGLE_PIN,
                'display_name' => 'تثبيت/إلغاء تثبيت',
                'description' => 'إمكانية تثبيت أو إلغاء تثبيت السيرة الذاتية',
                'group_name' => 'maids',
                'section_name' => 'cv_management',
            ],
            [
                'name' => PermKeys::MAIDS_CONTROL,
                'display_name' => 'التحكم في السير الذاتية',
                'description' => 'إمكانية التحكم في عمليات السير الذاتية',
                'group_name' => 'maids',
                'section_name' => 'cv_management',
            ],
            [
                'name' => PermKeys::MAIDS_RENTAL_VIEW,
                'display_name' => 'عرض سير عاملات الإيجار',
                'description' => 'إمكانية عرض قائمة سير عاملات الإيجار',
                'group_name' => 'maids_rental',
                'section_name' => 'cv_management',
            ],
            [
                'name' => PermKeys::MAIDS_BACKOUT_VIEW,
                'display_name' => 'عرض عاملات باك أوت',
                'description' => 'إمكانية عرض قائمة عاملات الباك أوت',
                'group_name' => 'maids_backout',
                'section_name' => 'cv_management',
            ],
            [
                'name' => PermKeys::MAIDS_DELETED_VIEW,
                'display_name' => 'عرض السير الذاتية المحذوفة',
                'description' => 'إمكانية عرض قائمة السير الذاتية المحذوفة',
                'group_name' => 'maids_deleted',
                'section_name' => 'cv_management',
            ],
            [
                'name' => 'cv_view',
                'display_name' => 'عرض السير الذاتية القديمة',
                'description' => 'إمكانية عرض صلاحيات السير الذاتية القديمة',
                'group_name' => 'cv_legacy',
                'section_name' => 'cv_management',
            ],
            [
                'name' => 'cv_create',
                'display_name' => 'إضافة سيرة ذاتية قديمة',
                'description' => 'إمكانية إضافة سيرة ذاتية بالنظام القديم',
                'group_name' => 'cv_legacy',
                'section_name' => 'cv_management',
            ],
            [
                'name' => 'cv_edit',
                'display_name' => 'تعديل السيرة الذاتية القديمة',
                'description' => 'إمكانية تعديل السيرة الذاتية بالنظام القديم',
                'group_name' => 'cv_legacy',
                'section_name' => 'cv_management',
            ],
            [
                'name' => 'cv_delete',
                'display_name' => 'حذف السيرة الذاتية القديمة',
                'description' => 'إمكانية حذف السيرة الذاتية بالنظام القديم',
                'group_name' => 'cv_legacy',
                'section_name' => 'cv_management',
            ],
            [
                'name' => 'cv_pin',
                'display_name' => 'تثبيت السيرة الذاتية القديمة',
                'description' => 'إمكانية تثبيت السيرة الذاتية بالنظام القديم',
                'group_name' => 'cv_legacy',
                'section_name' => 'cv_management',
            ],
            [
                'name' => 'cv_unpin',
                'display_name' => 'إلغاء تثبيت السيرة الذاتية القديمة',
                'description' => 'إمكانية إلغاء تثبيت السيرة الذاتية بالنظام القديم',
                'group_name' => 'cv_legacy',
                'section_name' => 'cv_management',
            ],
            [
                'name' => 'cv_approve',
                'display_name' => 'اعتماد السيرة الذاتية القديمة',
                'description' => 'إمكانية اعتماد السيرة الذاتية بالنظام القديم',
                'group_name' => 'cv_legacy',
                'section_name' => 'cv_management',
            ],
            [
                'name' => 'cv_reject',
                'display_name' => 'رفض السيرة الذاتية القديمة',
                'description' => 'إمكانية رفض السيرة الذاتية بالنظام القديم',
                'group_name' => 'cv_legacy',
                'section_name' => 'cv_management',
            ],
            [
                'name' => 'cv_export',
                'display_name' => 'تصدير السير الذاتية القديمة',
                'description' => 'إمكانية تصدير السير الذاتية بالنظام القديم',
                'group_name' => 'cv_legacy',
                'section_name' => 'cv_management',
            ],
            [
                'name' => 'cv_import',
                'display_name' => 'استيراد السير الذاتية القديمة',
                'description' => 'إمكانية استيراد السير الذاتية بالنظام القديم',
                'group_name' => 'cv_legacy',
                'section_name' => 'cv_management',
            ],
            [
                'name' => 'cv_manage',
                'display_name' => 'إدارة السير الذاتية القديمة',
                'description' => 'إمكانية إدارة السير الذاتية بالنظام القديم',
                'group_name' => 'cv_legacy',
                'section_name' => 'cv_management',
            ],
            [
                'name' => 'cv_download',
                'display_name' => 'تحميل السيرة الذاتية القديمة',
                'description' => 'إمكانية تحميل السيرة الذاتية بالنظام القديم',
                'group_name' => 'cv_legacy',
                'section_name' => 'cv_management',
            ],
            [
                'name' => 'cv_upload',
                'display_name' => 'رفع السيرة الذاتية القديمة',
                'description' => 'إمكانية رفع السيرة الذاتية بالنظام القديم',
                'group_name' => 'cv_legacy',
                'section_name' => 'cv_management',
            ],
            [
                'name' => 'cv_convert',
                'display_name' => 'تحويل السيرة الذاتية القديمة',
                'description' => 'إمكانية تحويل السيرة الذاتية بالنظام القديم',
                'group_name' => 'cv_legacy',
                'section_name' => 'cv_management',
            ],
            [
                'name' => 'cv_analytics',
                'display_name' => 'تحليلات السير الذاتية القديمة',
                'description' => 'إمكانية عرض تحليلات السير الذاتية بالنظام القديم',
                'group_name' => 'cv_legacy',
                'section_name' => 'cv_management',
            ],
            [
                'name' => 'cv_reports',
                'display_name' => 'تقارير السير الذاتية القديمة',
                'description' => 'إمكانية عرض تقارير السير الذاتية بالنظام القديم',
                'group_name' => 'cv_legacy',
                'section_name' => 'cv_management',
            ],
        ];

        foreach ($permissions as $attributes) {
            Permission::withoutGlobalScopes()->updateOrCreate(
                [
                    'name' => $attributes['name'],
                    'guard_name' => 'admin',
                ],
                array_merge($attributes, ['guard_name' => 'admin', 'is_active' => true])
            );
        }

        $sidebarViewPermissions = [
            PermKeys::MAIDS_RENTAL_VIEW,
            PermKeys::MAIDS_BACKOUT_VIEW,
            PermKeys::MAIDS_DELETED_VIEW,
        ];

        Role::query()
            ->where('guard_name', 'admin')
            ->get()
            ->each(function (Role $role) use ($sidebarViewPermissions) {
                if ($role->permissions()->where('name', PermKeys::MAIDS_VIEW)->exists()) {
                    $role->givePermissionTo($sidebarViewPermissions);
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
