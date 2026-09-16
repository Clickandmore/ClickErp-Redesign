<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\NewPermKeys;

class NewPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء جميع الصلاحيات الجديدة
        $this->createDashboardPermissions();
        $this->createUsersPermissions();
        $this->createClientsPermissions();
        $this->createContractsPermissions();
        $this->createOrdersPermissions();
        $this->createMaidsPermissions();
        $this->createContentPermissions();
        $this->createReportsPermissions();
        $this->createSettingsPermissions();
        $this->createActivityLogPermissions();
    }

    /**
     * إنشاء صلاحيات لوحة التحكم
     */
    private function createDashboardPermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::DASHBOARD_VIEW,
                'group_name' => 'dashboard',
                'section_name' => 'general',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::DASHBOARD_ANALYTICS,
                'group_name' => 'dashboard',
                'section_name' => 'general',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::DASHBOARD_EXPORT,
                'group_name' => 'dashboard',
                'section_name' => 'general',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::DASHBOARD_MANAGE,
                'group_name' => 'dashboard',
                'section_name' => 'general',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::BRANCHES_VIEW_ALL,
                'group_name' => 'branches',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }

    /**
     * إنشاء صلاحيات إدارة المستخدمين
     */
    private function createUsersPermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::USERS_VIEW,
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::USERS_CREATE,
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::USERS_EDIT,
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::USERS_DELETE,
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::USERS_EXPORT,
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::USERS_IMPORT,
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::USERS_MANAGE,
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::USERS_ACTIVATE,
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::USERS_DEACTIVATE,
                'group_name' => 'users',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }

    /**
     * إنشاء صلاحيات إدارة العملاء
     */
    private function createClientsPermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::CLIENTS_VIEW,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_CREATE,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_EDIT,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_DELETE,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_EXPORT,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_IMPORT,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_MANAGE,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_ACTIVATE,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CLIENTS_DEACTIVATE,
                'group_name' => 'clients',
                'section_name' => 'users_management',
                'guard_name' => 'admin',
            ],
            // صلاحيات إضافية للعملاء
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
    }

    /**
     * إنشاء صلاحيات إدارة العقود
     */
    private function createContractsPermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::CONTRACTS_VIEW,
                'group_name' => 'contracts',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CONTRACTS_CREATE,
                'group_name' => 'contracts',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CONTRACTS_EDIT,
                'group_name' => 'contracts',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CONTRACTS_DELETE,
                'group_name' => 'contracts',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CONTRACTS_APPROVE,
                'group_name' => 'contracts',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CONTRACTS_REJECT,
                'group_name' => 'contracts',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CONTRACTS_ASSIGN,
                'group_name' => 'contracts',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CONTRACTS_EXPORT,
                'group_name' => 'contracts',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CONTRACTS_MANAGE,
                'group_name' => 'contracts',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }

    /**
     * إنشاء صلاحيات إدارة الطلبات
     */
    private function createOrdersPermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::ORDERS_VIEW,
                'group_name' => 'orders',
                'section_name' => 'order_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ORDERS_CREATE,
                'group_name' => 'orders',
                'section_name' => 'order_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ORDERS_EDIT,
                'group_name' => 'orders',
                'section_name' => 'order_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ORDERS_DELETE,
                'group_name' => 'orders',
                'section_name' => 'order_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ORDERS_APPROVE,
                'group_name' => 'orders',
                'section_name' => 'order_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ORDERS_REJECT,
                'group_name' => 'orders',
                'section_name' => 'order_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ORDERS_ASSIGN,
                'group_name' => 'orders',
                'section_name' => 'order_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ORDERS_EXPORT,
                'group_name' => 'orders',
                'section_name' => 'order_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ORDERS_MANAGE,
                'group_name' => 'orders',
                'section_name' => 'order_management',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }

    /**
     * إنشاء صلاحيات إدارة الخادمات
     */
    private function createMaidsPermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::MAIDS_VIEW,
                'group_name' => 'maids',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::MAIDS_CREATE,
                'group_name' => 'maids',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::MAIDS_EDIT,
                'group_name' => 'maids',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::MAIDS_DELETE,
                'group_name' => 'maids',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::MAIDS_APPROVE,
                'group_name' => 'maids',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::MAIDS_REJECT,
                'group_name' => 'maids',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::MAIDS_ASSIGN,
                'group_name' => 'maids',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::MAIDS_EXPORT,
                'group_name' => 'maids',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::MAIDS_IMPORT,
                'group_name' => 'maids',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::MAIDS_MANAGE,
                'group_name' => 'maids',
                'section_name' => 'contract_management',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }

    /**
     * إنشاء صلاحيات إدارة المحتوى
     */
    private function createContentPermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::CONTENT_VIEW,
                'group_name' => 'content',
                'section_name' => 'content_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CONTENT_CREATE,
                'group_name' => 'content',
                'section_name' => 'content_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CONTENT_EDIT,
                'group_name' => 'content',
                'section_name' => 'content_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CONTENT_DELETE,
                'group_name' => 'content',
                'section_name' => 'content_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CONTENT_PUBLISH,
                'group_name' => 'content',
                'section_name' => 'content_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CONTENT_UNPUBLISH,
                'group_name' => 'content',
                'section_name' => 'content_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CONTENT_MANAGE,
                'group_name' => 'content',
                'section_name' => 'content_management',
                'guard_name' => 'admin',
            ],
            ...collect([
                NewPermKeys::WHY_CHOOSE_US_VIEW,
                NewPermKeys::WHY_CHOOSE_US_CREATE,
                NewPermKeys::WHY_CHOOSE_US_EDIT,
                NewPermKeys::WHY_CHOOSE_US_DELETE,
                NewPermKeys::WHY_CHOOSE_US_MANAGE,
                NewPermKeys::WHY_US_VIEW,
                NewPermKeys::WHY_US_CREATE,
                NewPermKeys::WHY_US_EDIT,
                NewPermKeys::WHY_US_DELETE,
                NewPermKeys::WHY_US_MANAGE,
                NewPermKeys::FIND_US_VIEW,
                NewPermKeys::FIND_US_CREATE,
                NewPermKeys::FIND_US_EDIT,
                NewPermKeys::FIND_US_DELETE,
                NewPermKeys::FIND_US_MANAGE,
                NewPermKeys::RECRUITMENT_REQUIREMENTS_VIEW,
                NewPermKeys::RECRUITMENT_REQUIREMENTS_CREATE,
                NewPermKeys::RECRUITMENT_REQUIREMENTS_EDIT,
                NewPermKeys::RECRUITMENT_REQUIREMENTS_DELETE,
                NewPermKeys::RECRUITMENT_REQUIREMENTS_MANAGE,
            ])->map(fn (string $name) => [
                'name' => $name,
                'group_name' => 'content',
                'section_name' => 'content_management',
                'guard_name' => 'admin',
            ])->all(),
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }

    /**
     * إنشاء صلاحيات إدارة المنظمة
     */
    private function createOrganizationPermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::ORGANIZATION_VIEW,
                'group_name' => 'organization',
                'section_name' => 'organization_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ORGANIZATION_MANAGE,
                'group_name' => 'organization',
                'section_name' => 'organization_management',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }

    /**
     * إنشاء صلاحيات خدمة العملاء
     */
    private function createCustomerServicePermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::CUSTOMER_SERVICE_VIEW,
                'group_name' => 'customer_service',
                'section_name' => 'customer_service',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::CUSTOMER_SERVICE_MANAGE,
                'group_name' => 'customer_service',
                'section_name' => 'customer_service',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }

    /**
     * إنشاء صلاحيات التقارير
     */
    private function createReportsPermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::REPORTS_VIEW,
                'group_name' => 'reports',
                'section_name' => 'reporting',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::REPORTS_CREATE,
                'group_name' => 'reports',
                'section_name' => 'reporting',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::REPORTS_EDIT,
                'group_name' => 'reports',
                'section_name' => 'reporting',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::REPORTS_DELETE,
                'group_name' => 'reports',
                'section_name' => 'reporting',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::REPORTS_EXPORT,
                'group_name' => 'reports',
                'section_name' => 'reporting',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::REPORTS_GENERATE,
                'group_name' => 'reports',
                'section_name' => 'reporting',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::REPORTS_SCHEDULE,
                'group_name' => 'reports',
                'section_name' => 'reporting',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::REPORTS_MANAGE,
                'group_name' => 'reports',
                'section_name' => 'reporting',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }

    /**
     * إنشاء صلاحيات إدارة العمليات
     */
    private function createProcessPermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::PROCESS_VIEW,
                'group_name' => 'process',
                'section_name' => 'process_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::PROCESS_MANAGE,
                'group_name' => 'process',
                'section_name' => 'process_management',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }

    /**
     * إنشاء صلاحيات الاتصالات
     */
    private function createCommunicationsPermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::COMMUNICATIONS_VIEW,
                'group_name' => 'communications',
                'section_name' => 'communications',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::COMMUNICATIONS_MANAGE,
                'group_name' => 'communications',
                'section_name' => 'communications',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }

    /**
     * إنشاء صلاحيات إدارة النظام
     */
    private function createSystemPermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::SYSTEM_VIEW,
                'group_name' => 'system',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::SYSTEM_MANAGE,
                'group_name' => 'system',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::SETTINGS_VIEW,
                'group_name' => 'settings',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::SETTINGS_EDIT,
                'group_name' => 'settings',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::SETTINGS_MANAGE,
                'group_name' => 'settings',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ROLES_VIEW,
                'group_name' => 'roles',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ROLES_CREATE,
                'group_name' => 'roles',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ROLES_EDIT,
                'group_name' => 'roles',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ROLES_DELETE,
                'group_name' => 'roles',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ROLES_ASSIGN_PERMISSIONS,
                'group_name' => 'roles',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ROLES_MANAGE,
                'group_name' => 'roles',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::PERMISSIONS_VIEW,
                'group_name' => 'permissions',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::PERMISSIONS_CREATE,
                'group_name' => 'permissions',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::PERMISSIONS_EDIT,
                'group_name' => 'permissions',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::PERMISSIONS_DELETE,
                'group_name' => 'permissions',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::PERMISSIONS_ASSIGN,
                'group_name' => 'permissions',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::PERMISSIONS_MANAGE,
                'group_name' => 'permissions',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ACTIVITY_LOG_VIEW,
                'group_name' => 'activity_log',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ACTIVITY_LOG_EXPORT,
                'group_name' => 'activity_log',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ACTIVITY_LOG_MANAGE,
                'group_name' => 'activity_log',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::BACKUP_CREATE,
                'group_name' => 'backup',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::BACKUP_DOWNLOAD,
                'group_name' => 'backup',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::BACKUP_RESTORE,
                'group_name' => 'backup',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::BACKUP_MANAGE,
                'group_name' => 'backup',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }

    /**
     * إنشاء صلاحيات إدارة النظام
     */
    private function createSettingsPermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::SETTINGS_VIEW,
                'group_name' => 'settings',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::SETTINGS_EDIT,
                'group_name' => 'settings',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::SETTINGS_MANAGE,
                'group_name' => 'settings',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }

    /**
     * إنشاء صلاحيات إدارة النظام
     */
    private function createActivityLogPermissions(): void
    {
        $permissions = [
            [
                'name' => NewPermKeys::ACTIVITY_LOG_VIEW,
                'group_name' => 'activity_log',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ACTIVITY_LOG_EXPORT,
                'group_name' => 'activity_log',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
            [
                'name' => NewPermKeys::ACTIVITY_LOG_MANAGE,
                'group_name' => 'activity_log',
                'section_name' => 'system_management',
                'guard_name' => 'admin',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate($permission);
        }
    }
}
