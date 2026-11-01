<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * صلاحية إعدادات الدليل المحاسبي (حصرية: لا تكفي finance_module) وصلاحيات بنود تقرير صندوق اليومية.
 * شاشة تعيين الصلاحيات تقرأ من جدول permissions، فلا تظهر الصلاحية هناك قبل تسجيلها.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $hasDisplayName = Schema::hasColumn('permissions', 'display_name');
        $hasIsActive = Schema::hasColumn('permissions', 'is_active');

        foreach ($this->permissions() as $name => $displayName) {
            $payload = ['group_name' => 'finance', 'section_name' => 'modules'];
            if ($hasDisplayName) {
                $payload['display_name'] = $displayName;
            }
            if ($hasIsActive) {
                $payload['is_active'] = true;
            }

            DB::table('permissions')->updateOrInsert(['name' => $name, 'guard_name' => 'admin'], $payload);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')->where('guard_name', 'admin')->whereIn('name', array_keys($this->permissions()))->delete();
        }
    }

    private function permissions(): array
    {
        return [
            'finance_chart_settings_manage' => 'إدارة إعدادات الدليل المحاسبي',
            'finance_daily_cash_reference_lines_view' => 'عرض بنود تقرير صندوق اليومية',
            'finance_daily_cash_reference_lines_create' => 'إضافة بنود تقرير صندوق اليومية',
            'finance_daily_cash_reference_lines_edit' => 'تعديل بنود تقرير صندوق اليومية',
            'finance_daily_cash_reference_lines_delete' => 'حذف بنود تقرير صندوق اليومية',
        ];
    }
};
