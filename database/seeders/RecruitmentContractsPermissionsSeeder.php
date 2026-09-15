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
                'name' => NewPermKeys::REC_CONTRACTS_UPDATE,
                'display_name' => 'تحديث مراحل وبيانات عقد الاستقدام',
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
            [
                'name' => NewPermKeys::REC_CONTRACTS_VIEW_SHELTER,
                'display_name' => 'عرض زر عقود الإيواء في عقود الاستقدام',
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
            [
                'name' => NewPermKeys::REC_CONTRACTS_RELEASE_MAID_AFTER_COMPLETION,
                'display_name' => 'اكتمال العقد وتسفير العاملة وإتاحتها للاستقدام',
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
        ];

        $descriptions = [
            NewPermKeys::REC_CONTRACTS_VIEW => 'إمكانية عرض عقود الاستقدام ومتابعة مراحلها.',
            NewPermKeys::REC_CONTRACTS_CREATE => 'إمكانية إنشاء عقد استقدام جديد.',
            NewPermKeys::REC_CONTRACTS_EDIT => 'إمكانية تعديل بيانات عقد الاستقدام الأساسية.',
            NewPermKeys::REC_CONTRACTS_UPDATE => 'إمكانية تحديث مراحل العقد، اختيار العاملة، إضافة الملاحظات، وإدارة الإرسالية.',
            NewPermKeys::REC_CONTRACTS_DELETE => 'إمكانية حذف عقد الاستقدام.',
            NewPermKeys::REC_CONTRACTS_EXPORT => 'إمكانية تصدير بيانات عقود الاستقدام.',
            NewPermKeys::REC_CONTRACTS_PRINT => 'إمكانية طباعة وثائق عقود الاستقدام.',
            NewPermKeys::REC_CONTRACTS_VIEW_SHELTER => 'إظهار زر عقود الإيواء وعرض عقود الإيواء فقط داخل شاشة عقود الاستقدام.',
            NewPermKeys::REC_CONTRACTS_CHANGE_TO_MSANED => 'إمكانية نقل العقد إلى مرحلة مساند.',
            NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFWED => 'إمكانية نقل العقد إلى مرحلة التفويض.',
            NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAFFEZ => 'إمكانية نقل العقد إلى مرحلة التفييز.',
            NewPermKeys::REC_CONTRACTS_CHANGE_TO_TAZKER => 'إمكانية نقل العقد إلى مرحلة التذكرة.',
            NewPermKeys::REC_CONTRACTS_ADD_TRAVEL => 'إمكانية إضافة رحلة مرتبطة بعقد الاستقدام.',
            NewPermKeys::REC_CONTRACTS_EDIT_TRAVEL => 'إمكانية تعديل بيانات رحلة عقد الاستقدام.',
            NewPermKeys::REC_CONTRACTS_DELETE_TRAVEL => 'إمكانية حذف رحلة عقد الاستقدام.',
            NewPermKeys::REC_CONTRACTS_COMPLETE_TRAVEL => 'إمكانية اعتماد اكتمال الرحلة ونقل العقد إلى الوصول.',
            NewPermKeys::REC_CONTRACTS_VIEW_TRAVEL => 'إمكانية عرض تفاصيل رحلة عقد الاستقدام.',
            NewPermKeys::REC_CONTRACTS_WARRANTY_VIEW => 'إمكانية عرض عقود الاستقدام ضمن فترة الضمان.',
            NewPermKeys::REC_CONTRACTS_WARRANTY_MANAGE => 'إمكانية إدارة فترة الضمان لعقود الاستقدام.',
            NewPermKeys::REC_CONTRACTS_CLOSE_AFTER_WARRANTY => 'إمكانية إغلاق العقد بعد انتهاء الضمان.',
            NewPermKeys::REC_CONTRACTS_RELEASE_MAID_AFTER_COMPLETION => 'إتاحة العاملة لعقد استقدام جديد بعد اكتمال مدة عقدها وعدم وجودها في الإيواء.',
            NewPermKeys::REC_CONTRACTS_CANCEL_VIEW => 'إمكانية عرض طلبات إلغاء عقود الاستقدام.',
            NewPermKeys::REC_CONTRACTS_CANCEL_CREATE => 'إمكانية إنشاء طلب إلغاء لعقد الاستقدام.',
            NewPermKeys::REC_CONTRACTS_CANCEL_EDIT => 'إمكانية تعديل طلب إلغاء عقد الاستقدام.',
            NewPermKeys::REC_CONTRACTS_CANCEL_DELETE => 'إمكانية حذف طلب إلغاء عقد الاستقدام.',
            NewPermKeys::REC_CONTRACTS_CANCEL_APPROVE => 'إمكانية الموافقة على طلب إلغاء عقد الاستقدام.',
            NewPermKeys::REC_CONTRACTS_CANCEL_REJECT => 'إمكانية رفض طلب إلغاء عقد الاستقدام.',
            NewPermKeys::REC_CONTRACTS_RETURN => 'إمكانية إرجاع عقد الاستقدام إلى المرحلة السابقة المسموح بها.',
            NewPermKeys::REC_CONTRACTS_RETURN_VIEW => 'إمكانية عرض العقود المرتجعة.',
            NewPermKeys::REC_CONTRACTS_RETURN_MANAGE => 'إمكانية إدارة عمليات إرجاع عقود الاستقدام.',
            NewPermKeys::REC_CONTRACTS_MANAGE => 'إمكانية الإدارة الشاملة لجميع عمليات عقود الاستقدام.',
            NewPermKeys::REC_CONTRACTS_REPORTS => 'إمكانية الوصول إلى تقارير عقود الاستقدام.',
            NewPermKeys::REC_CONTRACTS_ANALYTICS => 'إمكانية الوصول إلى إحصائيات وتحليلات عقود الاستقدام.',
        ];

        $groupOverrides = [
            NewPermKeys::REC_CONTRACTS_CANCEL_VIEW => 'cancel_contract',
            NewPermKeys::REC_CONTRACTS_CANCEL_CREATE => 'cancel_contract',
            NewPermKeys::REC_CONTRACTS_CANCEL_EDIT => 'cancel_contract',
            NewPermKeys::REC_CONTRACTS_CANCEL_DELETE => 'cancel_contract',
            NewPermKeys::REC_CONTRACTS_CANCEL_APPROVE => 'cancel_contract',
            NewPermKeys::REC_CONTRACTS_CANCEL_REJECT => 'cancel_contract',
        ];

        $permissions = array_map(function (array $permission) use ($descriptions, $groupOverrides) {
            return array_merge([
                'guard_name' => 'admin',
                'group_name' => $groupOverrides[$permission['name']] ?? 'rec_contracts',
                'section_name' => 'recruitment_contracts',
                'description' => $descriptions[$permission['name']] ?? ('إمكانية ' . $permission['display_name']),
            ], $permission);
        }, $permissions);

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
