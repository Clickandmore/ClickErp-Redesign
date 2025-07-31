<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\auth\Admin;
use Spatie\Permission\Models\Permission;
use App\Helpers\PermKeys;

class AssignRentContractPermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:assign-rent-contracts {admin_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign all rent contract permissions to admin user';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            // Get admin ID from argument or use first admin
            $adminId = $this->argument('admin_id');

            if ($adminId) {
                $admin = Admin::find($adminId);
                if (!$admin) {
                    $this->error("Admin with ID {$adminId} not found!");
                    return 1;
                }
            } else {
                $admin = Admin::first();
                if (!$admin) {
                    $this->error('No admin users found!');
                    return 1;
                }
            }

            $this->info("Assigning rent contract permissions to: {$admin->name}");

            // Define rent contract permissions
            $rentPermissions = [
                PermKeys::RENT_CONTRACT_VIEW,
                PermKeys::RENT_CONTRACT_CREATE,
                PermKeys::RENT_CONTRACT_EDIT,
                PermKeys::RENT_CONTRACT_DELETE,
                PermKeys::RENT_CONTRACT_CONTROL,
            ];

            $assignedCount = 0;
            $createdCount = 0;

            foreach ($rentPermissions as $permissionName) {
                // Create permission if it doesn't exist
                $permission = Permission::firstOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'admin'],
                    ['name' => $permissionName, 'guard_name' => 'admin']
                );

                if ($permission->wasRecentlyCreated) {
                    $createdCount++;
                    $this->line("Created permission: {$permissionName}");
                }

                // Assign permission to admin if not already assigned
                if (!$admin->hasPermissionTo($permissionName, 'admin')) {
                    $admin->givePermissionTo($permission);
                    $assignedCount++;
                    $this->line("Assigned permission: {$permissionName}");
                } else {
                    $this->line("Permission already assigned: {$permissionName}");
                }
            }

            $this->info("✅ Process completed!");
            $this->info("Created {$createdCount} new permissions");
            $this->info("Assigned {$assignedCount} permissions to {$admin->name}");

            // Show all rent contract permissions for this admin
            $this->info("\n📋 Current rent contract permissions for {$admin->name}:");
            $adminRentPermissions = $admin->getAllPermissions()
                ->filter(function($perm) {
                    return str_contains($perm->name, 'rent_contract');
                });

            foreach ($adminRentPermissions as $perm) {
                $this->line("  ✓ {$perm->name}");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
