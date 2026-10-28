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

    public function isAllowedFor(Admin $admin): bool
    {
        $route = $this->gateRoute();

        if ($route === null) {
            return true;
        }

        if (SystemModuleManager::disabledModuleForRoute($route, '')) {
            return false;
        }

        $permissions = AdminRoutePermissionMatrix::resolve($route);

        // مسار بلا إدخال في المصفوفة يعني خطأ في تعريف الأداة، لا إذناً مفتوحاً.
        if ($permissions === []) {
            return false;
        }

        return AdminRoutePermissionMatrix::adminHasAnyPermission($admin, $permissions);
    }

    public function definition(): array
    {
        return [
            'name' => $this->name(),
            'description' => $this->description(),
            'inputSchema' => $this->inputSchema(),
            'annotations' => ['readOnlyHint' => true, 'openWorldHint' => false],
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
