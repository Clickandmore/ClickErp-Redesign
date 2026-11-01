<?php

namespace App\Mcp\Tools;

use App\Models\auth\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * سجل الأنشطة (spatie activity_log): من فعل ماذا ومتى. شاشة dash.activity.log
 * نفسها لا تُقيَّد بالفرع، فالأداة كذلك.
 */
class ActivityLogTool extends McpTool
{
    /** حقول لا تُرسل قيمها للمساعد أبداً. */
    private const HIDDEN_FIELDS = ['password', 'remember_token', 'token', 'current_session_id', 'verification_code', 'reset_password_token', 'api_key', 'secret'];

    public function name(): string
    {
        return 'activity_log';
    }

    public function description(): string
    {
        return 'سجل الأنشطة في النظام: من أضاف أو عدّل أو حذف ماذا ومتى، مع ملخص حسب الموظف ونوع العملية والقسم. '
            . 'Activity log: who created/updated/deleted what and when, with summaries by employee, event and log.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'from' => ['type' => 'string', 'format' => 'date', 'description' => 'من تاريخ (Y-m-d)'],
                'to' => ['type' => 'string', 'format' => 'date', 'description' => 'إلى تاريخ (Y-m-d)'],
                'admin' => ['type' => 'string', 'description' => 'اسم الموظف أو بريده أو رقمه'],
                'event' => ['type' => 'string', 'description' => 'created, updated, deleted, login, logout, impersonated...'],
                'log_name' => ['type' => 'string', 'description' => 'القسم: default, finance, authentication, permissions, settings...'],
                'subject' => ['type' => 'string', 'description' => 'نوع السجل المتأثر، مثل Contract أو Maid أو User أو Account'],
                'subject_id' => ['type' => 'integer'],
                'query' => ['type' => 'string', 'description' => 'نص في وصف النشاط'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20],
                'offset' => $this->offsetProperty(),
            ],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'dash.activity.log';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $query = DB::table('activity_log');

        foreach (['from' => '>=', 'to' => '<='] as $key => $operator) {
            if (!empty($arguments[$key])) {
                $query->whereDate('created_at', $operator, $arguments[$key]);
            }
        }

        if (!empty($arguments['admin'])) {
            $value = (string) $arguments['admin'];
            $adminIds = Admin::withTrashed()
                ->where(fn ($q) => $q->where('name', 'like', "%{$value}%")->orWhere('email', $value)
                    ->when(ctype_digit($value), fn ($q) => $q->orWhere('id', (int) $value)))
                ->pluck('id');
            $query->where('causer_type', Admin::class)->whereIn('causer_id', $adminIds);
        }

        foreach (['event', 'log_name'] as $column) {
            if (!empty($arguments[$column])) {
                $query->where($column, (string) $arguments[$column]);
            }
        }

        if (!empty($arguments['subject'])) {
            $query->where('subject_type', 'like', '%' . str_replace('\\', '\\\\', (string) $arguments['subject']));
        }

        if (!empty($arguments['subject_id'])) {
            $query->where('subject_id', (int) $arguments['subject_id']);
        }

        if (!empty($arguments['query'])) {
            $query->where('description', 'like', '%' . $arguments['query'] . '%');
        }

        $byCauser = (clone $query)
            ->where('causer_type', Admin::class)
            ->selectRaw('causer_id, count(*) as total')
            ->groupBy('causer_id')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'causer_id');
        $names = Admin::withTrashed()->whereIn('id', $byCauser->keys())->pluck('name', 'id');

        $total = (clone $query)->count();
        $rows = (clone $query)->orderByDesc('id')->offset($this->offset($arguments))->limit($this->limit($arguments))->get();
        $rowNames = Admin::withTrashed()
            ->whereIn('id', $rows->where('causer_type', Admin::class)->pluck('causer_id')->unique())
            ->pluck('name', 'id');

        return [
            ...$this->pageInfo($total, $rows->count(), $arguments),
            'by_employee' => $byCauser->map(fn ($total, $id) => ['employee' => $names[$id] ?? "#{$id}", 'total' => (int) $total])->values()->all(),
            'by_event' => $this->countBy($query, 'event'),
            'by_log' => $this->countBy($query, 'log_name'),
            'by_subject' => collect($this->countBy($query, 'subject_type', 8))
                ->mapWithKeys(fn ($total, $type) => [class_basename((string) $type) ?: '-' => $total])->all(),
            'latest' => $rows->map(fn ($row) => [
                'id' => $row->id,
                'at' => $row->created_at,
                'employee' => $row->causer_type === Admin::class ? ($rowNames[$row->causer_id] ?? "#{$row->causer_id}") : null,
                'event' => $row->event,
                'log' => $row->log_name,
                'description' => Str::limit((string) $this->maskText($row->description), 200),
                'subject' => $row->subject_type ? class_basename($row->subject_type) . ' #' . $row->subject_id : null,
                'changes' => $this->changes($row->properties),
            ])->all(),
        ];
    }

    private function countBy($query, string $column, int $limit = 15): array
    {
        return (clone $query)
            ->selectRaw("{$column} as k, count(*) as total")
            ->groupBy($column)
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'k')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /** الحقول التي تغيّرت مع القيمة القديمة والجديدة، مختصرة وبلا الحقول السرية. */
    private function changes(?string $properties): ?array
    {
        $data = json_decode((string) $properties, true);
        if (!is_array($data) || !is_array($data['attributes'] ?? null)) {
            return null;
        }

        $old = is_array($data['old'] ?? null) ? $data['old'] : [];
        $changes = [];

        foreach (array_slice($data['attributes'], 0, 15, true) as $field => $new) {
            if (in_array($field, ['updated_at', 'created_at'], true)) {
                continue;
            }

            $hidden = Str::contains(strtolower((string) $field), self::HIDDEN_FIELDS);
            $changes[$field] = $hidden
                ? '[مخفي]'
                : ['old' => $this->short($old[$field] ?? null), 'new' => $this->short($new)];
        }

        return $changes ?: null;
    }

    private function short($value)
    {
        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return is_string($value) ? Str::limit((string) $this->maskText($value), 100) : $value;
    }
}
