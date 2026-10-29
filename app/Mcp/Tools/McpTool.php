<?php

namespace App\Mcp\Tools;

use App\Models\auth\Admin;
use App\Support\AdminRoutePermissionMatrix;
use App\Support\McpSettings;
use App\Support\SystemModuleManager;

/**
 * أداة MCP واحدة.
 *
 * كل أداة تستعير حمايتها من شاشة موجودة في لوحة التحكم عبر gateRoute():
 * نفس صلاحيات AdminRoutePermissionMatrix، ونفس قفل موديولات Super Admin.
 * فلو تغيّرت صلاحية الشاشة تتغير صلاحية الأداة تلقائياً، ولا توجد صلاحيات MCP منفصلة.
 */
abstract class McpTool
{
    abstract public function name(): string;

    abstract public function description(): string;

    /** JSON Schema لمدخلات الأداة. */
    abstract public function inputSchema(): array;

    /**
     * اسم المسار الإداري الذي تُستعار صلاحيته. null = متاحة لكل صاحب توكن صالح.
     */
    abstract protected function gateRoute(): ?string;

    abstract public function handle(array $arguments, Admin $admin): array;

    /**
     * مسارات الحزم (finance، hr...) محمية بـ package.route.permission لا بالمصفوفة.
     * أعد اسم الحزمة هنا ليُقرأ إذن gateRoute() من EnsurePackageRoutePermission.
     */
    protected function gatePackage(): ?string
    {
        return null;
    }

    public function isAllowedFor(Admin $admin): bool
    {
        return self::routeAllows($admin, $this->gateRoute(), $this->gatePackage());
    }

    /** نفس فحص الشاشة: موديول غير مغلق + إحدى صلاحيات المسار. */
    public static function routeAllows(Admin $admin, ?string $route, ?string $package = null): bool
    {
        if ($route === null) {
            return true;
        }

        // مسار محذوف أو مكتوب خطأً يعني خطأ في تعريف الأداة، لا إذناً مفتوحاً.
        if (!app('router')->has($route) || SystemModuleManager::disabledModuleForRoute($route, '')) {
            return false;
        }

        $permissions = $package
            ? app(\App\Http\Middleware\EnsurePackageRoutePermission::class)->resolvePermissions($route, $package)
            : AdminRoutePermissionMatrix::resolve($route);

        if ($permissions === []) {
            return false;
        }

        return AdminRoutePermissionMatrix::adminHasAnyPermission($admin, $permissions);
    }

    /** للعرض في شاشة Super Admin. */
    public function gateRouteName(): ?string
    {
        return $this->gateRoute();
    }

    /**
     * أدوات الإضافة والتعديل ترجع true، فلا تظهر إلا مع مفتاح الكتابة العام
     * ومستوى «كتابة» للموظف في شاشة Super Admin.
     */
    public function isWriteTool(): bool
    {
        return false;
    }

    /** مجموعة الأداة في شاشة Super Admin؛ تُستنتج من الشاشة المستعارة ويمكن تجاوزها. */
    public function category(): string
    {
        $route = (string) $this->gateRoute();

        return match (true) {
            str_starts_with($route, 'RecruitmentContract.'), str_starts_with($route, 'RentContract.') => 'contracts',
            str_starts_with($route, 'maids.') => 'maids',
            str_starts_with($route, 'clients.') => 'customers',
            str_starts_with($route, 'orders.') => 'orders',
            str_starts_with($route, 'complaints.') => 'complaints',
            str_starts_with($route, 'finance.'), str_starts_with($route, 'invoices.') => 'finance',
            default => 'system',
        };
    }

    public function definition(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'inputSchema' => $this->inputSchema(),
            'annotations' => [
                'readOnlyHint' => !$this->isWriteTool(),
                'destructiveHint' => $this->isWriteTool(),
                'openWorldHint' => false,
            ],
        ];
    }

    protected function limit(array $arguments, int $default = 20): int
    {
        $max = (int) config('mcp.max_results', 50);

        return max(1, min($max, (int) ($arguments['limit'] ?? $default)));
    }

    /** يُبقي آخر 4 خانات فقط عندما يكون الإخفاء مفعّلاً. */
    protected function mask(?string $value): ?string
    {
        if ($value === null || $value === '' || !McpSettings::maskSensitive()) {
            return $value;
        }

        $length = mb_strlen($value);

        return $length <= 4
            ? str_repeat('*', $length)
            : str_repeat('*', $length - 4) . mb_substr($value, -4);
    }

    protected function date($value): ?string
    {
        if (!$value) {
            return null;
        }

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) $value;
    }
}
