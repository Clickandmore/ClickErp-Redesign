<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\auth\Admin;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $admin = Admin::create([
            'name' => 'مشــرف',
            'phone' => '01009428727',
            'email' => 'admin@admin.com',
            'password' => bcrypt("123456"),
            'admin_type' => "super_admin",
        ]);

     //    $admin->attachRole('super_admin');

    }
}
