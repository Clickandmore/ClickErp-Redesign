<?php

namespace App\Http\Middleware;

use App\Mcp\McpAccess;
use App\Models\McpAccessToken;
use App\Models\McpAuditLog;
use App\Support\BranchQueryContext;
use App\Support\DashboardMaintenanceSettings;
use App\Support\McpSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
            // resource_metadata يدلّ claude.ai وChatGPT على OAuth فيبدآن تسجيل الدخول تلقائياً.
            $challenge = 'Bearer realm="clickerp-mcp", error="invalid_token"';
            if (McpSettings::oauthEnabled()) {
                $challenge .= ', resource_metadata="' . \App\Http\Controllers\McpOAuthController::absoluteUrl('/.well-known/oauth-protected-resource') . '"';
            }

            return response()->json(['error' => 'invalid_token'], 401)->header('WWW-Authenticate', $challenge);
        }

        if (DashboardMaintenanceSettings::enabled()) {
            return response()->json(['error' => 'maintenance'], 503)->header('Retry-After', '300');
        }

        // التوكن صالح لكن Super Admin لم يفعّل هذا الموظف (أو أوقفه): 403 لا 401،
        // فالعميل لا يعيد تسجيل الدخول في حلقة بلا فائدة.
        if (!app(McpAccess::class)->adminAllowed($admin)) {
            $this->auditBlocked($admin, $token, $request);

            return response()->json([
                'error' => 'access_denied',
                'error_description' => 'لم تفعّل الإدارة استخدام المساعد الذكي لحسابك. تواصل مع مدير النظام.',
            ], 403);
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

    /** محاولة واحدة في الدقيقة لكل توكن تكفي لتظهر في السجل دون أن تغرقه إعادة المحاولات. */
    private function auditBlocked($admin, McpAccessToken $token, Request $request): void
    {
        if (!Cache::add('mcp:blocked-audit:' . $token->id, true, now()->addMinute())) {
            return;
        }

        try {
            McpAuditLog::query()->create([
                'admin_id' => $admin->id,
                'token_id' => $token->id,
                'method' => (string) ($request->json('method') ?: 'request'),
                'tool' => $request->json('params.name'),
                'status' => 'blocked',
                'ip' => $request->ip(),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // السجل لا يغيّر الرد.
        }
    }
}
