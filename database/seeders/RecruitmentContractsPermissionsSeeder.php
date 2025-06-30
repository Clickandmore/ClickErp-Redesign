<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\NewPermKeys;

class RecruitmentContractsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // حذف الصلاحيات القديمة لعقود الاستقدام
        $oldPermissions = [
            'rec_contract_control',
            'rec_contract_delete',
            'rec_contract_view',
        ];

        foreach ($oldPermissions as $permission) {
            Permission::where('name', $permission)->delete();
        }

        // تعريف الصلاحيات الجديدة لعقود الاستقدام
        $permissions = [
            // الصلاحيات الأساسية للعقود
            [
                'name' => NewPermKeys::REC_CONTRACTS_VIEW,
                'display_name' => 'عرض عقود الاستقدام',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_CREATE,
                'display_name' => 'إنشاء عقد استقدام جديد',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_EDIT,
                'display_name' => 'تعديل عقد الاستقدام',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_DELETE,
                'display_name' => 'حذف عقد الاستقدام',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_EXPORT,
                'display_name' => 'تصدير عقود الاستقدام',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_PRINT,
                'display_name' => 'طباعة عقد الاستقدام',
                'guard_name' => 'admin'
            ],

            // صلاحيات تغيير حالة العقد (مرحلة بمرحلة)
            [
                'name' => NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED,
                'display_name' => 'تغيير من بانتظار الاعتماد إلى عقود مساند',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFWED,
                'display_name' => 'تغيير من عقود مساند إلى التفويض',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFFEZ,
                'display_name' => 'تغيير من التفويض إلى التفييز',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAZKER,
                'display_name' => 'تغيير من التفييز إلى التذكرة',
                'guard_name' => 'admin'
            ],

            // صلاحيات إدارة الرحلات (في مرحلة التذكرة)
            [
                'name' => NewPermKeys::REC_CONTRACTS_ADD_TRAVEL,
                'display_name' => 'إضافة رحلة للعقد',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_EDIT_TRAVEL,
                'display_name' => 'تعديل رحلة العقد',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_DELETE_TRAVEL,
                'display_name' => 'حذف رحلة العقد',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL,
                'display_name' => 'تحديد اكتمال الرحلة',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_VIEW_TRAVEL,
                'display_name' => 'عرض تفاصيل الرحلة',
                'guard_name' => 'admin'
            ],

            // صلاحيات إدارة الضمان
            [
                'name' => NewPermKeys::REC_CONTRACTS_WARRANTY_VIEW,
                'display_name' => 'عرض العقود تحت الضمان',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_WARRANTY_MANAGE,
                'display_name' => 'إدارة فترة الضمان',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_CLOSE_AFTER_WARRANTY,
                'display_name' => 'إغلاق العقد بعد انتهاء الضمان',
                'guard_name' => 'admin'
            ],

            // صلاحيات طلبات الإلغاء
            [
                'name' => NewPermKeys::REC_CONTRACTS_CANCEL_VIEW,
                'display_name' => 'عرض طلبات إلغاء العقود',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_CANCEL_CREATE,
                'display_name' => 'إنشاء طلب إلغاء عقد',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_CANCEL_EDIT,
                'display_name' => 'تعديل طلب إلغاء العقد',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_CANCEL_DELETE,
                'display_name' => 'حذف طلب إلغاء العقد',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_CANCEL_APPROVE,
                'display_name' => 'الموافقة على إلغاء العقد',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_CANCEL_REJECT,
                'display_name' => 'رفض إلغاء العقد',
                'guard_name' => 'admin'
            ],

            // صلاحيات المرتجعات
            [
                'name' => NewPermKeys::REC_CONTRACTS_RETURN,
                'display_name' => 'إرجاع عقد الاستقدام',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_RETURN_VIEW,
                'display_name' => 'عرض العقود المرتجعة',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_RETURN_MANAGE,
                'display_name' => 'إدارة المرتجعات',
                'guard_name' => 'admin'
            ],

            // صلاحيات الإدارة العامة
            [
                'name' => NewPermKeys::REC_CONTRACTS_MANAGE,
                'display_name' => 'إدارة عقود الاستقدام',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_REPORTS,
                'display_name' => 'تقارير عقود الاستقدام',
                'guard_name' => 'admin'
            ],
            [
                'name' => NewPermKeys::REC_CONTRACTS_ANALYTICS,
                'display_name' => 'إحصائيات عقود الاستقدام',
                'guard_name' => 'admin'
            ],
        ];

        // إنشاء الصلاحيات
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('تم إنشاء صلاحيات عقود الاستقدام بنجاح!');
    }
}
