<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\WebsiteVisitor;
use App\Models\WebsiteVisitorSite;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WebsiteVisitorChatController extends Controller
{
    private const ONLINE_SECONDS = 150;

    public function index()
    {
        $sites = WebsiteVisitorSite::query()
            ->withCount('visitors')
            ->latest()
            ->get();

        return view('admin.ingaz.website_visitors.index', compact('sites'));
    }

    public function storeSite(Request $request)
    {
        $site = WebsiteVisitorSite::create($this->siteData($request));

        return redirect()
            ->route('website-visitors.index', ['tab' => 'tracking'])
            ->with('success', 'تم إنشاء كود التتبع لموقع ' . $site->name);
    }

    public function updateSite(Request $request, WebsiteVisitorSite $site)
    {
        $site->update($this->siteData($request));

        return redirect()
            ->route('website-visitors.index', ['tab' => 'tracking'])
            ->with('success', 'تم تحديث إعدادات كود التتبع.');
    }

    public function destroySite(WebsiteVisitorSite $site)
    {
        $site->delete();

        return redirect()
            ->route('website-visitors.index', ['tab' => 'tracking'])
            ->with('success', 'تم حذف كود التتبع. بقي سجل الزيارات محفوظًا.');
    }

    public function data()
    {
        $onlineSince = now()->subSeconds(self::ONLINE_SECONDS);

        $visitors = WebsiteVisitor::query()
            ->with(['user:id,name,phone,email', 'site:id,name'])
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
                    'site_name' => $visitor->site?->name ?: 'موقع ClickERP',
                    'chat_available' => (bool) $visitor->website_visitor_site_id,
                ];
            }),
        ]);
    }

    public function history(Request $request)
    {
        $validated = $request->validate([
            'days' => ['nullable', 'integer', 'in:1,7,30,90'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $days = (int) ($validated['days'] ?? 30);
        $search = trim((string) ($validated['search'] ?? ''));

        $visitors = WebsiteVisitor::query()
            ->with(['user:id,name,phone,email', 'site:id,name'])
            ->withCount(['pageViews', 'messages'])
            ->where('last_seen_at', '>=', now()->subDays($days))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('id', $search)
                        ->orWhere('ip_address', 'like', '%' . $search . '%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%'));
                });
            })
            ->latest('last_seen_at')
            ->limit(300)
            ->get();

        return response()->json([
            'total' => $visitors->count(),
            'visitors' => $visitors->map(fn (WebsiteVisitor $visitor) => $this->historyPayload($visitor)),
        ]);
    }

    public function conversations(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim((string) ($validated['search'] ?? ''));

        $visitors = WebsiteVisitor::query()
            ->with(['user:id,name,phone,email', 'site:id,name', 'latestMessage'])
            ->withCount([
                'messages',
                'messages as unread_messages_count' => fn ($query) => $query
                    ->where('sender', 'visitor')
                    ->whereNull('read_at'),
            ])
            ->whereHas('messages')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('id', $search)
                        ->orWhereHas('user', fn ($userQuery) => $userQuery
                            ->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%'));
                });
            })
            ->orderByDesc(
                \App\Models\WebsiteVisitorMessage::select('created_at')
                    ->whereColumn('website_visitor_id', 'website_visitors.id')
                    ->latest('created_at')
                    ->limit(1)
            )
            ->limit(300)
            ->get();

        return response()->json([
            'total' => $visitors->count(),
            'conversations' => $visitors->map(function (WebsiteVisitor $visitor) {
                return [
                    'id' => $visitor->id,
                    'label' => $this->label($visitor),
                    'registered_client' => (bool) $visitor->user_id,
                    'phone' => $visitor->user?->phone,
                    'messages_count' => $visitor->messages_count,
                    'unread_messages_count' => $visitor->unread_messages_count,
                    'last_message' => $visitor->latestMessage?->body,
                    'last_sender' => $visitor->latestMessage?->sender,
                    'last_message_at' => optional($visitor->latestMessage?->created_at)->toIso8601String(),
                    'online' => $visitor->last_seen_at && $visitor->last_seen_at->gte(now()->subSeconds(self::ONLINE_SECONDS)),
                    'site_name' => $visitor->site?->name ?: 'موقع ClickERP',
                ];
            }),
        ]);
    }

    public function show(WebsiteVisitor $visitor)
    {
        $visitor->load(['user:id,name,phone,email', 'site:id,name']);

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
                'online' => $visitor->last_seen_at && $visitor->last_seen_at->gte(now()->subSeconds(self::ONLINE_SECONDS)),
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
                'site_name' => $visitor->site?->name ?: 'موقع ClickERP',
                'chat_available' => (bool) $visitor->website_visitor_site_id,
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
                'online' => $visitor->last_seen_at && $visitor->last_seen_at->gte(now()->subSeconds(self::ONLINE_SECONDS)),
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

    private function label(WebsiteVisitor $visitor): string
    {
        return $visitor->user?->name ?: 'زائر #' . str_pad((string) $visitor->id, 4, '0', STR_PAD_LEFT);
    }

    private function historyPayload(WebsiteVisitor $visitor): array
    {
        return [
            'id' => $visitor->id,
            'label' => $this->label($visitor),
            'registered_client' => (bool) $visitor->user_id,
            'phone' => $visitor->user?->phone,
            'ip_address' => $visitor->ip_address,
            'browser' => $this->browser($visitor->user_agent),
            'operating_system' => $this->operatingSystem($visitor->user_agent),
            'device' => $this->device($visitor->user_agent),
            'source' => $visitor->referrer
                ? (parse_url($visitor->referrer, PHP_URL_HOST) ?: $visitor->referrer)
                : 'دخول مباشر',
            'landing_url' => $visitor->landing_url,
            'current_url' => $visitor->current_url,
            'page_views_count' => $visitor->page_views_count,
            'messages_count' => $visitor->messages_count,
            'first_seen_at' => optional($visitor->created_at)->toIso8601String(),
            'last_seen_at' => optional($visitor->last_seen_at)->toIso8601String(),
            'online' => $visitor->last_seen_at && $visitor->last_seen_at->gte(now()->subSeconds(self::ONLINE_SECONDS)),
            'site_name' => $visitor->site?->name ?: 'موقع ClickERP',
            'chat_available' => (bool) $visitor->website_visitor_site_id,
        ];
    }

    private function siteData(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'allowed_domains' => ['required', 'string', 'max:4000'],
            'widget_title' => ['required', 'string', 'max:120'],
            'welcome_message' => ['nullable', 'string', 'max:1000'],
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'position' => ['required', Rule::in(['right', 'left'])],
            'launcher_mode' => ['required', Rule::in(['always', 'trigger'])],
            'trigger_selector' => ['nullable', 'string', 'max:255', 'required_if:launcher_mode,trigger'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $domains = collect(preg_split('/[\s,]+/', $validated['allowed_domains']))
            ->map(function ($domain) {
                $domain = strtolower(trim((string) $domain));
                if ($domain === '*') {
                    return null;
                }
                $wildcard = str_starts_with($domain, '*.');
                $candidate = $wildcard ? substr($domain, 2) : $domain;
                if (!str_contains($candidate, '://')) {
                    $candidate = 'https://' . $candidate;
                }
                $host = parse_url($candidate, PHP_URL_HOST);

                return $host ? ($wildcard ? '*.' . strtolower($host) : strtolower($host)) : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($domains === []) {
            abort(422, 'يجب إدخال نطاق صريح واحد على الأقل؛ السماح المفتوح * غير مسموح.');
        }

        return [
            'name' => $validated['name'],
            'allowed_domains' => $domains,
            'widget_title' => $validated['widget_title'],
            'welcome_message' => $validated['welcome_message'] ?? null,
            'primary_color' => strtolower($validated['primary_color']),
            'position' => $validated['position'],
            'launcher_mode' => $validated['launcher_mode'],
            'trigger_selector' => $validated['launcher_mode'] === 'trigger'
                ? trim($validated['trigger_selector'])
                : null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
