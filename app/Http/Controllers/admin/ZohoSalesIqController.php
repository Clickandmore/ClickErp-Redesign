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
        $result = $salesIq->conversationMessages($conversationId, [
            'limit' => $this->limit($request, 100, 200),
        ]);

        $messages = collect($result['items'] ?? [])
            ->map(fn (array $item) => $this->normalizeMessage($item))
            ->values();

        return view('admin.zoho_salesiq.conversation', [
            'conversationId' => $conversationId,
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
        return [
            'sender' => $this->text($this->first($item, ['sender.name', 'from.name', 'operator.name', 'visitor.name', 'sender', 'from'], '-')),
            'type' => $this->text($this->first($item, ['sender.type', 'from.type', 'type', 'message_type'], '-')),
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
