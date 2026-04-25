<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use Spatie\Permission\Models\Role;

class CleanAndAddRecruitmentContractsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🧹 جاري حذف جميع صلاحيات العقود الموجودة...');

        // حذف جميع صلاحيات العقود (rec_contract_ و rec_contracts_)
        $deletedCount = Permission::where('name', 'like', 'rec_contract_%')
            ->orWhere('name', 'like', 'rec_contracts_%')
            ->delete();

        $this->command->info("✅ تم حذف {$deletedCount} صلاحية قديمة");

        $this->command->info('🔄 جاري إضافة صلاحيات عقود الاستقدام الجديدة...');

        // الصلاحيات الأساسية للعقود
        $permissions = [
            // عرض وإدارة العقود
            [
                'name' => 'rec_contracts_view',
                'display_name' => 'عرض عقود الاستقدام',
                'description' => 'إمكانية عرض قائمة عقود الاستقدام'
            ],
            [
                'name' => 'rec_contracts_create',
                'display_name' => 'إنشاء عقد استقدام جديد',
                'description' => 'إمكانية إنشاء عقد استقدام جديد'
            ],
            [
                'name' => 'rec_contracts_edit',
                'display_name' => 'تعديل عقد الاستقدام',
                'description' => 'إمكانية تعديل بيانات عقد الاستقدام'
            ],
            [
                'name' => 'rec_contracts_update',
                'display_name' => 'تحديث مراحل وبيانات عقد الاستقدام',
                'description' => 'إمكانية تحديث مراحل العقد، اختيار العاملة، إضافة الملاحظات، وإدارة الإرسالية'
            ],
            [
                'name' => 'rec_contracts_delete',
                'display_name' => 'حذف عقد الاستقدام',
                'description' => 'إمكانية حذف عقد الاستقدام'
            ],
            [
                'name' => 'rec_contracts_export',
                'display_name' => 'تصدير عقود الاستقدام',
                'description' => 'إمكانية تصدير بيانات عقود الاستقدام'
            ],
            [
                'name' => 'rec_contracts_print',
                'display_name' => 'طباعة عقد الاستقدام',
                'description' => 'إمكانية طباعة عقد الاستقدام'
            ],

            // صلاحيات تغيير حالة العقد (مرحلة بمرحلة)
            [
                'name' => 'rec_contracts_change_to_msaned',
                'display_name' => 'تغيير إلى عقود مساند',
                'description' => 'تغيير حالة العقد من بانتظار الاعتماد إلى عقود مساند'
            ],
            [
                'name' => 'rec_contracts_change_to_tafwed',
                'display_name' => 'تغيير إلى التفويض',
                'description' => 'تغيير حالة العقد من عقود مساند إلى التفويض'
            ],
            [
                'name' => 'rec_contracts_change_to_taffez',
                'display_name' => 'تغيير إلى التفييز',
                'description' => 'تغيير حالة العقد من التفويض إلى التفييز'
            ],
            [
                'name' => 'rec_contracts_change_to_tazker',
                'display_name' => 'تغيير إلى التذكرة',
                'description' => 'تغيير حالة العقد من التفييز إلى التذكرة'
            ],

            // صلاحيات إدارة الرحلات
            [
                'name' => 'rec_contracts_add_travel',
                'display_name' => 'إضافة رحلة للعقد',
                'description' => 'إمكانية إضافة رحلة جديدة للعقد'
            ],
            [
                'name' => 'rec_contracts_edit_travel',
                'display_name' => 'تعديل رحلة العقد',
                'description' => 'إمكانية تعديل بيانات رحلة العقد'
            ],
            [
                'name' => 'rec_contracts_delete_travel',
                'display_name' => 'حذف رحلة العقد',
                'description' => 'إمكانية حذف رحلة من العقد'
            ],
            [
                'name' => 'rec_contracts_complete_travel',
                'display_name' => 'تحديد اكتمال الرحلة',
                'description' => 'إمكانية تحديد اكتمال الرحلة'
            ],
            [
                'name' => 'rec_contracts_view_travel',
                'display_name' => 'عرض تفاصيل الرحلة',
                'description' => 'إمكانية عرض تفاصيل رحلة العقد'
            ],

            // صلاحيات إدارة الضمان
            [
                'name' => 'rec_contracts_warranty_view',
                'display_name' => 'عرض العقود تحت الضمان',
                'description' => 'إمكانية عرض العقود التي تحت الضمان'
            ],
            [
                'name' => 'rec_contracts_warranty_manage',
                'display_name' => 'إدارة فترة الضمان',
                'description' => 'إمكانية إدارة فترة الضمان للعقود'
            ],
            [
                'name' => 'rec_contracts_close_after_warranty',
                'display_name' => 'إغلاق العقد بعد انتهاء الضمان',
                'description' => 'إمكانية إغلاق العقد بعد انتهاء فترة الضمان'
            ],

            // صلاحيات المرتجعات
            [
                'name' => 'rec_contracts_return',
                'display_name' => 'إرجاع عقد الاستقدام',
                'description' => 'إمكانية إرجاع عقد الاستقدام'
            ],
            [
                'name' => 'rec_contracts_return_view',
                'display_name' => 'عرض العقود المرتجعة',
                'description' => 'إمكانية عرض العقود المرتجعة'
            ],
            [
                'name' => 'rec_contracts_return_manage',
                'display_name' => 'إدارة المرتجعات',
                'description' => 'إمكانية إدارة عمليات الإرجاع'
            ],

            // صلاحيات الإدارة العامة
            [
                'name' => 'rec_contracts_manage',
                'display_name' => 'إدارة عقود الاستقدام',
                'description' => 'إمكانية إدارة جميع جوانب عقود الاستقدام'
            ],
            [
                'name' => 'rec_contracts_reports',
                'display_name' => 'تقارير عقود الاستقدام',
                'description' => 'إمكانية عرض وتوليد تقارير عقود الاستقدام'
            ],
            [
                'name' => 'rec_contracts_analytics',
                'display_name' => 'إحصائيات عقود الاستقدام',
                'description' => 'إمكانية عرض إحصائيات وتحليلات عقود الاستقدام'
            ],

            // صلاحيات طلبات الإلغاء
            [
                'name' => 'rec_contracts_cancel_view',
                'display_name' => 'عرض طلبات إلغاء العقود',
                'description' => 'إمكانية عرض طلبات إلغاء العقود'
            ],
            [
                'name' => 'rec_contracts_cancel_create',
                'display_name' => 'إنشاء طلب إلغاء عقد',
                'description' => 'إمكانية إنشاء طلب إلغاء عقد جديد'
            ],
            [
                'name' => 'rec_contracts_cancel_edit',
                'display_name' => 'تعديل طلب إلغاء العقد',
                'description' => 'إمكانية تعديل طلب إلغاء العقد'
            ],
            [
                'name' => 'rec_contracts_cancel_delete',
                'display_name' => 'حذف طلب إلغاء العقد',
                'description' => 'إمكانية حذف طلب إلغاء العقد'
            ],
            [
                'name' => 'rec_contracts_cancel_approve',
                'display_name' => 'الموافقة على إلغاء العقد',
                'description' => 'إمكانية الموافقة على طلب إلغاء العقد'
            ],
            [
                'name' => 'rec_contracts_cancel_reject',
                'display_name' => 'رفض إلغاء العقد',
                'description' => 'إمكانية رفض طلب إلغاء العقد'
            ],
        ];

        $groupOverrides = [
            'rec_contracts_cancel_view' => 'cancel_contract',
            'rec_contracts_cancel_create' => 'cancel_contract',
            'rec_contracts_cancel_edit' => 'cancel_contract',
            'rec_contracts_cancel_delete' => 'cancel_contract',
            'rec_contracts_cancel_approve' => 'cancel_contract',
            'rec_contracts_cancel_reject' => 'cancel_contract',
        ];

        // إضافة الصلاحيات
        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'description' => $permission['description'],
                'guard_name' => 'admin',
                'group_name' => $groupOverrides[$permission['name']] ?? 'rec_contracts',
                'section_name' => 'recruitment_contracts',
            ]);
        }

        // إضافة الصلاحيات إلى دور Administrator
        $administratorRole = Role::where('name', 'Administrator')->first();
        if ($administratorRole) {
            $permissionNames = array_column($permissions, 'name');
            $permissionsToAssign = Permission::whereIn('name', $permissionNames)->get();
            $administratorRole->syncPermissions($permissionsToAssign);
        }

        $this->command->info('✅ تم إضافة ' . count($permissions) . ' صلاحية لعقود الاستقدام بنجاح!');
        $this->command->info('🎉 تم اعتماد صلاحيات عقود الاستقدام الجديدة فقط!');
    }
}
