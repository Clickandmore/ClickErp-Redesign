<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class OrdersPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // الصلاحيات الأساسية للطلبات
        $ordersPermissions = [
            [
                'name' => 'orders_create',
                'display_name' => 'إنشاء طلب',
                'description' => 'إمكانية إنشاء طلب جديد',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'orders_edit',
                'display_name' => 'تعديل الطلبات',
                'description' => 'إمكانية تعديل بيانات الطلبات',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'orders_export',
                'display_name' => 'تصدير الطلبات',
                'description' => 'إمكانية تصدير بيانات الطلبات',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'orders_approve',
                'display_name' => 'الموافقة على الطلبات',
                'description' => 'إمكانية الموافقة على الطلبات',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'orders_reject',
                'display_name' => 'رفض الطلبات',
                'description' => 'إمكانية رفض الطلبات',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'orders_assign',
                'display_name' => 'تعيين الطلبات',
                'description' => 'إمكانية تعيين الطلبات للموظفين',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'orders_manage',
                'display_name' => 'إدارة الطلبات',
                'description' => 'إمكانية إدارة جميع جوانب الطلبات',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'orders_change_status',
                'display_name' => 'تغيير حالة الطلب',
                'description' => 'إمكانية تغيير حالة الطلب (جديد، تحت الإجراء، مكتمل، ملغي)',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'orders_mark_in_progress',
                'display_name' => 'تحديد الطلب تحت الإجراء',
                'description' => 'إمكانية تغيير حالة الطلب إلى تحت الإجراء',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'orders_mark_cancelled',
                'display_name' => 'إلغاء الطلب',
                'description' => 'إمكانية إلغاء الطلب',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'orders_mark_completed',
                'display_name' => 'إكمال الطلب',
                'description' => 'إمكانية تحديد الطلب كمكتمل',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'orders_add_contract',
                'display_name' => 'إضافة عقد للطلب',
                'description' => 'إمكانية إضافة عقد للطلب',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'orders_view_contract',
                'display_name' => 'عرض عقد الطلب',
                'description' => 'إمكانية عرض عقد الطلب',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'orders_reports',
                'display_name' => 'تقارير الطلبات',
                'description' => 'إمكانية عرض تقارير الطلبات',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'orders_analytics',
                'display_name' => 'تحليلات الطلبات',
                'description' => 'إمكانية عرض تحليلات الطلبات',
                'guard_name' => 'admin'
            ],
        ];

        // الصلاحيات الأساسية لطلبات الاسترجاع
        $returnPermissions = [
            [
                'name' => 'return_request_create',
                'display_name' => 'إنشاء طلب استرجاع',
                'description' => 'إمكانية إنشاء طلب استرجاع جديد',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'return_request_edit',
                'display_name' => 'تعديل طلبات الاسترجاع',
                'description' => 'إمكانية تعديل بيانات طلبات الاسترجاع',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'return_request_approve',
                'display_name' => 'الموافقة على طلبات الاسترجاع',
                'description' => 'إمكانية الموافقة على طلبات الاسترجاع',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'return_request_reject',
                'display_name' => 'رفض طلبات الاسترجاع',
                'description' => 'إمكانية رفض طلبات الاسترجاع',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'return_request_manage',
                'display_name' => 'إدارة طلبات الاسترجاع',
                'description' => 'إمكانية إدارة جميع جوانب طلبات الاسترجاع',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'return_request_export',
                'display_name' => 'تصدير طلبات الاسترجاع',
                'description' => 'إمكانية تصدير بيانات طلبات الاسترجاع',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'return_request_change_status',
                'display_name' => 'تغيير حالة طلب الاسترجاع',
                'description' => 'إمكانية تغيير حالة طلب الاسترجاع',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'return_request_reports',
                'display_name' => 'تقارير طلبات الاسترجاع',
                'description' => 'إمكانية عرض تقارير طلبات الاسترجاع',
                'guard_name' => 'admin'
            ],
        ];

        // إضافة صلاحيات الطلبات
        foreach ($ordersPermissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                [
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description']
                ]
            );
        }

        // إضافة صلاحيات طلبات الاسترجاع
        foreach ($returnPermissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                [
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description']
                ]
            );
        }

        $this->command->info('تم إضافة صلاحيات الطلبات وطلبات الاسترجاع بنجاح!');
    }
}
