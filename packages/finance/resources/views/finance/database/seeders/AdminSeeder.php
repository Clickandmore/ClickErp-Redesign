<?php

namespace Database\Seeders;

use App\Enums\AdminTypeisEnum;
use App\Models\Nami\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Admin::create([
            'name' => 'admin',
            'phone' => '500000000',
            'email' => 'admin@admin.com',
            'password' => bcrypt("123456"),
            'admin_type' => AdminTypeisEnum::GlobalManger->value,
        ]);

        $admin->modules()->create([
            'admin_id' => $admin->id,
            'module' => \App\Enums\UserModules::All->value
        ]);
        $dev = Admin::create([
            'name' => 'admin',
            'phone' => '500000000',
            'email' => 'dev@click.com',
            'password' => bcrypt("123456"),
            'admin_type' => AdminTypeisEnum::Developer->value,
        ]);

        $dev->modules()->create([
            'admin_id' => $admin->id,
            'module' => \App\Enums\UserModules::All->value
        ]);

        $hr = Admin::create([
            'name' => 'hr',
            'phone' => '500000000',
            'email' => 'hr@hr.com',
            'password' => bcrypt("hr@hr.com"),
            'admin_type' => AdminTypeisEnum::GlobalManger->value,
        ]);
        $hr->modules()->create([
            'admin_id' => $hr->id,
            'module' => \App\Enums\UserModules::HR->value
        ]);
        $finance = Admin::create([
            'name' => 'finance',
            'phone' => '500000000',
            'email' => 'finance@finance.com',
            'password' => bcrypt("finance@finance.com"),
            'admin_type' => AdminTypeisEnum::GlobalManger->value,
        ]);
        $finance->modules()->create([
            'admin_id' => $finance->id,
            'module' => \App\Enums\UserModules::Finance->value
        ]);
        $crm = Admin::create([
            'name' => 'crm',
            'phone' => '500000000',
            'email' => 'crm@crm.com',
            'password' => bcrypt("crm@crm.com"),
            'admin_type' => AdminTypeisEnum::GlobalManger->value,
        ]);
        $crm->modules()->create([
            'admin_id' => $crm->id,
            'module' => \App\Enums\UserModules::Crm->value
        ]);
        $zatca = Admin::create([
            'name' => 'zatca manager',
            'phone' => '500000000',
            'email' => 'zatca@zatca.com',
            'password' => bcrypt("zatca@zatca.com"),
            'admin_type' => AdminTypeisEnum::GlobalManger->value,
        ]);
        $zatca->modules()->create([
            'admin_id' => $crm->id,
            'module' => \App\Enums\UserModules::Zatca->value
        ]);

    }
}
