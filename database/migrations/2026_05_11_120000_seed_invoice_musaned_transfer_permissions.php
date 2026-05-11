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
                'section_name' => 'recruitment_contracts',
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
        if (!Schema::hasTable('permissions')) {
            return;
        }

        DB::table('permissions')
            ->where('guard_name', 'admin')
            ->whereIn('name', array_column($this->permissions(), 'name'))
            ->delete();
    }

    private function permissions(): array
    {
        return [
            [
                'name' => 'invoices_view',
                'group' => 'invoices',
                'display_name' => 'عرض الفواتير',
                'description' => 'إمكانية عرض قائمة الفواتير وتفاصيل الفاتورة من العقود أو تفاويض الإنجاز.',
            ],
            [
                'name' => 'invoices_create',
                'group' => 'invoices',
                'display_name' => 'إضافة الفواتير',
                'description' => 'إمكانية إنشاء فاتورة لعقد استقدام أو تفويض إنجاز.',
            ],
            [
                'name' => 'invoices_export',
                'group' => 'invoices',
                'display_name' => 'تصدير الفواتير',
                'description' => 'إمكانية تصدير الفواتير بصيغ Excel أو PDF.',
            ],
            [
                'name' => 'invoices_print',
                'group' => 'invoices',
                'display_name' => 'طباعة الفواتير',
                'description' => 'إمكانية طباعة أو تنزيل الفاتورة.',
            ],
            [
                'name' => 'invoices_control',
                'group' => 'invoices',
                'display_name' => 'إدارة الفواتير',
                'description' => 'إمكانية إدارة جميع عمليات الفواتير.',
            ],
            [
                'name' => 'musaned_transfers_view',
                'group' => 'musaned_transfers',
                'display_name' => 'عرض تحويلات مساند',
                'description' => 'إمكانية عرض قائمة تحويلات مساند وتفاصيل التحويل.',
            ],
            [
                'name' => 'musaned_transfers_create',
                'group' => 'musaned_transfers',
                'display_name' => 'تسجيل تحويل مساند',
                'description' => 'إمكانية تسجيل حوالة مساند مرتبطة بفواتير العقود أو تفاويض الإنجاز.',
            ],
            [
                'name' => 'musaned_transfers_control',
                'group' => 'musaned_transfers',
                'display_name' => 'إدارة تحويلات مساند',
                'description' => 'إمكانية إدارة جميع عمليات تحويلات مساند.',
            ],
        ];
    }
};
