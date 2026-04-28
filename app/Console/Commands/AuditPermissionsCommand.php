<?php

namespace App\Console\Commands;

use App\Http\Middleware\EnsurePackageRoutePermission;
use App\Models\Permission;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Console\Command;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use ReflectionClass;

class AuditPermissionsCommand extends Command
{
    protected $signature = 'permissions:audit
        {--module=all : all, ingaz, hr, finance, zatca}
        {--show-unused : Show permissions that are registered but not required by protected routes}
        {--show-unprotected : Show admin routes that have no route-permission middleware}
        {--json : Output the audit result as JSON}';

    protected $description = 'Audit registered admin permissions against protected admin, HR, Finance, and ZATCA routes.';

    public function handle(): int
    {
        $module = strtolower((string) $this->option('module'));
        $validModules = ['all', 'ingaz', 'hr', 'finance', 'zatca'];

        if (!in_array($module, $validModules, true)) {
            $this->error('Invalid module. Use one of: ' . implode(', ', $validModules));
            return self::FAILURE;
        }

        $registeredPermissions = Permission::query()
            ->where('guard_name', 'admin')
            ->pluck('name')
            ->unique()
            ->sort()
            ->values();

        $protectedRoutes = $this->protectedRoutes($module);
        $requiredPermissions = $protectedRoutes
            ->flatMap(fn (array $route) => $route['permissions'])
            ->unique()
            ->sort()
            ->values();

        $missingPermissions = $requiredPermissions
            ->diff($registeredPermissions)
            ->values();

        $unusedPermissions = $registeredPermissions
            ->diff($requiredPermissions)
            ->values();

        $unprotectedRoutes = $this->option('show-unprotected')
            ? $this->unprotectedAdminRoutes($module)
            : collect();

        $result = [
            'module' => $module,
            'registered_permissions_count' => $registeredPermissions->count(),
            'protected_routes_count' => $protectedRoutes->count(),
            'required_permissions_count' => $requiredPermissions->count(),
            'missing_permissions_count' => $missingPermissions->count(),
            'unused_permissions_count' => $unusedPermissions->count(),
            'unprotected_admin_routes_count' => $unprotectedRoutes->count(),
            'missing_permissions' => $missingPermissions->values()->all(),
            'unused_permissions' => $this->option('show-unused') ? $unusedPermissions->values()->all() : [],
            'unprotected_admin_routes' => $this->option('show-unprotected') ? $unprotectedRoutes->values()->all() : [],
            'protected_routes' => $protectedRoutes->values()->all(),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
            return $missingPermissions->isEmpty() ? self::SUCCESS : self::FAILURE;
        }

        $this->renderSummary($result);

        if ($missingPermissions->isNotEmpty()) {
            $this->newLine();
            $this->error('صلاحيات مطلوبة من المسارات لكنها غير موجودة في قاعدة البيانات:');
            $this->line($missingPermissions->implode(PHP_EOL));
        }

        if ($this->option('show-unused') && $unusedPermissions->isNotEmpty()) {
            $this->newLine();
            $this->warn('صلاحيات مسجلة في صفحة الصلاحيات لكنها غير مطلوبة مباشرة من أي مسار محمي:');
            $this->line($unusedPermissions->implode(PHP_EOL));
        }

        if ($this->option('show-unprotected') && $unprotectedRoutes->isNotEmpty()) {
            $this->newLine();
            $this->warn('مسارات إدارية لا تملك Middleware صلاحيات مخصص:');
            $this->table(
                ['method', 'uri', 'name', 'module'],
                $unprotectedRoutes->map(fn (array $route) => [
                    $route['method'],
                    $route['uri'],
                    $route['name'],
                    $route['module'],
                ])->all()
            );
        }

        return $missingPermissions->isEmpty() ? self::SUCCESS : self::FAILURE;
    }

    private function protectedRoutes(string $module): Collection
    {
        return collect(app('router')->getRoutes())
            ->map(fn (Route $route) => $this->routeAuditRow($route))
            ->filter()
            ->filter(fn (array $row) => $module === 'all' || $row['module'] === $module)
            ->values();
    }

    private function routeAuditRow(Route $route): ?array
    {
        $routeName = $route->getName();

        if (!$routeName) {
            return null;
        }

        $middleware = $this->routeMiddleware($route);
        $permissions = [];
        $module = null;

        if (in_array('admin.route.permission', $middleware, true)) {
            $permissions = AdminRoutePermissionMatrix::resolve($routeName);
            $module = 'ingaz';
        }

        foreach ($middleware as $middlewareName) {
            if (!is_string($middlewareName) || !str_starts_with($middlewareName, 'package.route.permission:')) {
                continue;
            }

            $module = substr($middlewareName, strlen('package.route.permission:'));
            $permissions = $this->resolvePackagePermissions($routeName, $module);
        }

        if (!$module || $permissions === []) {
            return null;
        }

        return [
            'method' => implode('|', $route->methods()),
            'uri' => $route->uri(),
            'name' => $routeName,
            'module' => $module,
            'permissions' => array_values(array_unique($permissions)),
        ];
    }

    private function resolvePackagePermissions(string $routeName, string $module): array
    {
        $middleware = new EnsurePackageRoutePermission();
        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('resolvePermissions');
        $method->setAccessible(true);

        return (array) $method->invoke($middleware, $routeName, $module);
    }

    private function unprotectedAdminRoutes(string $module): Collection
    {
        return collect(app('router')->getRoutes())
            ->filter(function (Route $route) {
                $middleware = $this->routeMiddleware($route);

                if (!$route->getName()) {
                    return false;
                }

                $isAdminRoute = in_array('admin', $middleware, true)
                    || in_array('auth:admin', $middleware, true);

                $hasPermissionMiddleware = in_array('admin.route.permission', $middleware, true)
                    || collect($middleware)->contains(fn ($name) => is_string($name) && str_starts_with($name, 'package.route.permission:'))
                    || collect($middleware)->contains(fn ($name) => is_string($name) && str_starts_with($name, 'permission:'));

                return $isAdminRoute && !$hasPermissionMiddleware;
            })
            ->map(function (Route $route) {
                return [
                    'method' => implode('|', $route->methods()),
                    'uri' => $route->uri(),
                    'name' => $route->getName(),
                    'module' => $this->guessModule($route),
                ];
            })
            ->filter(fn (array $row) => $module === 'all' || $row['module'] === $module)
            ->values();
    }

    private function guessModule(Route $route): string
    {
        $routeName = (string) $route->getName();
        $uri = $route->uri();

        if (str_starts_with($routeName, 'finance.') || str_contains($uri, 'finance')) {
            return 'finance';
        }

        if (str_starts_with($routeName, 'zatca.') || str_contains($uri, 'zatca')) {
            return 'zatca';
        }

        if (str_starts_with($routeName, 'hr.') || str_contains($uri, 'hr')) {
            return 'hr';
        }

        return 'ingaz';
    }

    private function routeMiddleware(Route $route): array
    {
        $middleware = $route->getAction('middleware') ?? [];

        if (is_string($middleware)) {
            return [$middleware];
        }

        return is_array($middleware) ? $middleware : [];
    }

    private function renderSummary(array $result): void
    {
        $this->info('=== تقرير اختبار الصلاحيات ===');
        $this->table(
            ['البند', 'القيمة'],
            [
                ['النطاق', $result['module']],
                ['الصلاحيات المسجلة في قاعدة البيانات', $result['registered_permissions_count']],
                ['المسارات المحمية بصلاحيات', $result['protected_routes_count']],
                ['الصلاحيات المطلوبة فعلياً', $result['required_permissions_count']],
                ['صلاحيات مطلوبة وغير موجودة', $result['missing_permissions_count']],
                ['صلاحيات مسجلة وغير مستخدمة مباشرة', $result['unused_permissions_count']],
                ['مسارات إدارية غير محمية بصلاحية مخصصة', $result['unprotected_admin_routes_count']],
            ]
        );

        if ((int) $result['missing_permissions_count'] === 0) {
            $this->info('لا توجد صلاحيات مطلوبة مفقودة من قاعدة البيانات.');
        }
    }
}
