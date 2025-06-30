<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class ClientsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // الصلاحيات الأساسية للعملاء فقط
        $permissions = [
            [
                'name' => 'clients_export_pdf',
                'display_name' => 'تصدير العملاء بصيغة PDF',
                'description' => 'إمكانية تصدير بيانات العملاء بصيغة PDF',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'clients_export_excel',
                'display_name' => 'تصدير العملاء بصيغة Excel',
                'description' => 'إمكانية تصدير بيانات العملاء بصيغة Excel',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'clients_toggle_status',
                'display_name' => 'تغيير حالة العميل',
                'description' => 'إمكانية تفعيل أو إلغاء تفعيل العميل',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'clients_show',
                'display_name' => 'عرض تفاصيل العميل',
                'description' => 'إمكانية عرض تفاصيل العميل الكاملة',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'clients_manage',
                'display_name' => 'إدارة العملاء',
                'description' => 'إمكانية إدارة جميع عمليات العملاء',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'clients_import',
                'display_name' => 'استيراد بيانات العملاء',
                'description' => 'إمكانية استيراد بيانات العملاء من ملفات خارجية',
                'guard_name' => 'admin'
            ],
            [
                'name' => 'clients_create_recruitment',
                'display_name' => 'إنشاء طلب استقدام',
                'description' => 'إمكانية إنشاء طلب استقدام للعميل',
                'guard_name' => 'admin'
            ],
        ];

        // إضافة الصلاحيات إلى قاعدة البيانات
        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => $permission['guard_name']],
                [
                    'display_name' => $permission['display_name'],
                    'description' => $permission['description']
                ]
            );
        }

        $this->command->info('تم إضافة صلاحيات العملاء الأساسية بنجاح!');
    }
}
