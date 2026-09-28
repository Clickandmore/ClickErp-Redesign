<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class EnsureHourlyMaidAccess
{
    public function handle(Request $request, Closure $next, string $channel) {
        $guard=auth($channel==='web' ? 'hourly_maid' : 'hourly_maid_api');
        $maid=$guard->user();
        $version=$channel==='web' ? $request->session()->get('hourly_maid_version') : ($maid ? $guard->payload()->get('maid_version') : null);
        if (!$maid || !$maid->canLoginVia($channel) || (int)$version !== $maid->hourly_auth_version) {
            if ($channel==='mobile') return response()->json(['message'=>'يرجى تسجيل الدخول بحساب عاملة متاح على الجوال.'],401);
            $guard->logout(); $request->session()->forget('hourly_maid_version');
            return redirect()->route('hourly-maid.login');
        }
        return $next($request);
    }
}
