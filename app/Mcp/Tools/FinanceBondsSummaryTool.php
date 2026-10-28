<?php

namespace App\Mcp\Tools;

use App\Models\auth\Admin;
use Nami\Finance\Enums\BondPaymentTypes;
use Nami\Finance\Models\Bond;

/**
 * سندات القبض والصرف في فترة. كل نوع يظهر فقط لمن يملك صلاحية شاشته؛
 * Bond عليه BranchScoped.
 */
class FinanceBondsSummaryTool extends McpTool
{
    private const TYPES = [
        'qabd' => ['label' => 'سندات القبض', 'route' => 'finance.bonds-qabd.index'],
        'sarf' => ['label' => 'سندات الصرف', 'route' => 'finance.bonds-sarf.index'],
    ];

    public function name(): string
    {
        return 'finance_bonds_summary';
    }

    public function description(): string
    {
        return 'ملخص سندات القبض (التحصيل) وسندات الصرف في فترة: العدد والإجمالي وطريقة الدفع وأحدث السندات. '
            . 'Receipt and payment vouchers for a period: counts, totals, payment methods, latest vouchers.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'from' => ['type' => 'string', 'format' => 'date'],
                'to' => ['type' => 'string', 'format' => 'date'],
                'type' => ['type' => 'string', 'enum' => ['qabd', 'sarf'], 'description' => 'qabd = قبض، sarf = صرف. بدونه: الاثنان'],
                'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10],
            ],
        ];
    }

    protected function gateRoute(): ?string
    {
        return 'finance.bonds-qabd.index';
    }

    protected function gatePackage(): ?string
    {
        return 'finance';
    }

    /** تظهر الأداة لمن يملك صلاحية القبض أو الصرف. */
    public function isAllowedFor(Admin $admin): bool
    {
        return $this->allowedTypes($admin) !== [];
    }

    public function handle(array $arguments, Admin $admin): array
    {
        $types = $this->allowedTypes($admin);
        if (!empty($arguments['type'])) {
            $types = array_values(array_intersect($types, [(string) $arguments['type']]));
        }

        $result = ['currency' => 'SAR'];

        foreach ($types as $type) {
            $query = Bond::query()->where('type', $type)
                ->when(!empty($arguments['from']), fn ($q) => $q->whereDate('date', '>=', $arguments['from']))
                ->when(!empty($arguments['to']), fn ($q) => $q->whereDate('date', '<=', $arguments['to']));

            $totals = (clone $query)->selectRaw('count(*) as bonds, coalesce(sum(value), 0) as total, coalesce(sum(net), 0) as net')->first();

            $result[$type] = [
                'label' => self::TYPES[$type]['label'],
                'count' => (int) $totals->bonds,
                'total' => round((float) $totals->total, 2),
                'net' => round((float) $totals->net, 2),
                'by_payment_type' => (clone $query)
                    ->selectRaw('payment_type, count(*) as bonds, coalesce(sum(value), 0) as total')
                    ->groupBy('payment_type')
                    ->get()
                    ->mapWithKeys(fn ($row) => [$this->paymentLabel($row->payment_type) => ['count' => (int) $row->bonds, 'total' => round((float) $row->total, 2)]])
                    ->all(),
                'latest' => $query->with(['debitAccount:id,name,code', 'creditAccount:id,name,code'])
                    ->latest('date')->latest('id')
                    ->limit($this->limit($arguments, 10))
                    ->get()
                    ->map(fn (Bond $bond) => [
                        'code' => $bond->code,
                        'date' => $this->date($bond->date),
                        'value' => $bond->value,
                        'payment_type' => $this->paymentLabel($bond->payment_type),
                        'debit_account' => $bond->debitAccount?->name,
                        'credit_account' => $bond->creditAccount?->name,
                        'statement' => \Illuminate\Support\Str::limit((string) $bond->statement, 150),
                    ])->all(),
            ];
        }

        $hidden = array_diff(array_keys(self::TYPES), $this->allowedTypes($admin));
        if ($hidden) {
            $result['not_permitted'] = array_values(array_map(fn ($type) => self::TYPES[$type]['label'], $hidden));
        }

        return $result;
    }

    private function paymentLabel($value): string
    {
        return is_numeric($value) ? BondPaymentTypes::langFromValue((int) $value) : (string) ($value ?: '-');
    }

    private function allowedTypes(Admin $admin): array
    {
        return array_keys(array_filter(self::TYPES, fn ($meta) => self::routeAllows($admin, $meta['route'], 'finance')));
    }
}
