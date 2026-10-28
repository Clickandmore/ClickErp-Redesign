<?php

namespace App\Http\Middleware;

use App\Models\McpAccessToken;
use App\Support\BranchQueryContext;
use App\Support\DashboardMaintenanceSettings;
use App\Support\McpSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * يحوّل توكن MCP إلى موظف مسجّل دخوله تحت حارس admin لمدة الطلب فقط.
 *
 * تسجيل الموظف في حارس admin هو ما يجعل BranchScope وفحوص الصلاحيات
 * تعمل على أدوات MCP تماماً كما تعمل على شاشات لوحة التحكم.
 */
class AuthenticateMcpToken
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless(McpSettings::enabled(), 404);

        $token = McpAccessToken::findActive((string) $request->bearerToken());
        $admin = $token?->admin;

        if (!$admin || ($admin->status ?? 'active') === 'suspended') {
            return response()->json(['error' => 'invalid_token'], 401)
                ->header('WWW-Authenticate', 'Bearer realm="clickerp-mcp", error="invalid_token"');
        }

        if (DashboardMaintenanceSettings::enabled()) {
            return response()->json(['error' => 'maintenance'], 503)->header('Retry-After', '300');
        }

        Auth::shouldUse('admin');
        Auth::guard('admin')->setUser($admin);
        BranchQueryContext::flush();

        // تحديث last_used_at مرة في الدقيقة على الأكثر، لا مع كل طلب.
        if (!$token->last_used_at || $token->last_used_at->lt(now()->subMinute())) {
            $token->forceFill(['last_used_at' => now()])->saveQuietly();
        }

        $request->attributes->set('mcp_token', $token);

        return $next($request);
    }
}
