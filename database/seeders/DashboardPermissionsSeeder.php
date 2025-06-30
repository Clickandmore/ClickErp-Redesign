<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use App\PermKeys;

class DashboardPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // صلاحيات الصفحة الرئيسية
        $dashboardPermissions = [
            PermKeys::DASHBOARD_CLIENTS_COUNT,
            PermKeys::DASHBOARD_CONTACTS_COUNT,
            PermKeys::DASHBOARD_CURRENT_ORDERS_COUNT,
            PermKeys::DASHBOARD_CV_COUNT,
            PermKeys::DASHBOARD_EMPLOYEES_COUNT,
            PermKeys::DASHBOARD_COMPLETED_CV_COUNT,
            PermKeys::DASHBOARD_ORDERS_STATISTICS,
            PermKeys::DASHBOARD_TOP_CUSTOMER_SERVICES,
            PermKeys::DASHBOARD_ORDERS_TYPES,
        ];

        foreach ($dashboardPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'admin'
            ]);
        }

        $this->command->info('Dashboard permissions seeded successfully!');
    }
}
