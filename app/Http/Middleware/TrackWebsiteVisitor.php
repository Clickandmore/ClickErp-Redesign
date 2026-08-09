<?php

namespace App\Http\Middleware;

use App\Models\WebsiteVisitor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TrackWebsiteVisitor
{
    public function handle(Request $request, Closure $next)
    {
        if (!config('website-visitors.legacy_internal_tracking', false)) {
            return $next($request);
        }

        $response = $next($request);

        if (
            !$request->isMethod('get')
            || $request->expectsJson()
            || !$response->isSuccessful()
            || !str_contains((string) $response->headers->get('Content-Type'), 'text/html')
            || !Schema::hasTable('website_visitors')
        ) {
            return $response;
        }

        $token = $request->session()->get('website_visitor_token');
        if (!$token) {
            $token = (string) Str::uuid();
            $request->session()->put('website_visitor_token', $token);
        }

        $visitor = WebsiteVisitor::firstOrCreate(
            ['token' => $token],
            [
                'user_id' => auth('web')->id(),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 2000, ''),
                'current_url' => $request->fullUrl(),
                'landing_url' => $request->fullUrl(),
                'referrer' => $request->headers->get('referer'),
                'locale' => app()->getLocale(),
                'session_started_at' => now(),
                'last_seen_at' => now(),
            ]
        );

        $sessionStartedAt = $visitor->session_started_at;
        if (!$sessionStartedAt || !$visitor->last_seen_at || $visitor->last_seen_at->lt(now()->subMinutes(10))) {
            $sessionStartedAt = now();
        }

        $visitor->update([
            'user_id' => auth('web')->id(),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 2000, ''),
            'current_url' => $request->fullUrl(),
            'landing_url' => $visitor->landing_url ?: $request->fullUrl(),
            'referrer' => $visitor->referrer ?: $request->headers->get('referer'),
            'locale' => app()->getLocale(),
            'session_started_at' => $sessionStartedAt,
            'last_seen_at' => now(),
        ]);

        if (Schema::hasTable('website_visitor_page_views')) {
            $visitor->pageViews()->create([
                'url' => $request->fullUrl(),
                'title' => null,
                'referrer' => $request->headers->get('referer'),
                'visited_at' => now(),
            ]);
        }

        return $response;
    }
}
