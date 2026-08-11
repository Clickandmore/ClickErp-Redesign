<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(LaratrustSeeder::class);
        $this->call(UsersTableSeeder::class);
        $this->call(JopTypesSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(AdminRoleSeeder::class);
        $this->call(PermissionSeeder::class);
        $this->call(AdminSidebarPermissionsSeeder::class);
        $this->call(RecruitmentContractsPermissionsSeeder::class);
        $this->call(AssignRecruitmentContractsPermissionsSeeder::class);
        $this->call(RentContractTermsPermissionsSeeder::class);
        $this->call(MoyasarSettingsPermissionsSeeder::class);
        $this->call(MaidLanguagesSeeder::class);

        // إضافة بيانات الدول والمدن
        $this->call(CountriesSeeder::class);
        $this->call(CitiesSeeder::class);
        $this->call(BranchSeeder::class);
        $this->call(StepsSeeder::class);
        $this->call(RecruitmentRequirementsSeeder::class);
        $this->call(WhyChooseUsSeeder::class);
    }
}
