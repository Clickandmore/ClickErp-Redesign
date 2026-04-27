<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\auth\Admin;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Nami\AccessControl\Services\PermissionRegistryService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PrepareServerPermissionsCommand extends Command
{
    protected $signature = 'permissions:prepare-server
        {--role-id=1 : Preferred role id that should receive all permissions}
        {--role-name=Administrator : Fallback role name that should receive all permissions}
        {--without-main : Skip legacy main resources from laratrust_seeder.php}
        {--attach-first-admin : Also attach the resolved role to the first admin account}';

    protected $description = 'Sync all permissions shown in the permissions screen and assign them to the main admin role for server deployment.';

    public function handle(PermissionRegistryService $service): int
    {
        $role = $this->resolveTargetRole();

        $this->info('Syncing permissions from the package registry...');

        $summary = $service->sync([
            'roles' => [],
            'assign_roles' => false,
            'include_legacy' => true,
            'include_legacy_main' => !$this->option('without-main'),
        ]);

        $this->line('Total definitions: ' . $summary['total']);
        $this->line('Created: ' . $summary['created'] . ' | Updated: ' . $summary['updated']);

        $matrixSummary = $this->syncRouteMatrixPermissions($service);

        $this->line('Route matrix definitions: ' . $matrixSummary['total']);
        $this->line('Route matrix created: ' . $matrixSummary['created'] . ' | Updated: ' . $matrixSummary['updated']);

        $allPermissions = Permission::query()
            ->where('guard_name', $role->guard_name)
            ->orderBy('name')
            ->get();

        if ($allPermissions->isEmpty()) {
            $this->error('No permissions were found after sync. Check the registry and database connection.');

            return self::FAILURE;
        }

        $role->syncPermissions($allPermissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info(sprintf(
            'Assigned %d permissions to role %s (ID: %d).',
            $allPermissions->count(),
            $role->name,
            $role->id
        ));

        if ($this->option('attach-first-admin')) {
            $this->attachRoleToFirstAdmin($role);
        }

        $this->printFinalVerification($role);

        $this->newLine();
        $this->info('Server permissions are ready.');

        return self::SUCCESS;
    }

    private function syncRouteMatrixPermissions(PermissionRegistryService $service): array
    {
        $definitions = collect(AdminRoutePermissionMatrix::allPermissions())
            ->filter(fn ($name) => is_string($name) && trim($name) !== '')
            ->unique()
            ->values()
            ->map(fn (string $name) => $service->makeExplicitPermissionDefinition([
                'name' => $name,
                'is_active' => true,
            ]))
            ->all();

        return $service->syncPermissionDefinitions($definitions, [], false);
    }

    private function printFinalVerification(Role $role): void
    {
        $screenPermissionNames = Permission::query()
            ->where('guard_name', $role->guard_name)
            ->orderBy('name')
            ->pluck('name');

        $assignedPermissionNames = $role->fresh()
            ->permissions()
            ->pluck('name');

        $missingPermissionNames = $screenPermissionNames
            ->diff($assignedPermissionNames)
            ->values();

        $this->newLine();
        $this->line('Final verification:');
        $this->line('Screen total: ' . $screenPermissionNames->count());
        $this->line('Role selected: ' . $assignedPermissionNames->intersect($screenPermissionNames)->count());
        $this->line('Missing selected: ' . $missingPermissionNames->count());

        if ($missingPermissionNames->isNotEmpty()) {
            $this->warn('Missing permissions: ' . $missingPermissionNames->take(30)->implode(', '));
        }
    }

    private function resolveTargetRole(): Role
    {
        $roleId = (int) $this->option('role-id');
        $roleName = trim((string) $this->option('role-name')) ?: 'Administrator';

        $role = Role::query()
            ->where('guard_name', 'admin')
            ->where(function ($query) use ($roleId, $roleName) {
                if ($roleId > 0) {
                    $query->where('id', $roleId);
                }

                if ($roleName !== '') {
                    $method = $roleId > 0 ? 'orWhere' : 'where';
                    $query->{$method}('name', $roleName);
                }
            })
            ->first();

        if ($role) {
            $this->line(sprintf('Target role: %s (ID: %d)', $role->name, $role->id));

            return $role;
        }

        $role = Role::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'admin',
        ]);

        $this->warn(sprintf('Target role was not found, created %s (ID: %d).', $role->name, $role->id));

        return $role;
    }

    private function attachRoleToFirstAdmin(Role $role): void
    {
        $admin = Admin::query()->orderBy('id')->first();

        if (!$admin) {
            $this->warn('No admins were found, so the role was not attached to a user.');

            return;
        }

        $admin->syncRoles([$role->name]);

        if (Schema::hasColumn($admin->getTable(), 'role_id') && (int) $admin->role_id !== $role->id) {
            $admin->role_id = $role->id;
            $admin->save();
        }

        $this->info(sprintf(
            'Attached role %s to admin %s (ID: %d).',
            $role->name,
            $admin->name ?? $admin->email ?? 'unknown',
            $admin->id
        ));
    }
}
