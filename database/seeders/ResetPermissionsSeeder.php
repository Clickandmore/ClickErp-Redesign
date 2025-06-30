<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ResetPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define permissions for the three sections
        $permissions = [
            // General permissions
            'general.view_dashboard' => [
                'group' => 'general',
                'section' => 'general',
                'display_name' => 'View Dashboard',
                'description' => 'Can view the main dashboard'
            ],
            'general.view_settings' => [
                'group' => 'general',
                'section' => 'general',
                'display_name' => 'View Settings',
                'description' => 'Can view system settings'
            ],
            'general.edit_settings' => [
                'group' => 'general',
                'section' => 'general',
                'display_name' => 'Edit Settings',
                'description' => 'Can edit system settings'
            ],

            // Users Management permissions
            'users_management.view_users' => [
                'group' => 'users_management',
                'section' => 'users_management',
                'display_name' => 'View Users',
                'description' => 'Can view users list'
            ],
            'users_management.add_user' => [
                'group' => 'users_management',
                'section' => 'users_management',
                'display_name' => 'Add User',
                'description' => 'Can add new users'
            ],
            'users_management.edit_user' => [
                'group' => 'users_management',
                'section' => 'users_management',
                'display_name' => 'Edit User',
                'description' => 'Can edit existing users'
            ],
            'users_management.delete_user' => [
                'group' => 'users_management',
                'section' => 'users_management',
                'display_name' => 'Delete User',
                'description' => 'Can delete users'
            ],

            // CV Management permissions
            'cv_management.view_cvs' => [
                'group' => 'cv_management',
                'section' => 'cv_management',
                'display_name' => 'View CVs',
                'description' => 'Can view CVs list'
            ],
            'cv_management.add_cv' => [
                'group' => 'cv_management',
                'section' => 'cv_management',
                'display_name' => 'Add CV',
                'description' => 'Can add new CVs'
            ],
            'cv_management.edit_cv' => [
                'group' => 'cv_management',
                'section' => 'cv_management',
                'display_name' => 'Edit CV',
                'description' => 'Can edit existing CVs'
            ],
            'cv_management.delete_cv' => [
                'group' => 'cv_management',
                'section' => 'cv_management',
                'display_name' => 'Delete CV',
                'description' => 'Can delete CVs'
            ],
            'cv_management.pin_cv' => [
                'group' => 'cv_management',
                'section' => 'cv_management',
                'display_name' => 'Pin CV',
                'description' => 'Can pin CVs to top'
            ],
        ];

        // Create permissions
        foreach ($permissions as $permissionName => $permissionData) {
            Permission::create([
                'name' => $permissionName,
                'guard_name' => 'admin',
                'group' => $permissionData['group'],
                'section' => $permissionData['section'],
                'display_name' => $permissionData['display_name'],
                'description' => $permissionData['description'],
            ]);
        }

        // Create default roles if they don't exist
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'admin']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'admin']);

        // Assign all permissions to super admin
        $superAdmin->givePermissionTo(Permission::all());

        // Assign basic permissions to admin
        $admin->givePermissionTo([
            'general.view_dashboard',
            'general.view_settings',
            'users_management.view_users',
            'cv_management.view_cvs',
        ]);

        // Assign minimal permissions to user
        $user->givePermissionTo([
            'general.view_dashboard',
        ]);

        $this->command->info('Permissions reset successfully!');
        $this->command->info('Created ' . count($permissions) . ' permissions');
        $this->command->info('Created 3 roles: super_admin, admin, user');
    }
}
