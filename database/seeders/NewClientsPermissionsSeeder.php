<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\NewPermKeys;

class NewClientsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createNewClientsPermissions();
    }

    /**
     * إنشاء الصلاحيات الجديدة للعملاء
     */
    private function createNewClientsPermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::CLIENTS_APPROVE,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_REJECT,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_BLOCK,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_UNBLOCK,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_VERIFY,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_ASSIGN,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_TRANSFER,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_BACKUP,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_RESTORE,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_ANALYTICS,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_REPORTS,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_NOTIFICATIONS,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_SETTINGS,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }

        $this->command->info('تم إنشاء الصلاحيات الجديدة للعملاء بنجاح!');
    }
}