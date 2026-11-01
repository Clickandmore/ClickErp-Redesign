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
    /** @var array<int, array<string, bool>> صلاحية الروابط داخل الطلب، حتى لا تتكرر الفحوص مع كل صف. */
    private array $linkAccess = [];

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

    protected function offset(array $arguments): int
    {
        return max(0, (int) ($arguments['offset'] ?? 0));
    }

    protected function offsetProperty(): array
    {
        return ['type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'لجلب الصفحة التالية: أرسل قيمة next_offset من الرد السابق.'];
    }

    /** next_offset = null يعني أنه لا توجد نتائج أخرى. */
    protected function pageInfo(int $total, int $returned, array $arguments): array
    {
        $next = $this->offset($arguments) + $returned;

        return [
            'total' => $total,
            'returned' => $returned,
            'offset' => $this->offset($arguments),
            'next_offset' => $returned > 0 && $next < $total ? $next : null,
        ];
    }

    /**
     * رابط السجل في لوحة التحكم، فقط إن كان الموظف يملك صلاحية تلك الشاشة.
     * به يصل الموظف إلى الجوال الكامل من الشاشة بدل تخفيف الإخفاء في رد المساعد.
     */
    protected function link(string $route, $parameters, ?string $gate = null): ?string
    {
        $admin = auth('admin')->user();

        if (!$admin instanceof Admin) {
            return null;
        }

        // بعض شاشات العرض بلا إدخال في المصفوفة (RentContract.show)؛ تُفحص بصلاحية قائمتها $gate.
        $gate ??= $route;
        $this->linkAccess[$admin->id][$gate] ??= self::routeAllows($admin, $gate);

        return $this->linkAccess[$admin->id][$gate] ? route($route, $parameters) : null;
    }

    /**
     * يخفي الأرقام الطويلة داخل النص الحر (ملاحظات، شكاوى، أوصاف): جوال أو هوية أو جواز كتبها موظف
     * أو عميل. تبقى آخر 4 خانات، وتُستثنى التواريخ. يدعم الأرقام العربية والفواصل بين الخانات.
     */
    protected function maskText(?string $text): ?string
    {
        if ($text === null || $text === '' || !McpSettings::maskSensitive()) {
            return $text;
        }

        return preg_replace_callback(
            '/(?<![\p{L}\p{Nd}])\+?[A-Za-z]{0,3}\p{Nd}(?:[\s-]?\p{Nd}){6,}(?![\p{L}\p{Nd}])/u',
            function (array $match) {
                if (preg_match('/^(\p{Nd}{4}-\p{Nd}{1,2}-\p{Nd}{1,2}|\p{Nd}{1,2}-\p{Nd}{1,2}-\p{Nd}{4})$/u', $match[0])) {
                    return $match[0];
                }

                return $this->mask((string) preg_replace('/[\s-]/u', '', $match[0]));
            },
            $text
        ) ?? $text;
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
