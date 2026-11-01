<?php

namespace App\Mcp\Tools;

use App\Models\auth\Admin;
use Illuminate\Http\Request;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Services\ConstraintDetailsService;

/** قائمة الدخل مختصرة، من نفس حساب شاشة finance.profit-loss فلا يوجد مصدران للرقم. */
class ProfitLossSummaryTool extends McpTool
{
    public function name(): string
    {
        return 'profit_loss_summary';
    }

    public function description(): string
    {
        return 'قائمة الدخل لفترة: إجمالي الإيرادات والمصروفات وصافي الربح أو الخسارة، مع الحسابات الرئيسية. '
            . 'Income statement for a period: revenue, expenses, net result and main accounts.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'from' => ['type' => 'string', 'format' => 'date'],
                'to' => ['type' => 'string', 'format' => 'date'],
            ],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'finance.profit-loss.index';
    }

    protected function gatePackage(): ?string
    {
        return 'finance';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $roots = app(ConstraintDetailsService::class)->filterProfitLoss(new Request(array_filter([
            'from_date' => $arguments['from'] ?? null,
            'to_date' => $arguments['to'] ?? null,
        ])));

        $revenue = 0.0;
        $expenses = 0.0;
        $sections = [];

        foreach ($roots as $root) {
            $isRevenue = $root['report_type'] === AccountTypesEnum::GeneralIncome;
            $total = round((float) $root['account_total_price'], 2);
            $isRevenue ? $revenue += $total : $expenses += $total;

            $sections[] = [
                'name' => $root['account']->name,
                'type' => $isRevenue ? 'revenue' : 'expense',
                'total' => $total,
                'accounts' => collect($root['list'])
                    ->map(fn (array $row) => ['name' => $row['account']->name ?? $row['name'] ?? null, 'total' => round((float) $row['total_price'], 2)])
                    ->filter(fn (array $row) => $row['total'] != 0.0)
                    ->values()
                    ->all(),
            ];
        }

        return [
            'currency' => 'SAR',
            'from' => $arguments['from'] ?? null,
            'to' => $arguments['to'] ?? null,
            'revenue' => round($revenue, 2),
            'expenses' => round($expenses, 2),
            'net' => round($revenue - $expenses, 2),
            'sections' => $sections,
        ];
    }
}
