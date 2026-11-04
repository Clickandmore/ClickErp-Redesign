<?php

namespace Nami\Finance\Services;

use App\Support\BranchReportFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Constraint;

/**
 * ميزان مراجعة العملاء: رصيد أول المدة، وحركة الفترة، والرصيد الختامي لكل حساب عميل.
 * الصفحة والتصدير يستدعيان report() بالفلاتر نفسها، فيطابق الشيت ما يظهر على الشاشة.
 * الرصيد = مدين − دائن (موجب: على العميل، سالب: للعميل).
 */
class ClientTrialBalanceService
{
    public const BALANCE_FILTERS = [
        'all' => 'كل العملاء',
        'non_zero' => 'رصيده لا يساوي صفر',
        'zero' => 'رصيده صفر',
        'debit' => 'رصيده مدين (على العميل)',
        'credit' => 'رصيده دائن (للعميل)',
    ];

    public const MOVEMENT_FILTERS = [
        'all' => 'الكل',
        'with' => 'له حركة في الفترة',
        'without' => 'بلا حركة في الفترة',
    ];

    public const SORTS = [
        'code' => 'الكود',
        'name' => 'الاسم',
        'balance_desc' => 'الرصيد من الأعلى',
        'balance_asc' => 'الرصيد من الأقل',
    ];

    private const EPSILON = 0.005;

    /** يحوّل مدخلات الطلب إلى فلاتر صالحة بقيم افتراضية. */
    public function filters(array $input, ?object $year = null): array
    {
        $pick = fn (string $key, array $allowed, string $default) => array_key_exists((string) ($input[$key] ?? ''), $allowed) ? (string) $input[$key] : $default;

        return [
            'from_date' => $this->date($input['from_date'] ?? null) ?? ($year->from_date ?? now()->startOfYear()->toDateString()),
            'to_date' => $this->date($input['to_date'] ?? null) ?? ($year->to_date ?? now()->toDateString()),
            'with_opening' => ($input['with_opening'] ?? '1') !== '0',
            'approved_only' => ($input['approved_only'] ?? '0') === '1',
            'balance' => $pick('balance', self::BALANCE_FILTERS, 'all'),
            'movement' => $pick('movement', self::MOVEMENT_FILTERS, 'all'),
            'search' => trim((string) ($input['search'] ?? '')),
            'min_balance' => is_numeric($input['min_balance'] ?? null) ? abs((float) $input['min_balance']) : null,
            'sort' => $pick('sort', self::SORTS, 'code'),
        ];
    }

    /** @return array{rows: Collection, totals: array, count: int} */
    public function report(array $filters): array
    {
        $rows = $this->rows($filters);

        $totals = ['opening_debit' => 0.0, 'opening_credit' => 0.0, 'period_debit' => 0.0, 'period_credit' => 0.0, 'closing_debit' => 0.0, 'closing_credit' => 0.0];
        foreach ($rows as $row) {
            foreach ($totals as $key => $value) {
                $totals[$key] = $value + $row->{$key};
            }
        }
        $totals = array_map(fn ($value) => round($value, 2), $totals);
        $totals['net'] = round($totals['closing_debit'] - $totals['closing_credit'], 2);

        return ['rows' => $rows, 'totals' => $totals, 'count' => $rows->count()];
    }

    private function rows(array $filters): Collection
    {
        $from = $filters['from_date'];
        $to = $filters['to_date'];
        $lines = DB::table('fn_constraint_details as d')
            ->join('fn_constraints as c', 'c.id', '=', 'd.constraint_id')
            ->whereNull('d.deleted_at')
            ->whereNull('c.deleted_at')
            ->whereDate('d.date', '<=', $to)
            ->when($filters['approved_only'] && Constraint::hasApprovalColumns(), fn ($q) => $q->where('c.approval_status', Constraint::APPROVAL_APPROVED))
            ->select('d.account_id')
            ->selectRaw('SUM(CASE WHEN DATE(d.date) < ? THEN CAST(d.debtor_value AS DECIMAL(24,2)) ELSE 0 END) opening_debit_raw', [$from])
            ->selectRaw('SUM(CASE WHEN DATE(d.date) < ? THEN CAST(d.creditor_value AS DECIMAL(24,2)) ELSE 0 END) opening_credit_raw', [$from])
            ->selectRaw('SUM(CASE WHEN DATE(d.date) >= ? THEN CAST(d.debtor_value AS DECIMAL(24,2)) ELSE 0 END) period_debit', [$from])
            ->selectRaw('SUM(CASE WHEN DATE(d.date) >= ? THEN CAST(d.creditor_value AS DECIMAL(24,2)) ELSE 0 END) period_credit', [$from])
            ->selectRaw('SUM(CASE WHEN DATE(d.date) >= ? THEN 1 ELSE 0 END) period_lines', [$from])
            ->groupBy('d.account_id');
        BranchReportFilter::apply($lines, 'd');

        $rows = DB::table('fn_accounts as a')
            ->leftJoinSub($lines, 'm', 'm.account_id', '=', 'a.id')
            ->where('a.type', AccountTypesEnum::Client->value)
            ->when($filters['search'] !== '', function ($q) use ($filters) {
                $q->where(fn ($w) => $w->where('a.name', 'like', '%' . $filters['search'] . '%')->orWhere('a.code', 'like', $filters['search'] . '%'));
            })
            ->select('a.id', 'a.code', 'a.name')
            ->selectRaw('COALESCE(m.opening_debit_raw, 0) opening_debit_raw, COALESCE(m.opening_credit_raw, 0) opening_credit_raw')
            ->selectRaw('COALESCE(m.period_debit, 0) period_debit, COALESCE(m.period_credit, 0) period_credit, COALESCE(m.period_lines, 0) period_lines')
            ->get();

        $rows = $rows->map(function ($row) use ($filters) {
            $openingNet = $filters['with_opening'] ? (float) $row->opening_debit_raw - (float) $row->opening_credit_raw : 0.0;
            $row->opening_debit = round(max($openingNet, 0), 2);
            $row->opening_credit = round(max(-$openingNet, 0), 2);
            $row->period_debit = round((float) $row->period_debit, 2);
            $row->period_credit = round((float) $row->period_credit, 2);
            $row->balance = round($openingNet + $row->period_debit - $row->period_credit, 2);
            $row->closing_debit = max($row->balance, 0);
            $row->closing_credit = max(-$row->balance, 0);
            $row->period_lines = (int) $row->period_lines;

            return $row;
        });

        $rows = $rows->filter(function ($row) use ($filters) {
            $zero = abs($row->balance) < self::EPSILON;
            $passesBalance = match ($filters['balance']) {
                'non_zero' => !$zero,
                'zero' => $zero,
                'debit' => $row->balance >= self::EPSILON,
                'credit' => $row->balance <= -self::EPSILON,
                default => true,
            };
            $passesMovement = match ($filters['movement']) {
                'with' => $row->period_lines > 0,
                'without' => $row->period_lines === 0,
                default => true,
            };
            $passesMin = $filters['min_balance'] === null || abs($row->balance) >= $filters['min_balance'];

            return $passesBalance && $passesMovement && $passesMin;
        });

        $rows = match ($filters['sort']) {
            'name' => $rows->sortBy('name', SORT_NATURAL),
            'balance_desc' => $rows->sortByDesc('balance'),
            'balance_asc' => $rows->sortBy('balance'),
            default => $rows->sortBy(fn ($row) => str_pad((string) $row->code, 20, '0', STR_PAD_LEFT)),
        };

        return $rows->values();
    }

    /** وصف الفلاتر المطبقة لرأس الشيت والطباعة. */
    public function describe(array $filters): array
    {
        $lines = ['الفترة: من ' . $filters['from_date'] . ' إلى ' . $filters['to_date']];
        $lines[] = $filters['with_opening'] ? 'يشمل رصيد أول المدة' : 'حركة الفترة فقط بلا رصيد أول المدة';
        $lines[] = $filters['approved_only'] ? 'القيود المعتمدة فقط' : 'كل القيود غير المحذوفة';
        if ($filters['balance'] !== 'all') {
            $lines[] = 'الرصيد: ' . self::BALANCE_FILTERS[$filters['balance']];
        }
        if ($filters['movement'] !== 'all') {
            $lines[] = 'الحركة: ' . self::MOVEMENT_FILTERS[$filters['movement']];
        }
        if ($filters['min_balance'] !== null) {
            $lines[] = 'الرصيد لا يقل عن ' . number_format($filters['min_balance'], 2);
        }
        if ($filters['search'] !== '') {
            $lines[] = 'بحث: ' . $filters['search'];
        }

        return $lines;
    }

    private function date($value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }
}
