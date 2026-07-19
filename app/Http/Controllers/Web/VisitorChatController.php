<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WebsiteVisitor;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VisitorChatController extends Controller
{
    public function heartbeat(Request $request)
    {
        $validated = $request->validate([
            'current_url' => ['nullable', 'string', 'max:2048'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:2048'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'screen_width' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'screen_height' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $visitor = $this->visitor($request);
        $currentUrl = $validated['current_url'] ?? url()->current();
        $latestPageView = $visitor->pageViews()->latest('visited_at')->first();

        if (!$latestPageView || $latestPageView->url !== $currentUrl) {
            $visitor->pageViews()->create([
                'url' => $currentUrl,
                'title' => $validated['page_title'] ?? null,
                'referrer' => $validated['referrer'] ?? null,
                'visited_at' => now(),
            ]);
        } elseif (!empty($validated['page_title']) && !$latestPageView->title) {
            $latestPageView->update([
                'title' => $validated['page_title'],
                'referrer' => $latestPageView->referrer ?: ($validated['referrer'] ?? null),
            ]);
        }

        $sessionStartedAt = $visitor->session_started_at;
        if (!$sessionStartedAt || !$visitor->last_seen_at || $visitor->last_seen_at->lt(now()->subMinutes(10))) {
            $sessionStartedAt = now();
        }

        $visitor->update([
            'user_id' => auth('web')->id(),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 2000, ''),
            'current_url' => $currentUrl,
            'landing_url' => $visitor->landing_url ?: $currentUrl,
            'referrer' => $visitor->referrer ?: ($validated['referrer'] ?? null),
            'page_title' => $validated['page_title'] ?? null,
            'locale' => app()->getLocale(),
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

    public function messages(Request $request)
    {
        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $visitor = $this->visitor($request);
        $visitor->update(['last_seen_at' => now()]);

        $messages = $visitor->messages()
            ->when(!empty($validated['after_id']), function ($query) use ($validated) {
                $query->where('id', '>', $validated['after_id']);
            })
            ->latest('id')
            ->limit(100)
            ->get()
            ->sortBy('id')
            ->values();

        $visitor->messages()
            ->where('sender', 'admin')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'conversation_started' => (bool) $visitor->conversation_started_at,
            'messages' => $messages->map(fn ($message) => $this->messagePayload($message)),
        ]);
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $visitor = $this->visitor($request);
        $visitor->update([
            'last_seen_at' => now(),
            'conversation_started_at' => $visitor->conversation_started_at ?: now(),
        ]);

        $message = $visitor->messages()->create([
            'sender' => 'visitor',
            'body' => trim($validated['body']),
        ]);

        return response()->json([
            'message' => $this->messagePayload($message),
        ], 201);
    }

    private function visitor(Request $request): WebsiteVisitor
    {
        $token = $request->session()->get('website_visitor_token');

        if (!$token) {
            $token = (string) Str::uuid();
            $request->session()->put('website_visitor_token', $token);
        }

        return WebsiteVisitor::firstOrCreate(
            ['token' => $token],
            [
                'user_id' => auth('web')->id(),
                'ip_address' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 2000, ''),
                'current_url' => url()->current(),
                'locale' => app()->getLocale(),
                'last_seen_at' => now(),
                'session_started_at' => now(),
            ]
        );
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
