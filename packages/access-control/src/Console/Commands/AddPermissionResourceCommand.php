<?php

namespace Nami\AccessControl\Console\Commands;

use Illuminate\Console\Command;
use Nami\AccessControl\Services\PermissionRegistryService;

class AddPermissionResourceCommand extends Command
{
    protected $signature = 'access-control:add-resource
        {resource : Resource key, for example finance.reports or users}
        {--group= : Logical package group such as finance/hr/crm/main}
        {--section= : Optional section override used by the permissions screen}
        {--actions=view,create,edit,delete : Comma separated actions}
        {--role=* : Roles that should receive the generated permissions after sync}
        {--no-sync : Only write the resource into the package registry}
        {--no-assign-roles : Do not assign generated permissions to any roles}';

    protected $description = 'Add a CRUD-style resource to the access-control package registry and sync its permissions.';

    public function handle(PermissionRegistryService $service): int
    {
        $resource = trim((string) $this->argument('resource'));
        $group = trim((string) ($this->option('group') ?: ''));
        $section = $this->option('section') ?: null;
        $actions = explode(',', (string) $this->option('actions'));

        if ($resource === '') {
            $this->error('The resource key is required.');

            return self::FAILURE;
        }

        $service->addResourceToRegistry($resource, $group, $actions, $section);
        $this->info('The resource was written to packages/access-control/config/permission_registry.php');

        if ($this->option('no-sync')) {
            return self::SUCCESS;
        }

        $definitions = $service->buildDefinitionsForResource($resource, $group, $actions, $section);
        $summary = $service->syncPermissionDefinitions(
            $definitions,
            $service->resolveRoleNames($this->option('role')),
            !$this->option('no-assign-roles')
        );

        $this->line('Generated permissions: ' . $summary['total']);
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
