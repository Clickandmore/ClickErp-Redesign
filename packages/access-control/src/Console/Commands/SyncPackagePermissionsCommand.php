<?php

namespace Nami\AccessControl\Console\Commands;

use Illuminate\Console\Command;
use Nami\AccessControl\Services\PermissionRegistryService;

class SyncPackagePermissionsCommand extends Command
{
    protected $signature = 'access-control:sync-permissions
        {--role=* : Roles that should receive the synced permissions}
        {--registry-only : Sync only the machine-managed registry file}
        {--include-main : Include legacy main resources from laratrust_seeder.php}
        {--no-assign-roles : Do not assign synced permissions to any roles}';

    protected $description = 'Sync access-control package permissions into the ClickERP permissions table.';

    public function handle(PermissionRegistryService $service): int
    {
        $summary = $service->sync([
            'roles' => $service->resolveRoleNames($this->option('role')),
            'assign_roles' => !$this->option('no-assign-roles'),
            'include_legacy' => !$this->option('registry-only'),
            'include_legacy_main' => $this->option('include-main'),
        ]);

        $this->info('Permissions synced successfully.');
        $this->line('Total definitions: ' . $summary['total']);
        $this->line('Created: ' . $summary['created'] . ' | Updated: ' . $summary['updated']);

        if (!$this->option('no-assign-roles')) {
            $this->line('Assigned entries: ' . $summary['assigned_permissions']);

            if ($summary['roles_found'] !== []) {
                $this->line('Roles updated: ' . implode(', ', $summary['roles_found']));
            }

            if ($summary['roles_missing'] !== []) {
                $this->warn('Missing roles: ' . implode(', ', $summary['roles_missing']));
            }
        }

        return self::SUCCESS;
    }
}
