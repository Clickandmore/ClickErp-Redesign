<?php

namespace App\Support;

use App\Http\Middleware\EnsurePackageRoutePermission;
use App\Models\Permission;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * كتالوج الصلاحيات: المصدر الموحد الذي يجيب على سؤال واحد فقط:
 * "ما هي الصلاحيات التي يستطيع النظام فعلياً أن يطلبها من المستخدم؟"
 *
 * يستخرجها من المسارات نفسها بدل الاعتماد على ملفات تعريف منفصلة:
 *  - admin.route.permission          => AdminRoutePermissionMatrix
 *  - package.route.permission:module => EnsurePackageRoutePermission
 *  - permission:name,guard           => middleware سبيتي المباشر
 *
 * أي مسار جديد يظهر تلقائياً هنا، ومنه في شاشة /dash/perms بعد أمر المزامنة.
 */
class PermissionCatalog
{
    /**
     * كل مسار إداري محمي ومعه الصلاحيات المطلوبة له.
     *
     * @return Collection<int, array{name: string, uri: string, methods: string, module: string, source: string, permissions: array<int, string>}>
     */
    public static function protectedRoutes(): Collection
    {
        $packageMiddleware = new EnsurePackageRoutePermission();

        return self::adminRoutes()
            ->map(function (RoutingRoute $route) use ($packageMiddleware) {
                $routeName = (string) $route->getName();
                $middleware = self::middlewareOf($route);
                $permissions = [];
                $source = null;

                if (in_array('admin.route.permission', $middleware, true)) {
                    $permissions = AdminRoutePermissionMatrix::resolve($routeName);
                    $source = 'matrix';
                }

                foreach ($middleware as $name) {
                    if (!is_string($name)) {
                        continue;
                    }

                    if (str_starts_with($name, 'package.route.permission:')) {
                        $module = substr($name, strlen('package.route.permission:'));
                        $permissions = $packageMiddleware->resolvePermissions($routeName, $module);
                        $source = 'package:' . $module;
                    }

                    if (str_starts_with($name, 'permission:')) {
                        $spec = explode(',', substr($name, strlen('permission:')));
                        $permissions = array_merge($permissions, array_filter([trim($spec[0] ?? '')]));
                        $source = $source ?? 'middleware';
                    }
                }

                $permissions = array_values(array_unique(array_filter($permissions)));

                if ($permissions === []) {
                    return null;
                }

                return [
                    'name' => $routeName,
                    'uri' => $route->uri(),
                    'methods' => implode('|', $route->methods()),
                    'module' => self::moduleOf($route),
                    'source' => $source ?? 'unknown',
                    'permissions' => $permissions,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * المسارات الإدارية التي لا تمر على أي فحص صلاحية إطلاقاً.
     *
     * @return Collection<int, array{name: string, uri: string, methods: string, module: string}>
     */
    public static function ungatedRoutes(): Collection
    {
        return self::adminRoutes()
            ->filter(function (RoutingRoute $route) {
                $middleware = self::middlewareOf($route);

                return !in_array('admin.route.permission', $middleware, true)
                    && !collect($middleware)->contains(
                        fn ($name) => is_string($name)
                            && (str_starts_with($name, 'package.route.permission:') || str_starts_with($name, 'permission:'))
                    );
            })
            ->map(fn (RoutingRoute $route) => [
                'name' => (string) $route->getName(),
                'uri' => $route->uri(),
                'methods' => implode('|', $route->methods()),
                'module' => self::moduleOf($route),
            ])
            ->values();
    }

    /**
     * كل اسم صلاحية يمكن أن يطلبه النظام. هذا ما يجب أن يوجد في جدول permissions.
     *
     * @return Collection<int, string>
     */
    public static function requiredPermissions(): Collection
    {
        return self::protectedRoutes()
            ->flatMap(fn (array $route) => $route['permissions'])
            ->merge(array_keys(AdminDispatchPermissions::definitions()))
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * الصلاحيات المسجلة فعلياً في قاعدة البيانات (بما فيها المعطلة).
     *
     * @return Collection<int, string>
     */
    public static function registeredPermissions(string $guard = 'admin'): Collection
    {
        return Permission::withoutGlobalScopes()
            ->where('guard_name', $guard)
            ->pluck('name')
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * الصلاحيات التي تظهر فعلا في شاشة /dash/perms ويمكن منحها.
     * الصف المعطل (is_active = 0) يختفي عن كل استعلامات الموديل بسبب global scope،
     * فهو موجود في الجدول لكنه غير قابل للمنح.
     *
     * @return Collection<int, string>
     */
    public static function grantablePermissions(string $guard = 'admin'): Collection
    {
        return Permission::query()
            ->where('guard_name', $guard)
            ->pluck('name')
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * صلاحيات يطلبها النظام ولا يمكن منحها لأي دور،
     * إما لأنها غير موجودة في الجدول أو لأنها معطلة.
     *
     * @return Collection<int, string>
     */
    public static function missingPermissions(string $guard = 'admin'): Collection
    {
        return self::requiredPermissions()->diff(self::grantablePermissions($guard))->values();
    }

    /**
     * صلاحيات موجودة في الشاشة ولا يطلبها أي مسار.
     * قد تكون مستخدمة داخل الواجهات عبر @can، لذلك تُقسم إلى نوعين.
     *
     * @return array{referenced: Collection<int, string>, orphan: Collection<int, string>}
     */
    public static function unusedPermissions(string $guard = 'admin'): array
    {
        $unused = self::grantablePermissions($guard)->diff(self::requiredPermissions())->values();
        $haystack = self::codeReferenceHaystack();

        return [
            'referenced' => $unused->filter(fn (string $name) => str_contains($haystack, $name))->values(),
            'orphan' => $unused->reject(fn (string $name) => str_contains($haystack, $name))->values(),
        ];
    }

    /**
     * مسارات لا يمكن لأي دور الوصول إليها لأن كل صلاحياتها المطلوبة غير مسجلة.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function blockedRoutes(string $guard = 'admin'): Collection
    {
        $registered = self::grantablePermissions($guard)->flip();

        return self::protectedRoutes()
            ->filter(fn (array $route) => collect($route['permissions'])->every(
                fn (string $permission) => !$registered->has($permission)
            ))
            ->values();
    }

    /**
     * @return Collection<int, RoutingRoute>
     */
    private static function adminRoutes(): Collection
    {
        return collect(Route::getRoutes())
            ->filter(function (RoutingRoute $route) {
                if (!$route->getName()) {
                    return false;
                }

                $middleware = self::middlewareOf($route);

                return in_array('admin', $middleware, true) || in_array('auth:admin', $middleware, true);
            })
            ->values();
    }

    /**
     * @return array<int, mixed>
     */
    private static function middlewareOf(RoutingRoute $route): array
    {
        $middleware = $route->getAction('middleware') ?? [];

        if (is_string($middleware)) {
            return [$middleware];
        }

        return is_array($middleware) ? $middleware : [];
    }

    private static function moduleOf(RoutingRoute $route): string
    {
        $routeName = (string) $route->getName();
        $uri = $route->uri();

        return match (true) {
            str_starts_with($routeName, 'finance.') || str_contains($uri, 'finance') => 'finance',
            str_starts_with($routeName, 'zatca.') || str_contains($uri, 'zatca') => 'zatca',
            str_starts_with($routeName, 'hr.') || str_contains($uri, '/hr') => 'hr',
            str_starts_with($routeName, 'crm.') => 'crm',
            str_starts_with($routeName, 'employee.') => 'employee',
            default => 'ingaz',
        };
    }

    private static ?string $codeHaystack = null;

    /**
     * محتوى الكود والواجهات مجمعاً مرة واحدة، لفحص الصلاحيات المستخدمة عبر @can.
     */
    private static function codeReferenceHaystack(): string
    {
        if (self::$codeHaystack !== null) {
            return self::$codeHaystack;
        }

        $buffer = '';

        foreach (['app', 'resources/views', 'routes', 'packages'] as $relativePath) {
            $path = base_path($relativePath);

            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveCallbackFilterIterator(
                    new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                    fn (\SplFileInfo $file) => $file->isDir()
                        ? !in_array($file->getFilename(), ['vendor', 'node_modules', '.git'], true)
                        : in_array($file->getExtension(), ['php', 'blade'], true)
                )
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $buffer .= file_get_contents($file->getPathname());
                }
            }
        }

        return self::$codeHaystack = $buffer;
    }
}
