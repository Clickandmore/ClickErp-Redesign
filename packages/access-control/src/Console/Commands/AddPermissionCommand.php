<?php

namespace Nami\AccessControl\Console\Commands;

use Illuminate\Console\Command;
use Nami\AccessControl\Services\PermissionRegistryService;

class AddPermissionCommand extends Command
{
    protected $signature = 'access-control:add-permission
        {name : The permission name to create}
        {--group= : Logical permission group}
        {--section= : Section name used by the permissions screen}
        {--display-name= : Optional display name}
        {--description= : Optional description}
        {--role=* : Roles that should receive the permission after sync}
        {--inactive : Create the permission as inactive}
        {--no-sync : Only write to the package registry}
        {--no-assign-roles : Do not assign the permission to any roles}
        {--no-write-config : Do not persist this permission in the package registry}';

    protected $description = 'Add a single permission to the access-control package registry and sync it into the database.';

    public function handle(PermissionRegistryService $service): int
    {
        $permission = [
            'name' => trim((string) $this->argument('name')),
            'group' => $this->option('group') ?: null,
            'section' => $this->option('section') ?: null,
            'display_name' => $this->option('display-name') ?: null,
            'description' => $this->option('description') ?: null,
            'is_active' => !$this->option('inactive'),
        ];

        if ($permission['name'] === '') {
            $this->error('The permission name is required.');

            return self::FAILURE;
        }

        if (!$this->option('no-write-config')) {
            $service->addPermissionToRegistry($permission);
            $this->info('The permission was written to packages/access-control/config/permission_registry.php');
        }

        if ($this->option('no-sync')) {
            return self::SUCCESS;
        }

        $summary = $service->syncPermissionDefinitions(
            [$service->makeExplicitPermissionDefinition($permission)],
            $service->resolveRoleNames($this->option('role')),
            !$this->option('no-assign-roles')
        );

        $this->line('Synced permissions: ' . $summary['total']);
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
