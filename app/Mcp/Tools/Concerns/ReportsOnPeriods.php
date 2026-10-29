<?php

namespace App\Mcp\Tools\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * أدوات التقارير: فترة موحّدة (اليوم، الأسبوع، الشهر، الشهر الماضي، السنة، الكل، أو من/إلى)،
 * وتجميع العدد حسب عمود، واتجاه شهري. كل الحساب في قاعدة البيانات لا في PHP.
 */
trait ReportsOnPeriods
{
    protected function periodProperties(string $dateLabel): array
    {
        return [
            'period' => [
                'type' => 'string',
                'enum' => ['today', 'this_week', 'this_month', 'last_month', 'this_year', 'last_year', 'all'],
                'default' => 'this_month',
                'description' => "فترة التقرير حسب {$dateLabel}. تُتجاهل إذا أُرسل from أو to.",
            ],
            'from' => ['type' => 'string', 'format' => 'date', 'description' => 'من تاريخ (YYYY-MM-DD)'],
            'to' => ['type' => 'string', 'format' => 'date', 'description' => 'إلى تاريخ (YYYY-MM-DD)'],
        ];
    }

    /** @return array{from: ?Carbon, to: ?Carbon, label: string} */
    protected function resolvePeriod(array $arguments): array
    {
        $from = $this->parseDate($arguments['from'] ?? null);
        $to = $this->parseDate($arguments['to'] ?? null);

        if ($from || $to) {
            return ['from' => $from?->startOfDay(), 'to' => $to?->endOfDay(), 'label' => 'custom'];
        }

        $period = (string) ($arguments['period'] ?? 'this_month');
        $now = now();

        [$start, $end] = match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(Carbon::SATURDAY), $now->copy()->endOfDay()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            'last_year' => [$now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()],
            'all' => [null, null],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
        };

        $known = ['today', 'this_week', 'this_month', 'last_month', 'this_year', 'last_year', 'all'];

        return ['from' => $start, 'to' => $end, 'label' => in_array($period, $known, true) ? $period : 'this_month'];
    }

    /** $column عمود مؤهل أو تعبير SQL مثل COALESCE(contract_date, DATE(created_at)). */
    protected function applyPeriod($query, string $column, array $period)
    {
        if ($period['from']) {
            $query->whereRaw("{$column} >= ?", [$period['from']->toDateTimeString()]);
        }
        if ($period['to']) {
            $query->whereRaw("{$column} <= ?", [$period['to']->toDateTimeString()]);
        }

        return $query;
    }

    protected function periodSummary(array $period): array
    {
        return [
            'period' => $period['label'],
            'from' => $period['from']?->toDateString(),
            'to' => $period['to']?->toDateString(),
        ];
    }

    /** العدد حسب تعبير SQL (عمود أو COALESCE)، مرتباً تنازلياً. */
    protected function countBy($query, string $expression, int $limit = 50): array
    {
        return (clone $query)
            ->selectRaw("{$expression} as k, COUNT(*) as total")
            ->groupByRaw($expression)
            ->orderByDesc('total')
            ->limit($limit)
            ->pluck('total', 'k')
            ->mapWithKeys(fn ($total, $key) => [($key === null || $key === '' ? 'غير محدد' : (string) $key) => (int) $total])
            ->all();
    }

    /**
     * تجميع يحتاج JOIN (جنسية، مسوّق، فرع، مكتب). يُبنى على استعلام مستقل مقيّد بمعرّفات
     * الاستعلام الأصلي، لأن scopes الموديلات تكتب أعمدة مثل status بلا اسم الجدول فتتعارض مع الـ JOIN.
     */
    protected function countByJoin($scopedQuery, string $table, callable $join, string $expression, int $limit = 15): array
    {
        $query = DB::table($table)->whereIn("{$table}.id", (clone $scopedQuery)->select("{$table}.id")->toBase());
        $join($query);

        return $this->countBy($query, $expression, $limit);
    }

    /** يحوّل ['new' => 5] إلى [['key' => 'new', 'label' => 'جديد', 'count' => 5]] ليقرأه المساعد دون تخمين. */
    protected function labeled(array $counts, callable $label): array
    {
        return collect($counts)->map(fn ($count, $key) => [
            'key' => (string) $key,
            'label' => $label((string) $key) ?: (string) $key,
            'count' => $count,
        ])->values()->all();
    }

    protected function locale(): string
    {
        return app()->getLocale() ?: 'ar';
    }

    protected function ratio(int $part, int $total): ?float
    {
        return $total > 0 ? round($part / $total * 100, 1) : null;
    }

    /** العدد شهرياً داخل الفترة، بحد أقصى 24 شهراً (الأحدث). */
    protected function monthlyTrend($query, string $column): array
    {
        return (clone $query)
            ->selectRaw("DATE_FORMAT({$column}, '%Y-%m') as k, COUNT(*) as total")
            ->groupByRaw("DATE_FORMAT({$column}, '%Y-%m')")
            ->orderByDesc('k')
            ->limit(24)
            ->pluck('total', 'k')
            ->sortKeys()
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    private function parseDate($value): ?Carbon
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            throw new \App\Mcp\McpToolException("تاريخ غير صالح: {$value}. الصيغة المطلوبة YYYY-MM-DD.");
        }
    }
}
