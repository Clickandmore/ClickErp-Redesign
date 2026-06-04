<?php

namespace App\Http\Controllers\admin;

use App\Services\ZohoSalesIq\ZohoSalesIqService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ZohoSalesIqController extends Controller
{
    public function visitors(Request $request, ZohoSalesIqService $salesIq)
    {
        $filters = $this->visitorFilters($request);
        $result = $salesIq->visitors($filters['api_query']);

        $visitors = collect($result['items'] ?? [])
            ->map(fn (array $item) => $this->normalizeVisitor($item))
            ->values();

        return view('admin.zoho_salesiq.visitors', [
            'filters' => $filters,
            'result' => $result,
            'visitors' => $visitors,
            'missingConfig' => $salesIq->missingConfigKeys(),
            'isConfigured' => $salesIq->isConfigured(),
        ]);
    }

    public function conversations(Request $request, ZohoSalesIqService $salesIq)
    {
        // Check for scope errors
        $testResult = $salesIq->conversations(['limit' => 1]);
        if (!$testResult['ok'] && str_contains(($testResult['error'] ?? ''), 'Invalid OAuthScope')) {
            return view('admin.zoho_salesiq.no_access');
        }

        $filters = $this->conversationFilters($request);
        $result = $salesIq->conversations($filters['api_query']);

        $conversations = collect($result['items'] ?? [])
            ->map(fn (array $item) => $this->normalizeConversation($item))
            ->values();

        return view('admin.zoho_salesiq.conversations', [
            'filters' => $filters,
            'result' => $result,
            'conversations' => $conversations,
            'missingConfig' => $salesIq->missingConfigKeys(),
            'isConfigured' => $salesIq->isConfigured(),
        ]);
    }

    public function conversation(Request $request, ZohoSalesIqService $salesIq, string $conversationId)
    {
        $detailResult = $salesIq->conversationDetails($conversationId);

        $result = $salesIq->conversationMessages($conversationId, [
            'limit' => $this->limit($request, 100, 200),
        ]);

        $messages = collect($result['items'] ?? [])
            ->map(fn (array $item) => $this->normalizeMessage($item))
            ->values();

        if (!$this->payloadHasOverviewData($detailResult['payload'] ?? [])) {
            $detailResult = $this->conversationFromRecentList($salesIq, $conversationId, $detailResult);
        }

        return view('admin.zoho_salesiq.conversation', [
            'conversationId' => $conversationId,
            'conversation' => $this->conversationOverview($conversationId, $detailResult, $result, $messages),
            'messages' => $messages,
            'result' => $result,
            'missingConfig' => $salesIq->missingConfigKeys(),
            'isConfigured' => $salesIq->isConfigured(),
        ]);
    }

    private function visitorFilters(Request $request): array
    {
        $from = $this->dateInput($request->input('from'), now()->subDay());
        $limit = $this->limit($request, 50, 100);

        // Build query with only basic parameters
        $query = ['limit' => $limit];
        
        // Add next_key if provided
        if ($request->input('next_key')) {
            $query['next_key'] = $request->input('next_key');
        }

        return [
            'from' => $from->format('Y-m-d\TH:i'),
            'limit' => $limit,
            'next_key' => $request->input('next_key'),
            'api_query' => $query,
        ];
    }

    private function conversationFilters(Request $request): array
    {
        $from = $this->dateInput($request->input('from'), now()->subDays(7)->startOfDay());
        $to = $this->dateInput($request->input('to'), now());
        $limit = $this->limit($request, 50, 200);

        return [
            'from' => $from->format('Y-m-d\TH:i'),
            'to' => $to->format('Y-m-d\TH:i'),
            'limit' => $limit,
            'api_query' => [
                'limit' => $limit,
                'fromtime' => $this->milliseconds($from),
                'totime' => $this->milliseconds($to),
            ],
        ];
    }

    private function conversationFromRecentList(ZohoSalesIqService $salesIq, string $conversationId, array $currentResult): array
    {
        $recentResult = $salesIq->conversations(['limit' => 200]);

        if (!$recentResult['ok']) {
            return $currentResult;
        }

        $matched = collect($recentResult['items'] ?? [])
            ->first(function ($item) use ($conversationId) {
                if (!is_array($item)) {
                    return false;
                }

                $id = $this->text($this->first($item, ['id', 'conversation_id', 'chat_id', 'conversation.id'], ''), '');

                return $id !== '' && $id === $conversationId;
            });

        if (!is_array($matched)) {
            return $currentResult;
        }

        return [
            'ok' => true,
            'status_code' => $recentResult['status_code'] ?? null,
            'error' => null,
            'missing' => [],
            'items' => [],
            'payload' => ['conversation' => $matched],
            'meta' => $recentResult['meta'] ?? [],
        ];
    }

    private function conversationOverview(string $conversationId, array $detailResult, array $messagesResult, $messages): array
    {
        $detailPayload = is_array($detailResult['payload'] ?? null) ? $detailResult['payload'] : [];
        $messagesPayload = is_array($messagesResult['payload'] ?? null) ? $messagesResult['payload'] : [];
        $conversationPayload = $this->conversationPayload($detailPayload);

        if ($conversationPayload === [] && $this->payloadHasOverviewData($messagesPayload)) {
            $conversationPayload = $this->conversationPayload($messagesPayload);
        }

        $normalized = $conversationPayload === [] ? [] : $this->normalizeConversation($conversationPayload);
        $lastMessage = $messages->last() ?: [];
        $payloads = array_values(array_filter(
            [$conversationPayload, $detailPayload, $messagesPayload],
            static fn ($payload) => is_array($payload) && $payload !== []
        ));
        $visitor = $this->overviewText($normalized, 'visitor', $payloads, [
            'visitor.name',
            'visitor_name',
            'customer.name',
            'contact.name',
            'lead.name',
            'data.visitor.name',
            'data.customer.name',
            'data.contact.name',
            'data.conversation.visitor.name',
            'conversation.visitor.name',
            'chat.visitor.name',
        ], '');
        $operator = $this->overviewText($normalized, 'operator', $payloads, [
            'attender.name',
            'operator.name',
            'operator',
            'agent.name',
            'owner.name',
            'assigned_to.name',
            'user.name',
            'data.attender.name',
            'data.operator.name',
            'data.agent.name',
            'data.owner.name',
            'data.conversation.attender.name',
            'data.conversation.operator.name',
            'conversation.attender.name',
            'conversation.operator.name',
            'chat.attender.name',
            'chat.operator.name',
        ], '');
        $lastActivity = $lastMessage['time'] ?? '';

        if (!$this->isUsefulText($visitor)) {
            $visitor = $this->participantName($messages, 'visitor');
        }

        if (!$this->isUsefulText($operator)) {
            $operator = $this->participantName($messages, 'operator');
        }

        if (!$this->isUsefulText($lastActivity)) {
            $lastActivity = $this->overviewDate($normalized, 'created_at', $payloads, [
                'updated_time',
                'modified_time',
                'last_active_time',
                'last_message_time',
                'data.updated_time',
                'data.modified_time',
                'data.last_active_time',
                'data.conversation.updated_time',
                'conversation.updated_time',
            ]);
        }

        return [
            'id' => $conversationId,
            'status' => $this->overviewText($normalized, 'status', $payloads, [
                'status',
                'state',
                'conversation.status',
                'chat.status',
                'data.status',
                'data.state',
                'data.conversation.status',
                'data.chat.status',
            ]),
            'visitor' => $this->isUsefulText($visitor) ? $visitor : '-',
            'email' => $this->overviewText($normalized, 'email', $payloads, [
                'visitor.email',
                'visitor_email',
                'email',
                'customer.email',
                'contact.email',
                'lead.email',
                'data.visitor.email',
                'data.customer.email',
                'data.contact.email',
                'data.conversation.visitor.email',
                'conversation.visitor.email',
                'chat.visitor.email',
            ]),
            'phone' => $this->overviewText($normalized, 'phone', $payloads, [
                'visitor.phone',
                'phone',
                'mobile',
                'customer.phone',
                'contact.phone',
                'lead.phone',
                'data.visitor.phone',
                'data.customer.phone',
                'data.contact.phone',
                'data.conversation.visitor.phone',
                'conversation.visitor.phone',
                'chat.visitor.phone',
            ]),
            'operator' => $this->isUsefulText($operator) ? $operator : '-',
            'department' => $this->overviewText($normalized, 'department', $payloads, [
                'department.name',
                'department',
                'department_name',
                'attender.department.name',
                'operator.department.name',
                'data.department.name',
                'data.department',
                'data.conversation.department.name',
                'conversation.department.name',
                'chat.department.name',
            ]),
            'created_at' => $this->overviewDate($normalized, 'created_at', $payloads, [
                'created_time',
                'created_at',
                'start_time',
                'time',
                'data.created_time',
                'data.created_at',
                'data.start_time',
                'data.time',
                'data.conversation.created_time',
                'data.conversation.created_at',
                'conversation.created_time',
                'conversation.created_at',
                'chat.created_time',
                'chat.created_at',
            ]),
            'closed_at' => $this->overviewDate($normalized, 'closed_at', $payloads, [
                'closed_time',
                'ended_time',
                'end_time',
                'closed_at',
                'data.closed_time',
                'data.ended_time',
                'data.end_time',
                'data.closed_at',
                'data.conversation.closed_time',
                'data.conversation.ended_time',
                'conversation.closed_time',
                'conversation.ended_time',
                'chat.closed_time',
                'chat.ended_time',
            ]),
            'last_activity' => $this->isUsefulText($lastActivity) ? $lastActivity : '-',
            'message_count' => $messages->count(),
            'raw' => $this->json($detailPayload !== [] ? $detailPayload : $messagesPayload),
        ];
    }

    private function conversationPayload(array $payload): array
    {
        foreach (['data.conversation', 'conversation', 'data.chat', 'chat', 'data.details', 'details', 'data'] as $key) {
            $value = data_get($payload, $key);

            if (is_array($value) && !array_is_list($value)) {
                return $value;
            }
        }

        return [];
    }

    private function payloadHasOverviewData($payload): bool
    {
        if (!is_array($payload) || $payload === []) {
            return false;
        }

        $conversationPayload = $this->conversationPayload($payload);
        $payloads = array_filter([$conversationPayload, $payload], static fn ($item) => is_array($item) && $item !== []);

        foreach ($payloads as $item) {
            foreach ([
                'visitor.name',
                'visitor.email',
                'visitor.phone',
                'visitor_name',
                'customer.name',
                'attender.name',
                'operator.name',
                'department.name',
                'status',
                'state',
                'data.visitor.name',
                'data.conversation.visitor.name',
                'data.attender.name',
                'data.operator.name',
                'data.department.name',
                'data.conversation.status',
            ] as $key) {
                if (filled(data_get($item, $key))) {
                    return true;
                }
            }
        }

        return false;
    }

    private function overviewText(array $normalized, string $normalizedKey, array $payloads, array $keys, string $default = '-'): string
    {
        if ($this->isUsefulText($normalized[$normalizedKey] ?? null)) {
            return $normalized[$normalizedKey];
        }

        foreach ($payloads as $payload) {
            $text = $this->text($this->first($payload, $keys, null), '');

            if ($this->isUsefulText($text)) {
                return $text;
            }
        }

        return $default;
    }

    private function overviewDate(array $normalized, string $normalizedKey, array $payloads, array $keys): string
    {
        if ($this->isUsefulText($normalized[$normalizedKey] ?? null)) {
            return $normalized[$normalizedKey];
        }

        foreach ($payloads as $payload) {
            $date = $this->dateLabel($this->first($payload, $keys, null));

            if ($this->isUsefulText($date)) {
                return $date;
            }
        }

        return '-';
    }

    private function participantName($messages, string $role): string
    {
        $message = $messages->first(fn ($message) => ($message['role'] ?? null) === $role
            && $this->isUsefulText($message['sender'] ?? null));

        return $message['sender'] ?? '';
    }

    private function isUsefulText($value): bool
    {
        if ($value === null || is_array($value)) {
            return false;
        }

        if (is_object($value) && !($value instanceof \Stringable)) {
            return false;
        }

        $value = trim((string) $value);

        return $value !== '' && $value !== '-';
    }

    private function normalizeVisitor(array $item): array
    {
        return [
            'id' => $this->text($this->first($item, ['id', 'visitor_id', 'visitor.id', 'uvid', 'cuid'], '-')),
            'name' => $this->text($this->first($item, ['name', 'visitor_name', 'visitor.name', 'display_name', 'info.name'], 'زائر غير معروف'), 'زائر غير معروف'),
            'email' => $this->text($this->first($item, ['email', 'visitor.email', 'info.email'], '-')),
            'phone' => $this->text($this->first($item, ['phone', 'mobile', 'visitor.phone', 'info.phone'], '-')),
            'status' => $this->text($this->first($item, ['status', 'state', 'visitor.status'], '-')),
            'location' => $this->location($item),
            'ip' => $this->text($this->first($item, ['ip', 'ip_address', 'visitor.ip'], '-')),
            'source' => $this->text($this->first($item, ['source', 'referrer', 'campaign.source', 'utm_source'], '-')),
            'page' => $this->text($this->first($item, ['page', 'current_page', 'last_page', 'visited_page.title', 'visited_page.url'], '-')),
            'last_seen' => $this->dateLabel($this->first($item, ['last_seen', 'last_visit_time', 'last_active_time', 'updated_time', 'modified_time', 'time'], null)),
            'raw' => $this->json($item),
        ];
    }

    private function normalizeConversation(array $item): array
    {
        return [
            'id' => $this->text($this->first($item, ['id', 'conversation_id', 'chat_id', 'conversation.id'], '-')),
            'visitor' => $this->text($this->first($item, ['visitor.name', 'visitor_name', 'name', 'customer.name'], 'زائر غير معروف'), 'زائر غير معروف'),
            'email' => $this->text($this->first($item, ['visitor.email', 'visitor_email', 'email', 'customer.email'], '-')),
            'phone' => $this->text($this->first($item, ['visitor.phone', 'phone', 'customer.phone'], '-')),
            'status' => $this->text($this->first($item, ['status', 'state', 'conversation.status'], '-')),
            'operator' => $this->text($this->first($item, ['attender.name', 'operator.name', 'operator', 'owner.name', 'assigned_to.name'], '-')),
            'department' => $this->text($this->first($item, ['department.name', 'department', 'department_name'], '-')),
            'last_message' => Str::limit(strip_tags($this->text($this->first($item, ['last_message', 'message', 'question', 'summary', 'subject'], '-'))), 120),
            'created_at' => $this->dateLabel($this->first($item, ['created_time', 'created_at', 'start_time', 'time'], null)),
            'closed_at' => $this->dateLabel($this->first($item, ['closed_time', 'ended_time', 'end_time'], null)),
            'raw' => $this->json($item),
        ];
    }

    private function normalizeMessage(array $item): array
    {
        $sender = $this->text($this->first($item, ['sender.name', 'from.name', 'operator.name', 'visitor.name', 'sender', 'from'], '-'));
        $type = $this->text($this->first($item, ['sender.type', 'from.type', 'type', 'message_type'], '-'));

        return [
            'sender' => $sender,
            'type' => $type,
            'role' => $this->messageRole($item, $sender, $type),
            'message' => strip_tags($this->text($this->first($item, ['message', 'text', 'content', 'body', 'msg'], ''), '')),
            'time' => $this->dateLabel($this->first($item, ['time', 'created_time', 'created_at', 'sent_time'], null)),
            'raw' => $this->json($item),
        ];
    }

    private function first(array $item, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            $value = data_get($item, $key);

            if (filled($value)) {
                return $value;
            }
        }

        return $default;
    }

    private function location(array $item): string
    {
        $parts = array_filter([
            $this->text($this->first($item, ['city', 'location.city', 'visitor.city'], null), ''),
            $this->text($this->first($item, ['region', 'location.region', 'state'], null), ''),
            $this->text($this->first($item, ['country', 'location.country', 'visitor.country'], null), ''),
        ]);

        return $parts === [] ? '-' : implode(', ', array_unique($parts));
    }

    private function text($value, string $default = '-'): string
    {
        if ($value === null) {
            return $default;
        }

        if ($value instanceof \Stringable) {
            $value = (string) $value;
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            $value = trim((string) $value);

            return $value === '' ? $default : $value;
        }

        if (is_array($value)) {
            foreach ([
                'text',
                'message',
                'content',
                'body',
                'msg',
                'value',
                'label',
                'name',
                'title',
                'id',
                'email',
                'phone',
                'status',
                'state',
                'type',
                'url',
            ] as $key) {
                $nested = data_get($value, $key);

                if (filled($nested)) {
                    $text = $this->text($nested, '');

                    if ($text !== '') {
                        return $text;
                    }
                }
            }

            $flattened = collect($value)
                ->flatten()
                ->filter(static fn ($part) => is_scalar($part) && trim((string) $part) !== '')
                ->map(static fn ($part) => is_bool($part) ? ($part ? 'true' : 'false') : trim((string) $part))
                ->implode(' ');

            return $flattened === '' ? $default : $flattened;
        }

        return $default;
    }

    private function messageRole(array $item, string $sender, string $type): string
    {
        $roleText = Str::lower(implode(' ', [
            $sender,
            $type,
            $this->text($this->first($item, ['sender.type', 'from.type', 'user_type', 'actor.type', 'source'], ''), ''),
        ]));

        if (Str::contains($roleText, ['visitor', 'customer', 'client', 'guest', 'lead'])) {
            return 'visitor';
        }

        if (Str::contains($roleText, ['system', 'bot', 'trigger', 'auto', 'notification'])) {
            return 'system';
        }

        return 'operator';
    }

    private function dateInput($value, Carbon $default): Carbon
    {
        if (blank($value)) {
            return $default->copy();
        }

        try {
            return Carbon::parse($value, config('app.timezone'));
        } catch (\Throwable $exception) {
            return $default->copy();
        }
    }

    private function dateLabel($value): string
    {
        $value = $this->dateValue($value);

        if (blank($value)) {
            return '-';
        }

        try {
            if (is_numeric($value)) {
                $timestamp = (int) $value;
                $timestamp = $timestamp > 9999999999 ? (int) floor($timestamp / 1000) : $timestamp;

                return Carbon::createFromTimestamp($timestamp)
                    ->timezone(config('app.timezone'))
                    ->format('Y-m-d H:i');
            }

            return Carbon::parse((string) $value)
                ->timezone(config('app.timezone'))
                ->format('Y-m-d H:i');
        } catch (\Throwable $exception) {
            return $this->text($value);
        }
    }

    private function dateValue($value)
    {
        if ($value instanceof \Stringable || is_scalar($value) || $value === null) {
            return $value;
        }

        if (is_array($value)) {
            foreach ([
                'timestamp',
                'time',
                'date',
                'datetime',
                'value',
                'created_time',
                'created_at',
                'updated_time',
                'sent_time',
            ] as $key) {
                $nested = data_get($value, $key);

                if (filled($nested)) {
                    return $this->dateValue($nested);
                }
            }
        }

        return null;
    }

    private function milliseconds(Carbon $date): int
    {
        return $date->copy()->utc()->getTimestamp() * 1000;
    }

    private function limit(Request $request, int $default, int $max): int
    {
        return min($max, max(1, (int) $request->input('limit', $default)));
    }

    private function json(array $item): string
    {
        return json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}';
    }
}
