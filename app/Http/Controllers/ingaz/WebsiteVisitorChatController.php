<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\WebsiteVisitor;
use Illuminate\Http\Request;

class WebsiteVisitorChatController extends Controller
{
    public function index()
    {
        return view('admin.ingaz.website_visitors.index');
    }

    public function data()
    {
        $onlineSince = now()->subSeconds(70);

        $visitors = WebsiteVisitor::query()
            ->with('user:id,name,phone,email')
            ->withCount(['pageViews', 'messages'])
            ->withCount([
                'messages as unread_messages_count' => fn ($query) => $query
                    ->where('sender', 'visitor')
                    ->whereNull('read_at'),
            ])
            ->where('last_seen_at', '>=', $onlineSince)
            ->orderByDesc('last_seen_at')
            ->limit(100)
            ->get();

        return response()->json([
            'online_count' => $visitors->count(),
            'visitors' => $visitors->map(function (WebsiteVisitor $visitor) {
                return [
                    'id' => $visitor->id,
                    'label' => $visitor->user?->name ?: 'زائر #' . str_pad((string) $visitor->id, 4, '0', STR_PAD_LEFT),
                    'phone' => $visitor->user?->phone,
                    'current_url' => $visitor->current_url,
                    'page_title' => $visitor->page_title,
                    'browser' => $this->browser($visitor->user_agent),
                    'operating_system' => $this->operatingSystem($visitor->user_agent),
                    'device' => $this->device($visitor->user_agent),
                    'session_seconds' => max(0, ($visitor->session_started_at ?: $visitor->created_at)?->diffInSeconds(now()) ?? 0),
                    'page_views_count' => $visitor->page_views_count,
                    'messages_count' => $visitor->messages_count,
                    'last_seen_at' => optional($visitor->last_seen_at)->toIso8601String(),
                    'unread_messages_count' => $visitor->unread_messages_count,
                    'conversation_started' => (bool) $visitor->conversation_started_at,
                ];
            }),
        ]);
    }

    public function show(WebsiteVisitor $visitor)
    {
        $visitor->load('user:id,name,phone,email');

        $pageViews = $visitor->pageViews()
            ->latest('visited_at')
            ->limit(50)
            ->get();

        $messages = $visitor->messages()
            ->oldest('id')
            ->limit(200)
            ->get();

        $visitor->messages()
            ->where('sender', 'visitor')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $source = 'دخول مباشر';
        if ($visitor->referrer) {
            $source = parse_url($visitor->referrer, PHP_URL_HOST) ?: $visitor->referrer;
        }

        return response()->json([
            'visitor' => [
                'id' => $visitor->id,
                'label' => $visitor->user?->name ?: 'زائر #' . str_pad((string) $visitor->id, 4, '0', STR_PAD_LEFT),
                'online' => $visitor->last_seen_at && $visitor->last_seen_at->gte(now()->subSeconds(70)),
                'name' => $visitor->user?->name,
                'phone' => $visitor->user?->phone,
                'email' => $visitor->user?->email,
                'registered_client' => (bool) $visitor->user_id,
                'ip_address' => $visitor->ip_address,
                'browser' => $this->browser($visitor->user_agent),
                'operating_system' => $this->operatingSystem($visitor->user_agent),
                'device' => $this->device($visitor->user_agent),
                'screen' => $visitor->screen_width && $visitor->screen_height
                    ? $visitor->screen_width . ' × ' . $visitor->screen_height
                    : 'غير معروف',
                'timezone' => $visitor->timezone ?: 'غير معروف',
                'locale' => $visitor->locale ?: '-',
                'source' => $source,
                'referrer' => $visitor->referrer,
                'landing_url' => $visitor->landing_url,
                'current_url' => $visitor->current_url,
                'page_title' => $visitor->page_title,
                'started_at' => optional($visitor->session_started_at ?: $visitor->created_at)->toIso8601String(),
                'last_seen_at' => optional($visitor->last_seen_at)->toIso8601String(),
                'session_seconds' => max(0, ($visitor->session_started_at ?: $visitor->created_at)?->diffInSeconds(now()) ?? 0),
                'page_views_count' => $visitor->pageViews()->count(),
                'messages_count' => $visitor->messages()->count(),
                'lead_score' => $this->leadScore($visitor),
            ],
            'page_views' => $pageViews->map(fn ($view) => [
                'id' => $view->id,
                'url' => $view->url,
                'path' => parse_url($view->url, PHP_URL_PATH) ?: '/',
                'title' => $view->title,
                'visited_at' => optional($view->visited_at)->toIso8601String(),
            ]),
            'messages' => $messages->map(fn ($message) => [
                'id' => $message->id,
                'sender' => $message->sender,
                'body' => $message->body,
                'created_at' => optional($message->created_at)->toIso8601String(),
            ]),
        ]);
    }

    public function messages(WebsiteVisitor $visitor)
    {
        $messages = $visitor->messages()
            ->oldest('id')
            ->limit(200)
            ->get();

        $visitor->messages()
            ->where('sender', 'visitor')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'visitor' => [
                'id' => $visitor->id,
                'label' => $visitor->user?->name ?: 'زائر #' . str_pad((string) $visitor->id, 4, '0', STR_PAD_LEFT),
                'online' => $visitor->last_seen_at && $visitor->last_seen_at->gte(now()->subSeconds(70)),
            ],
            'messages' => $messages->map(fn ($message) => [
                'id' => $message->id,
                'sender' => $message->sender,
                'body' => $message->body,
                'created_at' => optional($message->created_at)->toIso8601String(),
            ]),
        ]);
    }

    public function send(Request $request, WebsiteVisitor $visitor)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $visitor->update([
            'conversation_started_at' => $visitor->conversation_started_at ?: now(),
        ]);

        $message = $visitor->messages()->create([
            'sender' => 'admin',
            'admin_id' => auth('admin')->id(),
            'body' => trim($validated['body']),
        ]);

        return response()->json([
            'message' => [
                'id' => $message->id,
                'sender' => $message->sender,
                'body' => $message->body,
                'created_at' => optional($message->created_at)->toIso8601String(),
            ],
        ], 201);
    }

    private function browser(?string $userAgent): string
    {
        $userAgent = strtolower((string) $userAgent);

        return match (true) {
            str_contains($userAgent, 'edg/') => 'Microsoft Edge',
            str_contains($userAgent, 'opr/') || str_contains($userAgent, 'opera') => 'Opera',
            str_contains($userAgent, 'chrome/') => 'Google Chrome',
            str_contains($userAgent, 'firefox/') => 'Mozilla Firefox',
            str_contains($userAgent, 'safari/') => 'Safari',
            default => 'متصفح غير معروف',
        };
    }

    private function operatingSystem(?string $userAgent): string
    {
        $userAgent = strtolower((string) $userAgent);

        return match (true) {
            str_contains($userAgent, 'windows') => 'Windows',
            str_contains($userAgent, 'android') => 'Android',
            str_contains($userAgent, 'iphone') || str_contains($userAgent, 'ipad') => 'iOS',
            str_contains($userAgent, 'mac os') || str_contains($userAgent, 'macintosh') => 'macOS',
            str_contains($userAgent, 'linux') => 'Linux',
            default => 'نظام غير معروف',
        };
    }

    private function device(?string $userAgent): string
    {
        $userAgent = strtolower((string) $userAgent);

        return match (true) {
            str_contains($userAgent, 'ipad') || str_contains($userAgent, 'tablet') => 'جهاز لوحي',
            str_contains($userAgent, 'mobile') || str_contains($userAgent, 'iphone') || str_contains($userAgent, 'android') => 'هاتف',
            default => 'كمبيوتر',
        };
    }

    private function leadScore(WebsiteVisitor $visitor): int
    {
        $pageViews = $visitor->pageViews()->count();
        $messages = $visitor->messages()->count();
        $startedAt = $visitor->session_started_at ?: $visitor->created_at;
        $minutes = min(20, (int) floor(max(0, $startedAt?->diffInSeconds(now()) ?? 0) / 60));

        return min(100, ($pageViews * 8) + ($messages * 12) + ($minutes * 2));
    }
}
