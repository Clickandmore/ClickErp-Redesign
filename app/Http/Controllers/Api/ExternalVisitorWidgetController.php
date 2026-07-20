<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebsiteVisitor;
use App\Models\WebsiteVisitorSite;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExternalVisitorWidgetController extends Controller
{
    public function heartbeat(Request $request, string $siteKey)
    {
        $site = $this->site($siteKey);
        $this->authorizeOrigin($request, $site, true);

        $validated = $request->validate([
            'current_url' => ['required', 'url', 'max:2048'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'url', 'max:2048'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'screen_width' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'screen_height' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        if (!$site->allowsOrigin($validated['current_url'])) {
            abort(403, 'The tracked URL is outside the allowed domains.');
        }

        $visitor = $this->visitor($request, $site, true);
        $latestPageView = $visitor->pageViews()->latest('visited_at')->first();

        if (!$latestPageView || $latestPageView->url !== $validated['current_url']) {
            $visitor->pageViews()->create([
                'url' => $validated['current_url'],
                'title' => $validated['page_title'] ?? null,
                'referrer' => $validated['referrer'] ?? null,
                'visited_at' => now(),
            ]);
        }

        $sessionStartedAt = $visitor->session_started_at;
        if (!$sessionStartedAt || !$visitor->last_seen_at || $visitor->last_seen_at->lt(now()->subMinutes(10))) {
            $sessionStartedAt = now();
        }

        $visitor->update([
            'website_visitor_site_id' => $site->id,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 2000, ''),
            'current_url' => $validated['current_url'],
            'landing_url' => $visitor->landing_url ?: $validated['current_url'],
            'referrer' => $visitor->referrer ?: ($validated['referrer'] ?? null),
            'page_title' => $validated['page_title'] ?? null,
            'locale' => substr((string) $request->header('Accept-Language'), 0, 20) ?: null,
            'timezone' => $validated['timezone'] ?? null,
            'screen_width' => $validated['screen_width'] ?? null,
            'screen_height' => $validated['screen_height'] ?? null,
            'session_started_at' => $sessionStartedAt,
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'online' => true,
            'conversation_started' => (bool) $visitor->conversation_started_at,
        ]);
    }

    public function messages(Request $request, string $siteKey)
    {
        $site = $this->site($siteKey);
        $this->authorizeOrigin($request, $site);
        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);
        $visitor = $this->visitor($request, $site);
        $visitor->update(['last_seen_at' => now()]);

        $messages = $visitor->messages()
            ->when(!empty($validated['after_id']), fn ($query) => $query->where('id', '>', $validated['after_id']))
            ->oldest('id')
            ->limit(100)
            ->get();

        $visitor->messages()
            ->where('sender', 'admin')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'conversation_started' => (bool) $visitor->conversation_started_at,
            'messages' => $messages->map(fn ($message) => $this->messagePayload($message)),
        ]);
    }

    public function send(Request $request, string $siteKey)
    {
        $site = $this->site($siteKey);
        $this->authorizeOrigin($request, $site);
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);
        $visitor = $this->visitor($request, $site);
        $visitor->update([
            'last_seen_at' => now(),
            'conversation_started_at' => $visitor->conversation_started_at ?: now(),
        ]);
        $message = $visitor->messages()->create([
            'sender' => 'visitor',
            'body' => trim($validated['body']),
        ]);

        return response()->json(['message' => $this->messagePayload($message)], 201);
    }

    private function site(string $siteKey): WebsiteVisitorSite
    {
        return WebsiteVisitorSite::query()
            ->where('public_key', $siteKey)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function authorizeOrigin(Request $request, WebsiteVisitorSite $site, bool $externalOnly = false): void
    {
        $origin = $request->headers->get('Origin');
        if ($site->allowsOrigin($origin)) {
            return;
        }

        $source = $origin ?: $request->headers->get('Referer');
        $originHost = strtolower((string) parse_url((string) $source, PHP_URL_HOST));
        $appHost = strtolower($request->getHost());
        if (!$externalOnly && $originHost !== '' && $originHost === $appHost) {
            return;
        }

        abort(403, 'This origin is not allowed.');
    }

    private function visitor(Request $request, WebsiteVisitorSite $site, bool $allowCreate = false): WebsiteVisitor
    {
        $token = (string) $request->header('X-Visitor-Token');
        if (!Str::isUuid($token)) {
            abort(422, 'Invalid visitor token.');
        }

        $visitor = WebsiteVisitor::query()->where('token', $token)->first();
        if ($visitor && $visitor->website_visitor_site_id !== $site->id) {
            abort(403, 'Visitor token belongs to another site.');
        }

        if (!$visitor && !$allowCreate) {
            abort(404, 'Visitor session has not started yet.');
        }

        return $visitor ?: WebsiteVisitor::create([
            'token' => $token,
            'website_visitor_site_id' => $site->id,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 2000, ''),
            'last_seen_at' => now(),
            'session_started_at' => now(),
        ]);
    }

    private function messagePayload($message): array
    {
        return [
            'id' => $message->id,
            'sender' => $message->sender,
            'body' => $message->body,
            'created_at' => optional($message->created_at)->toIso8601String(),
        ];
    }
}
