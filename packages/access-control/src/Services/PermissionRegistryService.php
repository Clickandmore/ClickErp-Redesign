<?php

namespace Nami\AccessControl\Services;

use App\Models\Permission;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionRegistryService
{
    private ?array $permissionColumns = null;

    public function addPermissionToRegistry(array $permission): void
    {
        $config = $this->loadRegistryConfig();
        $permissions = collect($config['permissions'] ?? []);
        $name = trim((string) ($permission['name'] ?? ''));

        if ($name === '') {
            return;
        }

        $permissions = $permissions
            ->reject(fn (array $item) => ($item['name'] ?? null) === $name)
            ->push(array_filter([
                'name' => $name,
                'group' => $permission['group'] ?? null,
                'section' => $permission['section'] ?? null,
                'display_name' => $permission['display_name'] ?? null,
                'description' => $permission['description'] ?? null,
                'is_active' => $permission['is_active'] ?? true,
            ], fn ($value) => $value !== null))
            ->values()
            ->all();

        $config['permissions'] = $permissions;
        $this->saveRegistryConfig($config);
    }

    public function addResourceToRegistry(string $resource, string $group, array $actions, ?string $section = null): void
    {
        $config = $this->loadRegistryConfig();
        $group = $this->normalizeGroup($group ?: $this->guessGroupFromResource($resource));
        $actions = $this->normalizeActions($actions);

        if (!isset($config['resources'][$group]) || !is_array($config['resources'][$group])) {
            $config['resources'][$group] = [];
        }

        $resourceConfig = [
            'actions' => $actions,
        ];

        if ($section !== null && $section !== '') {
            $resourceConfig['section'] = $section;
            $config['group_sections'][$group] = $section;
        }

        $config['resources'][$group][$resource] = $resourceConfig;
        $this->saveRegistryConfig($config);
    }

    public function makeExplicitPermissionDefinition(array $permission): array
    {
        $name = trim((string) ($permission['name'] ?? ''));
        $group = $permission['group'] ?? null;
        $section = $permission['section'] ?? null;
        ['group' => $resolvedGroup, 'section' => $resolvedSection] = $this->inferPlacement($name, $group, $section);
        $displayName = $permission['display_name'] ?? $this->buildDisplayName($name, $resolvedGroup);
        $description = $permission['description'] ?? $this->buildDescription($displayName);

        return array_filter([
            'name' => $name,
            'guard_name' => $this->defaultGuardName(),
            'group_name' => $resolvedGroup,
            'section_name' => $resolvedSection,
            'display_name' => $displayName,
            'description' => $description,
            'is_active' => $permission['is_active'] ?? true,
        ], fn ($value) => $value !== null);
    }

    public function buildDefinitionsForResource(string $resource, string $group, array $actions, ?string $section = null): array
    {
        $group = $this->normalizeGroup($group ?: $this->guessGroupFromResource($resource));
        $section = $section ?: $this->sectionForGroup($group);
        $definitions = [];

        foreach ($this->normalizeActions($actions) as $action) {
            $permissionName = $this->buildPermissionName($resource, $action, $group);
            ['group' => $resolvedGroup, 'section' => $resolvedSection] = $this->inferPlacement(
                $permissionName,
                $this->preferredGroupName($group, $resource),
                $section
            );

            $definitions[$permissionName] = [
                'name' => $permissionName,
                'guard_name' => $this->defaultGuardName(),
                'group_name' => $resolvedGroup,
                'section_name' => $resolvedSection,
                'display_name' => $this->buildDisplayName($permissionName, $resolvedGroup),
                'description' => $this->buildDescription($this->buildDisplayName($permissionName, $resolvedGroup)),
                'is_active' => true,
            ];
        }

        return array_values($definitions);
    }

    public function sync(array $options = []): array
    {
        $includeLegacy = $options['include_legacy']
            ?? (bool) config('access_control_permission_registry.defaults.include_legacy', true);
        $includeLegacyMain = $options['include_legacy_main']
            ?? (bool) config('access_control_permission_registry.defaults.include_legacy_main', false);
        $assignRoles = $options['assign_roles'] ?? true;
        $roles = $this->resolveRoleNames($options['roles'] ?? []);

        $definitions = $this->buildDefinitionsFromRegistry();

        if ($includeLegacy) {
            $definitions = $this->mergeDefinitions(
                $definitions,
                $this->buildDefinitionsFromLegacy($includeLegacyMain)
            );
        }

        return $this->syncPermissionDefinitions($definitions, $roles, $assignRoles);
    }

    public function syncPermissionDefinitions(array $definitions, array $roles = [], bool $assignRoles = true): array
    {
        $definitions = $this->mergeDefinitions([], $definitions);
        $this->ensurePermissionsTableExists();

        $summary = [
            'total' => count($definitions),
            'created' => 0,
            'updated' => 0,
            'assigned_permissions' => 0,
            'roles_found' => [],
            'roles_missing' => [],
        ];

        if ($definitions === []) {
            return $summary;
        }

        $permissionIds = [];
        $guardName = $this->defaultGuardName();
        $columns = array_flip($this->permissionTableColumns());

        foreach ($definitions as $definition) {
            $attributes = [
                'name' => $definition['name'],
                'guard_name' => $definition['guard_name'] ?? $guardName,
            ];

            $values = array_intersect_key($definition, $columns);
            $existing = Permission::withoutGlobalScopes()
                ->where($attributes)
                ->first();

            if ($existing) {
                $existing->fill($values);
                $existing->save();
                $permission = $existing;
                $summary['updated']++;
            } else {
                $permission = Permission::withoutGlobalScopes()->create($values);
                $summary['created']++;
            }

            $permissionIds[] = $permission->id;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($assignRoles && $roles !== []) {
            $roleModels = Role::query()
                ->where('guard_name', $guardName)
                ->whereIn('name', $roles)
                ->get()
                ->keyBy('name');

            $summary['roles_found'] = $roleModels->keys()->all();
            $summary['roles_missing'] = array_values(array_diff($roles, $summary['roles_found']));

            foreach ($roleModels as $role) {
                $role->permissions()->syncWithoutDetaching($permissionIds);
                $summary['assigned_permissions'] += count($permissionIds);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return $summary;
    }

    public function resolveRoleNames(array $roles = []): array
    {
        $roles = collect($roles)
            ->flatten()
            ->map(function ($role) {
                if (is_string($role) && str_contains($role, ',')) {
                    return explode(',', $role);
                }

                return [$role];
            })
            ->flatten()
            ->map(fn ($role) => trim((string) $role))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($roles !== []) {
            return $roles;
        }

        return collect(config('access_control_permission_registry.defaults.assign_roles', ['Administrator']))
            ->map(fn ($role) => trim((string) $role))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function buildDefinitionsFromRegistry(): array
    {
        $config = $this->loadRegistryConfig();
        $definitions = [];

        foreach ($config['permissions'] ?? [] as $permission) {
            if (!is_array($permission) || empty($permission['name'])) {
                continue;
            }

            $definitions[] = $this->makeExplicitPermissionDefinition($permission);
        }

        foreach (($config['resources'] ?? []) as $group => $resources) {
            if (!is_array($resources)) {
                continue;
            }

            foreach ($resources as $resource => $resourceConfig) {
                if (is_array($resourceConfig) && array_is_list($resourceConfig)) {
                    $actions = $resourceConfig;
                    $section = null;
                } else {
                    $actions = $resourceConfig['actions'] ?? [];
                    $section = $resourceConfig['section'] ?? null;
                }

                $definitions = array_merge(
                    $definitions,
                    $this->buildDefinitionsForResource($resource, (string) $group, (array) $actions, $section)
                );
            }
        }

        return $definitions;
    }

    private function buildDefinitionsFromLegacy(bool $includeMain = false): array
    {
        $config = $this->loadLegacyConfig();
        $definitions = [];
        $resources = [];

        foreach (($config['roles_structure'] ?? []) as $roleGroups) {
            if (!is_array($roleGroups)) {
                continue;
            }

            foreach ($roleGroups as $group => $items) {
                if (!is_array($items)) {
                    continue;
                }

                if (!$includeMain && $group === 'main') {
                    continue;
                }

                foreach ($items as $resource => $codes) {
                    $resourceKey = $group . '::' . $resource;
                    $currentActions = $resources[$resourceKey]['actions'] ?? [];
                    $resources[$resourceKey] = [
                        'group' => $group,
                        'resource' => $resource,
                        'actions' => array_values(array_unique(array_merge(
                            $currentActions,
                            $this->legacyCodesToActions((string) $codes)
                        ))),
                    ];
                }
            }
        }

        foreach ($resources as $resource) {
            $definitions = array_merge(
                $definitions,
                $this->buildDefinitionsForResource(
                    $resource['resource'],
                    $resource['group'],
                    $resource['actions'],
                    $this->sectionForGroup($resource['group'])
                )
            );
        }

        return $definitions;
    }

    private function mergeDefinitions(array $base, array $incoming): array
    {
        $merged = [];

        foreach (array_merge($base, $incoming) as $definition) {
            if (!is_array($definition) || empty($definition['name'])) {
                continue;
            }

            $name = $definition['name'];
            $merged[$name] = array_filter(
                array_merge($merged[$name] ?? [], $definition),
                fn ($value) => $value !== null
            );
        }

        return array_values($merged);
    }

    private function buildPermissionName(string $resource, string $action, string $group): string
    {
        $group = $this->normalizeGroup($group);
        $resourceName = $this->normalizeResourceKey($resource);
        $actionName = $this->normalizeAction($action);

        if ($group !== 'main' && !Str::startsWith($resourceName, $group . '_')) {
            $resourceName = $group . '_' . $resourceName;
        }

        return trim($resourceName . '_' . $actionName, '_');
    }

    private function inferPlacement(string $permissionName, ?string $group = null, ?string $section = null): array
    {
        $normalizedName = str_replace(['.', '-'], '_', $permissionName);

        $map = [
            'dashboard_' => ['section' => 'dashboard', 'group' => 'dashboard'],
            'clients_' => ['section' => 'clients', 'group' => 'clients'],
            'maids_rental_' => ['section' => 'cv_management', 'group' => 'maids_rental'],
            'maids_backout_' => ['section' => 'cv_management', 'group' => 'maids_backout'],
            'maids_deleted_' => ['section' => 'cv_management', 'group' => 'maids_deleted'],
            'maids_' => ['section' => 'cv_management', 'group' => 'maids'],
            'cv_' => ['section' => 'cv_management', 'group' => 'maids'],
            'orders_' => ['section' => 'orders', 'group' => 'orders'],
            'return_request_' => ['section' => 'orders', 'group' => 'return_request'],
            'professional_application_' => ['section' => 'orders', 'group' => 'professional_application'],
            'recruitment_requests_' => ['section' => 'orders', 'group' => 'recruitment_requests'],
            'renew_contracts_' => ['section' => 'orders', 'group' => 'renew_contracts'],
            'rec_contract_' => ['section' => 'recruitment_contracts', 'group' => 'rec_contracts'],
            'rec_contracts_' => ['section' => 'recruitment_contracts', 'group' => 'rec_contracts'],
            'cancel_contract_' => ['section' => 'recruitment_contracts', 'group' => 'cancel_contract'],
            'rent_contract_' => ['section' => 'rent_contracts', 'group' => 'rent_contract'],
            'rental_orders_' => ['section' => 'rent_contracts', 'group' => 'rental_orders'],
            'shelter_contract_' => ['section' => 'shelter_management', 'group' => 'shelter_contract'],
            'shelter_workers_' => ['section' => 'shelter_management', 'group' => 'shelter_workers'],
            'shelter_places_' => ['section' => 'shelter_management', 'group' => 'shelter_places'],
            'travel_' => ['section' => 'travel_management', 'group' => 'travel'],
            'rec_offices_' => ['section' => 'maids_settings', 'group' => 'rec_offices'],
            'recruitment_offices_' => ['section' => 'maids_settings', 'group' => 'rec_offices'],
            'complaint_type_' => ['section' => 'complaints', 'group' => 'complaint_type'],
            'complaint_' => ['section' => 'complaints', 'group' => 'complaint'],
            'complaints_' => ['section' => 'complaints', 'group' => 'complaint'],
            'sponsor_transfer_web_requests_' => ['section' => 'additional_services', 'group' => 'sponsor_transfer_web_requests'],
            'sponsor_transfer_' => ['section' => 'additional_services', 'group' => 'sponsor_transfer'],
            'settings_' => ['section' => 'settings', 'group' => 'settings'],
            'activity_' => ['section' => 'settings', 'group' => 'activity_log'],
            'job_titles_' => ['section' => 'maids_settings', 'group' => 'job_titles'],
            'social_status_' => ['section' => 'maids_settings', 'group' => 'social_status'],
            'packages_' => ['section' => 'maids_settings', 'group' => 'packages'],
            'nationalities_' => ['section' => 'maids_settings', 'group' => 'nationalities'],
            'religions_' => ['section' => 'maids_settings', 'group' => 'religions'],
            'relegions_' => ['section' => 'maids_settings', 'group' => 'religions'],
            'skills_' => ['section' => 'maids_settings', 'group' => 'skills'],
            'countries_' => ['section' => 'maids_settings', 'group' => 'countries'],
            'users_' => ['section' => 'users_management', 'group' => 'users'],
            'roles_' => ['section' => 'users_management', 'group' => 'roles'],
            'permissions_' => ['section' => 'users_management', 'group' => 'permissions'],
            'admins_' => ['section' => 'users_management', 'group' => 'admins'],
            'role_permissions_' => ['section' => 'users_management', 'group' => 'role_permissions'],
            'statistics_' => ['section' => 'content_management', 'group' => 'statistics'],
            'stat_' => ['section' => 'content_management', 'group' => 'statistics'],
            'notifications_' => ['section' => 'content_management', 'group' => 'notifications'],
            'sliders_' => ['section' => 'content_management', 'group' => 'sliders'],
            'faq_' => ['section' => 'content_management', 'group' => 'faq'],
            'about_us_' => ['section' => 'content_management', 'group' => 'about_us'],
            'contact_' => ['section' => 'orders', 'group' => 'contact'],
            'steps_' => ['section' => 'content_management', 'group' => 'steps'],
            'services_' => ['section' => 'content_management', 'group' => 'services'],
            'crm_' => ['section' => 'clients', 'group' => 'crm'],
            'hr_' => ['section' => 'modules', 'group' => 'hr'],
            'finance_' => ['section' => 'modules', 'group' => 'finance'],
            'employee_' => ['section' => 'modules', 'group' => 'employee'],
            'zatca_' => ['section' => 'modules', 'group' => 'zatca'],
        ];

        foreach ($map as $prefix => $placement) {
            if (Str::startsWith($normalizedName, $prefix)) {
                return $placement;
            }
        }

        $resolvedGroup = $this->normalizeGroup($group ?? $this->resourceGroupFromPermissionName($permissionName));
        $resolvedSection = $section ?: $this->sectionForGroup($resolvedGroup);

        return [
            'section' => $resolvedSection,
            'group' => $resolvedGroup,
        ];
    }

    private function resourceGroupFromPermissionName(string $permissionName): string
    {
        $normalizedName = str_replace('-', '_', $permissionName);
        $baseName = preg_replace('/_(view|create|edit|delete|manage|assign|export|import|approve|reject|toggle_status|toggle_pin|change_status|show)$/', '', $normalizedName);

        $resolved = (string) Str::of((string) $baseName)->beforeLast('_');

        return $resolved !== '' ? $resolved : (string) $baseName;
    }

    private function preferredGroupName(string $group, string $resource): string
    {
        if ($group !== 'main') {
            return $group;
        }

        return $this->normalizeResourceKey($resource);
    }

    private function sectionForGroup(string $group): string
    {
        $sections = config('access_control_permission_registry.group_sections', []);

        if (isset($sections[$group]) && is_string($sections[$group]) && $sections[$group] !== '') {
            return $sections[$group];
        }

        if ($group === 'crm') {
            return 'clients';
        }

        return $group === 'main' ? 'users_management' : 'modules';
    }

    private function guessGroupFromResource(string $resource): string
    {
        $normalized = $this->normalizeResourceKey($resource);

        foreach (['finance', 'hr', 'crm', 'employee', 'zatca'] as $group) {
            if (Str::startsWith($normalized, $group . '_')) {
                return $group;
            }
        }

        return 'main';
    }

    private function normalizeGroup(string $group): string
    {
        $group = trim((string) $group);

        return $group === '' ? 'main' : Str::snake(str_replace(['.', '-'], '_', $group));
    }

    private function normalizeResourceKey(string $resource): string
    {
        $resource = trim($resource);
        $resource = str_replace(['.', '-'], '_', $resource);

        return trim((string) Str::of($resource)->snake()->replaceMatches('/_+/', '_'), '_');
    }

    private function normalizeActions(array $actions): array
    {
        return collect($actions)
            ->flatten()
            ->map(function ($action) {
                if (is_string($action) && str_contains($action, ',')) {
                    return explode(',', $action);
                }

                return [$action];
            })
            ->flatten()
            ->map(fn ($action) => $this->normalizeAction((string) $action))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeAction(string $action): string
    {
        $action = trim(Str::snake(str_replace('-', '_', $action)));

        $aliases = [
            'c' => 'create',
            'create' => 'create',
            'add' => 'create',
            'store' => 'create',
            'r' => 'view',
            'read' => 'view',
            'view' => 'view',
            'show' => 'view',
            'u' => 'edit',
            'update' => 'edit',
            'edit' => 'edit',
            'd' => 'delete',
            'delete' => 'delete',
            'destroy' => 'delete',
            'remove' => 'delete',
            'manage' => 'manage',
            'assign' => 'assign',
            'export' => 'export',
            'import' => 'import',
            'approve' => 'approve',
            'reject' => 'reject',
            'toggle_status' => 'toggle_status',
            'toggle_pin' => 'toggle_pin',
            'change_status' => 'change_status',
        ];

        return $aliases[$action] ?? $action;
    }

    private function buildDisplayName(string $permissionName, string $group): string
    {
        $exactTranslation = $this->translateLabel($permissionName);

        if ($exactTranslation !== null) {
            return $exactTranslation;
        }

        $resource = $this->extractResourceName($permissionName);
        $resourceLabel = $this->buildResourceLabel($resource, $group);
        $actionTitle = $this->resolveActionTitle($permissionName);

        if ($actionTitle !== null && $resourceLabel !== '') {
            return trim($actionTitle . ' ' . $resourceLabel);
        }

        return $resourceLabel !== '' ? $resourceLabel : str_replace('_', ' ', $permissionName);
    }

    private function buildDescription(string $displayName): string
    {
        $displayName = trim($displayName);

        return $displayName === '' ? null : 'يمنح الوصول إلى ' . $displayName;
    }

    private function buildResourceLabel(string $resource, string $group): string
    {
        $candidates = [$resource];

        if (in_array($group, ['finance', 'hr', 'crm', 'employee', 'zatca'], true)) {
            $prefix = $group . '_';

            if (Str::startsWith($resource, $prefix)) {
                $candidates[] = substr($resource, strlen($prefix));
            }
        }

        foreach (array_unique(array_filter($candidates)) as $candidate) {
            $translation = $this->translateLabel($candidate);

            if ($translation !== null) {
                return $translation;
            }
        }

        return $this->humanizeWords(end($candidates) ?: $resource);
    }

    private function extractResourceName(string $permissionName): string
    {
        $normalizedName = str_replace('-', '_', $permissionName);

        foreach ([
            'change_status',
            'toggle_status',
            'export_excel',
            'export_pdf',
            'toggle_pin',
            'mark_in_progress',
            'mark_cancelled',
            'mark_completed',
            'view_statistics',
            'create_recruitment',
            'add_contract',
            'view_contract',
            'change_maid',
            'change_responsible',
            'approve',
            'reject',
            'assign',
            'manage',
            'control',
            'create',
            'edit',
            'update',
            'delete',
            'export',
            'import',
            'print',
            'view',
            'show',
            'read',
        ] as $suffix) {
            if ($normalizedName === $suffix || Str::endsWith($normalizedName, '_' . $suffix)) {
                return preg_replace('/_' . preg_quote($suffix, '/') . '$/', '', $normalizedName) ?: '';
            }
        }

        return $normalizedName;
    }

    private function resolveActionTitle(string $permissionName): ?string
    {
        $normalizedName = str_replace('-', '_', $permissionName);
        $suffixes = [
            'change_status' => 'تغيير حالة',
            'toggle_status' => 'تفعيل أو تعطيل',
            'export_excel' => 'تصدير Excel',
            'export_pdf' => 'تصدير PDF',
            'toggle_pin' => 'تثبيت',
            'approve' => 'اعتماد',
            'reject' => 'رفض',
            'assign' => 'تعيين',
            'manage' => 'إدارة',
            'control' => 'إدارة',
            'create' => 'إضافة',
            'edit' => 'تعديل',
            'update' => 'تحديث',
            'delete' => 'حذف',
            'export' => 'تصدير',
            'import' => 'استيراد',
            'print' => 'طباعة',
            'view' => 'عرض',
            'show' => 'عرض',
            'read' => 'قراءة',
        ];

        foreach ($suffixes as $suffix => $title) {
            if ($normalizedName === $suffix || Str::endsWith($normalizedName, '_' . $suffix)) {
                return $title;
            }
        }

        return null;
    }

    private function translateLabel(string $key): ?string
    {
        $key = trim($key);

        if ($key === '') {
            return null;
        }

        foreach (['permissions', 'admin', 'ingaz'] as $file) {
            $translation = __($file . '.' . $key);

            if ($translation !== $file . '.' . $key) {
                return $translation;
            }
        }

        return null;
    }

    private function humanizeWords(string $value): string
    {
        $dictionary = [
            'home' => 'الرئيسية',
            'dashboard' => 'لوحة التحكم',
            'reports' => 'التقارير',
            'report' => 'التقرير',
            'settings' => 'الإعدادات',
            'setting' => 'الإعداد',
            'account' => 'الحساب',
            'accounts' => 'الحسابات',
            'transfer' => 'التحويل',
            'clients' => 'العملاء',
            'client' => 'العميل',
            'suppliers' => 'الموردين',
            'supplier' => 'المورد',
            'banks' => 'البنوك',
            'bank' => 'البنك',
            'items' => 'الأصناف',
            'item' => 'الصنف',
            'stocks' => 'المخازن',
            'stock' => 'المخزن',
            'categories' => 'التصنيفات',
            'category' => 'التصنيف',
            'notifications' => 'الإشعارات',
            'notification' => 'الإشعار',
            'employees' => 'الموظفين',
            'employee' => 'الموظف',
            'messages' => 'الرسائل',
            'message' => 'الرسالة',
            'offers' => 'العروض',
            'offer' => 'العرض',
            'price' => 'السعر',
            'areas' => 'المناطق',
            'area' => 'المنطقة',
            'polls' => 'الاستبيانات',
            'poll' => 'الاستبيان',
            'attachments' => 'المرفقات',
            'attachment' => 'المرفق',
        ];

        $words = array_filter(explode('_', str_replace('-', '_', $value)));
        $translatedWords = array_map(fn (string $word) => $dictionary[$word] ?? $word, $words);

        return trim(implode(' ', $translatedWords));
    }

    private function legacyCodesToActions(string $codes): array
    {
        $map = [
            'c' => 'create',
            'r' => 'view',
            'u' => 'edit',
            'd' => 'delete',
        ];

        return collect(explode(',', $codes))
            ->map(fn ($code) => $map[trim($code)] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    private function ensurePermissionsTableExists(): void
    {
        if (!Schema::hasTable('permissions')) {
            throw new \RuntimeException('The permissions table does not exist yet.');
        }
    }

    private function permissionTableColumns(): array
    {
        if ($this->permissionColumns !== null) {
            return $this->permissionColumns;
        }

        $this->ensurePermissionsTableExists();

        return $this->permissionColumns = Schema::getColumnListing('permissions');
    }

    private function defaultGuardName(): string
    {
        return (string) config('access_control_permission_registry.defaults.guard_name', 'admin');
    }

    private function loadRegistryConfig(): array
    {
        return $this->requirePhpConfig($this->registryConfigPath());
    }

    private function loadLegacyConfig(): array
    {
        return $this->requirePhpConfig($this->legacyConfigPath());
    }

    private function saveRegistryConfig(array $config): void
    {
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        File::put($this->registryConfigPath(), $content);
    }

    private function requirePhpConfig(string $path): array
    {
        if (!File::exists($path)) {
            return [];
        }

        $config = require $path;

        return is_array($config) ? $config : [];
    }

    private function registryConfigPath(): string
    {
        return dirname(__DIR__, 2) . '/config/permission_registry.php';
    }

    private function legacyConfigPath(): string
    {
        return dirname(__DIR__, 2) . '/config/laratrust_seeder.php';
    }
}
