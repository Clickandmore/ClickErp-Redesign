<?php

namespace App\Mcp\Tools;

use App\Models\auth\Admin;
use App\Models\ingaz\Invoice;
use App\Models\RentalContract;
use Illuminate\Support\Facades\DB;

/**
 * الديون على العملاء الآن: أعمار الفواتير غير المدفوعة، أكبر المدينين، ومتبقي عقود الإيجار.
 * الاستعلامات مقيدة بمعرّفات Invoice وRentalContract، وكلاهما عليه BranchScoped.
 */
class ReceivablesReportTool extends McpTool
{
    public function name(): string
    {
        return 'receivables_report';
    }

    public function description(): string
    {
        return 'ديون العملاء الآن: الفواتير غير المدفوعة حسب عمرها (حتى 30 و31-60 و61-90 وأكثر من 90 يوماً)، أكبر المدينين، ومتبقي عقود الإيجار السارية. '
            . 'Receivables now: unpaid invoice aging, top debtors and rental balances.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'top' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10, 'description' => 'عدد أكبر المدينين'],
            ],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'invoices.index';
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $unpaid = Invoice::query()->where(fn ($q) => $q->where('invoices.is_paid', false)->orWhereNull('invoices.is_paid'))
            ->select('invoices.id')->toBase();

        $aging = DB::table('invoices')->whereIn('id', $unpaid)->selectRaw('
            coalesce(sum(case when DATEDIFF(CURDATE(), created_at) <= 30 then total_due else 0 end), 0) as d30,
            coalesce(sum(case when DATEDIFF(CURDATE(), created_at) between 31 and 60 then total_due else 0 end), 0) as d60,
            coalesce(sum(case when DATEDIFF(CURDATE(), created_at) between 61 and 90 then total_due else 0 end), 0) as d90,
            coalesce(sum(case when DATEDIFF(CURDATE(), created_at) > 90 then total_due else 0 end), 0) as older,
            coalesce(sum(total_due), 0) as total,
            count(*) as invoices
        ')->first();

        // فواتير الاستقدام غالباً بلا client_id؛ العميل على العقد.
        $debtors = DB::table('invoices')
            ->leftJoin('contracts', 'contracts.id', '=', 'invoices.contract_id')
            ->leftJoin('users', 'users.id', '=', DB::raw('COALESCE(invoices.client_id, contracts.user_id)'))
            ->whereIn('invoices.id', $unpaid)
            ->selectRaw('users.id as id, users.name as name, count(*) as invoices, coalesce(sum(invoices.total_due), 0) as due')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('due')
            ->limit(max(1, min(50, (int) ($arguments['top'] ?? 10))))
            ->get();

        $result = [
            'as_of' => today()->toDateString(),
            'currency' => 'SAR',
            'total' => (int) $aging->invoices,
            'unpaid_invoices_total' => round((float) $aging->total, 2),
            'aging' => [
                'up_to_30_days' => round((float) $aging->d30, 2),
                'days_31_60' => round((float) $aging->d60, 2),
                'days_61_90' => round((float) $aging->d90, 2),
                'over_90_days' => round((float) $aging->older, 2),
            ],
            'top_debtors' => $debtors->map(fn ($row) => [
                'id' => $row->id,
                'url' => $row->id ? $this->link('clients.show', $row->id) : null,
                'name' => $row->name ?? 'غير محدد',
                'invoices' => (int) $row->invoices,
                'due' => round((float) $row->due, 2),
            ])->all(),
            'notes' => 'العمر محسوب من تاريخ إنشاء الفاتورة. is_paid لا يعرف السداد الجزئي، فالفاتورة المدفوعة جزئياً تُحسب كاملة.',
        ];

        if (self::routeAllows($admin, 'RentContract.index')) {
            $rentals = RentalContract::query()->whereIn('rental_contracts.status', ['maid_delivered', 'active'])
                ->select('rental_contracts.id')->toBase();
            $balance = DB::table('rental_contract_infos')->whereIn('rental_contract_id', $rentals)->where('remaining_amount', '>', 0)
                ->selectRaw('count(*) as contracts, coalesce(sum(remaining_amount), 0) as remaining')->first();

            $result['rental_balances'] = ['contracts' => (int) $balance->contracts, 'remaining' => round((float) $balance->remaining, 2)];
        }

        return $result;
    }
}
