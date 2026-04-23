<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        $hasDisplayName = Schema::hasColumn('permissions', 'display_name');
        $hasDescription = Schema::hasColumn('permissions', 'description');
        $hasIsActive = Schema::hasColumn('permissions', 'is_active');

        foreach ($this->permissions() as $permission) {
            $payload = [
                'group_name' => $permission['group'],
                'section_name' => $permission['section'],
            ];

            if ($hasDisplayName) {
                $payload['display_name'] = $permission['display_name'];
            }

            if ($hasDescription) {
                $payload['description'] = $permission['description'];
            }

            if ($hasIsActive) {
                $payload['is_active'] = true;
            }

            DB::table('permissions')->updateOrInsert(
                [
                    'name' => $permission['name'],
                    'guard_name' => 'admin',
                ],
                $payload
            );
        }
    }

    public function down(): void
    {
        // Keep the seeded CV permissions in place to avoid breaking existing roles on rollback.
    }

    private function permissions(): array
    {
        return [
            [
                'name' => 'maids_view',
                'group' => 'maids',
                'section' => 'cv_management',
                'display_name' => 'عرض السير الذاتية',
                'description' => 'إمكانية عرض قائمة السير الذاتية',
            ],
            [
                'name' => 'maids_create',
                'group' => 'maids',
                'section' => 'cv_management',
                'display_name' => 'إضافة سيرة ذاتية',
                'description' => 'إمكانية إضافة سيرة ذاتية جديدة',
            ],
            [
                'name' => 'maids_edit',
                'group' => 'maids',
                'section' => 'cv_management',
                'display_name' => 'تعديل السير الذاتية',
                'description' => 'إمكانية تعديل السير الذاتية',
            ],
            [
                'name' => 'maids_delete',
                'group' => 'maids',
                'section' => 'cv_management',
                'display_name' => 'حذف السير الذاتية',
                'description' => 'إمكانية حذف السير الذاتية',
            ],
            [
                'name' => 'maids_export',
                'group' => 'maids',
                'section' => 'cv_management',
                'display_name' => 'تصدير السير الذاتية',
                'description' => 'إمكانية تصدير السير الذاتية',
            ],
            [
                'name' => 'maids_export_pdf',
                'group' => 'maids',
                'section' => 'cv_management',
                'display_name' => 'تصدير السير الذاتية بصيغة PDF',
                'description' => 'إمكانية تصدير السير الذاتية بصيغة PDF',
            ],
            [
                'name' => 'maids_export_excel',
                'group' => 'maids',
                'section' => 'cv_management',
                'display_name' => 'تصدير السير الذاتية بصيغة Excel',
                'description' => 'إمكانية تصدير السير الذاتية بصيغة Excel',
            ],
            [
                'name' => 'maids_show',
                'group' => 'maids',
                'section' => 'cv_management',
                'display_name' => 'عرض تفاصيل السيرة الذاتية',
                'description' => 'إمكانية عرض تفاصيل السيرة الذاتية',
            ],
            [
                'name' => 'maids_toggle_status',
                'group' => 'maids',
                'section' => 'cv_management',
                'display_name' => 'تغيير حالة الظهور',
                'description' => 'إمكانية تغيير حالة ظهور السيرة الذاتية',
            ],
            [
                'name' => 'maids_toggle_pin',
                'group' => 'maids',
                'section' => 'cv_management',
                'display_name' => 'تثبيت/إلغاء تثبيت',
                'description' => 'إمكانية تثبيت أو إلغاء تثبيت السيرة الذاتية',
            ],
            [
                'name' => 'maids_control',
                'group' => 'maids',
                'section' => 'cv_management',
                'display_name' => 'التحكم في السير الذاتية',
                'description' => 'إمكانية التحكم في عمليات السير الذاتية',
            ],
            [
                'name' => 'maids_rental_view',
                'group' => 'maids_rental',
                'section' => 'cv_management',
                'display_name' => 'عرض سير عاملات الإيجار',
                'description' => 'إمكانية عرض قائمة سير عاملات الإيجار',
            ],
            [
                'name' => 'maids_backout_view',
                'group' => 'maids_backout',
                'section' => 'cv_management',
                'display_name' => 'عرض عاملات باك أوت',
                'description' => 'إمكانية عرض قائمة عاملات الباك أوت',
            ],
            [
                'name' => 'maids_deleted_view',
                'group' => 'maids_deleted',
                'section' => 'cv_management',
                'display_name' => 'عرض السير الذاتية المحذوفة',
                'description' => 'إمكانية عرض قائمة السير الذاتية المحذوفة',
            ],
            [
                'name' => 'cv_view',
                'group' => 'cv_legacy',
                'section' => 'cv_management',
                'display_name' => 'عرض السير الذاتية القديمة',
                'description' => 'إمكانية عرض صلاحيات السير الذاتية القديمة',
            ],
            [
                'name' => 'cv_create',
                'group' => 'cv_legacy',
                'section' => 'cv_management',
                'display_name' => 'إضافة سيرة ذاتية قديمة',
                'description' => 'إمكانية إضافة سيرة ذاتية بالنظام القديم',
            ],
            [
                'name' => 'cv_edit',
                'group' => 'cv_legacy',
                'section' => 'cv_management',
                'display_name' => 'تعديل السيرة الذاتية القديمة',
                'description' => 'إمكانية تعديل السيرة الذاتية بالنظام القديم',
            ],
            [
                'name' => 'cv_delete',
                'group' => 'cv_legacy',
                'section' => 'cv_management',
                'display_name' => 'حذف السيرة الذاتية القديمة',
                'description' => 'إمكانية حذف السيرة الذاتية بالنظام القديم',
            ],
            [
                'name' => 'cv_pin',
                'group' => 'cv_legacy',
                'section' => 'cv_management',
                'display_name' => 'تثبيت السيرة الذاتية القديمة',
                'description' => 'إمكانية تثبيت السيرة الذاتية بالنظام القديم',
            ],
            [
                'name' => 'cv_unpin',
                'group' => 'cv_legacy',
                'section' => 'cv_management',
                'display_name' => 'إلغاء تثبيت السيرة الذاتية القديمة',
                'description' => 'إمكانية إلغاء تثبيت السيرة الذاتية بالنظام القديم',
            ],
            [
                'name' => 'cv_approve',
                'group' => 'cv_legacy',
                'section' => 'cv_management',
                'display_name' => 'اعتماد السيرة الذاتية القديمة',
                'description' => 'إمكانية اعتماد السيرة الذاتية بالنظام القديم',
            ],
            [
                'name' => 'cv_reject',
                'group' => 'cv_legacy',
                'section' => 'cv_management',
                'display_name' => 'رفض السيرة الذاتية القديمة',
                'description' => 'إمكانية رفض السيرة الذاتية بالنظام القديم',
            ],
            [
                'name' => 'cv_export',
                'group' => 'cv_legacy',
                'section' => 'cv_management',
                'display_name' => 'تصدير السير الذاتية القديمة',
                'description' => 'إمكانية تصدير السير الذاتية بالنظام القديم',
            ],
            [
                'name' => 'cv_import',
                'group' => 'cv_legacy',
                'section' => 'cv_management',
                'display_name' => 'استيراد السير الذاتية القديمة',
                'description' => 'إمكانية استيراد السير الذاتية بالنظام القديم',
            ],
            [
                'name' => 'cv_manage',
                'group' => 'cv_legacy',
                'section' => 'cv_management',
                'display_name' => 'إدارة السير الذاتية القديمة',
                'description' => 'إمكانية إدارة السير الذاتية بالنظام القديم',
            ],
            [
                'name' => 'cv_download',
                'group' => 'cv_legacy',
                'section' => 'cv_management',
                'display_name' => 'تحميل السيرة الذاتية القديمة',
                'description' => 'إمكانية تحميل السيرة الذاتية بالنظام القديم',
            ],
            [
                'name' => 'cv_upload',
                'group' => 'cv_legacy',
                'section' => 'cv_management',
                'display_name' => 'رفع السيرة الذاتية القديمة',
                'description' => 'إمكانية رفع السيرة الذاتية بالنظام القديم',
            ],
            [
                'name' => 'cv_convert',
                'group' => 'cv_legacy',
                'section' => 'cv_management',
                'display_name' => 'تحويل السيرة الذاتية القديمة',
                'description' => 'إمكانية تحويل السيرة الذاتية بالنظام القديم',
            ],
            [
                'name' => 'cv_analytics',
                'group' => 'cv_legacy',
                'section' => 'cv_management',
                'display_name' => 'تحليلات السير الذاتية القديمة',
                'description' => 'إمكانية عرض تحليلات السير الذاتية بالنظام القديم',
            ],
            [
                'name' => 'cv_reports',
                'group' => 'cv_legacy',
                'section' => 'cv_management',
                'display_name' => 'تقارير السير الذاتية القديمة',
                'description' => 'إمكانية عرض تقارير السير الذاتية بالنظام القديم',
            ],
        ];
    }
};
