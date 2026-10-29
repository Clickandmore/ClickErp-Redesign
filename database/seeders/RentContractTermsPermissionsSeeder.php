<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\PermKeys;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RentContractTermsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'name' => PermKeys::RENT_CONTRACT_SEND_OVERDUE_SMS,
                'guard_name' => 'admin',
                'group_name' => 'rent_contract',
                'section_name' => 'rent_contracts',
                'display_name' => 'إرسال رسالة للعميل بعد انتهاء عقد التأجير',
            ],
            [
                'name' => PermKeys::RENT_CONTRACT_CHANGE_MARKETER,
                'guard_name' => 'admin',
                'group_name' => 'rent_contract',
                'section_name' => 'rent_contracts',
                'display_name' => 'تغيير المسوق في عقد التأجير',
            ],
            [
                'name' => PermKeys::RENT_CONTRACT_VIEW,
                'guard_name' => 'admin',
                'group_name' => 'rent_contract',
                'section_name' => 'rent_contracts',
                'display_name' => 'عرض عقود الإيجار',
            ],
            [
                'name' => PermKeys::RENT_CONTRACT_CREATE,
                'guard_name' => 'admin',
                'group_name' => 'rent_contract',
                'section_name' => 'rent_contracts',
                'display_name' => 'إضافة عقد إيجار',
            ],
            [
                'name' => PermKeys::RENT_CONTRACT_EDIT,
                'guard_name' => 'admin',
                'group_name' => 'rent_contract',
                'section_name' => 'rent_contracts',
                'display_name' => 'تعديل عقود الإيجار',
            ],
            [
                'name' => PermKeys::RENT_CONTRACT_DELETE,
                'guard_name' => 'admin',
                'group_name' => 'rent_contract',
                'section_name' => 'rent_contracts',
                'display_name' => 'حذف عقود الإيجار',
            ],
            [
                'name' => PermKeys::RENT_CONTRACT_CONTROL,
                'guard_name' => 'admin',
                'group_name' => 'rent_contract',
                'section_name' => 'rent_contracts',
                'display_name' => 'التحكم في عقود الإيجار',
            ],
            [
                'name' => PermKeys::RENT_CONTRACT_CLOSURE_ADJUSTMENT_OVERRIDE,
                'guard_name' => 'admin',
                'group_name' => 'rent_contract',
                'section_name' => 'rent_contracts',
                'display_name' => 'تعديل تسوية إنهاء عقد الإيجار',
            ],
            [
                'name' => PermKeys::RENT_CONTRACT_CLOSURE_ADJUSTMENT_APPROVE,
                'guard_name' => 'admin',
                'group_name' => 'rent_contract',
                'section_name' => 'rent_contracts',
                'display_name' => 'اعتماد تسوية إنهاء عقد الإيجار',
            ],
            [
                'name' => PermKeys::RENT_CONTRACT_TERMS_VIEW,
                'guard_name' => 'admin',
                'group_name' => 'rent_contract',
                'section_name' => 'rent_contracts',
                'display_name' => 'عرض بنود عقد الإيجار',
            ],
            [
                'name' => PermKeys::RENT_CONTRACT_TERMS_CREATE,
                'guard_name' => 'admin',
                'group_name' => 'rent_contract',
                'section_name' => 'rent_contracts',
                'display_name' => 'إضافة بنود عقد الإيجار',
            ],
            [
                'name' => PermKeys::RENT_CONTRACT_TERMS_EDIT,
                'guard_name' => 'admin',
                'group_name' => 'rent_contract',
                'section_name' => 'rent_contracts',
                'display_name' => 'تعديل بنود عقد الإيجار',
            ],
            [
                'name' => PermKeys::RENT_CONTRACT_TERMS_DELETE,
                'guard_name' => 'admin',
                'group_name' => 'rent_contract',
                'section_name' => 'rent_contracts',
                'display_name' => 'حذف بنود عقد الإيجار',
            ],
            [
                'name' => PermKeys::CONTRACT_SETTINGS,
                'guard_name' => 'admin',
                'group_name' => 'contract_settings',
                'section_name' => 'settings',
                'display_name' => 'إعدادات العقد',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::withoutGlobalScopes()->updateOrCreate(
                [
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                ],
                [
                    'group_name' => $permission['group_name'],
                    'section_name' => $permission['section_name'],
                    'display_name' => $permission['display_name'],
                ]
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('تمت مزامنة صلاحيات عقود الإيجار وبنود العقد بنجاح.');
    }
}
