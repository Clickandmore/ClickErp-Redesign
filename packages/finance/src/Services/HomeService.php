<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\BondTypes;
use Nami\Finance\MainService;
use Nami\Finance\Models\Bond;


class HomeService extends MainService
{
    public function __construct(private BondService $bondService) {}

    public function index()
    {
        // Group revenues by month
        $revenues_value_month = Bond::query()
            ->where('type', BondTypes::Qabd->value)
            ->selectRaw('MONTH(created_at) as month, SUM(value) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        // Group expenses by month
        $expenses_value_month =  Bond::query()
            ->where('type', BondTypes::Sarf->value)
            ->selectRaw('MONTH(created_at) as month, SUM(value) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        return [
            'revenues' => $this->bondService->getWhere(['type' =>  BondTypes::Qabd->value])->sum('value'),
            'expenses' => $this->bondService->getWhere(['type' => BondTypes::Sarf->value])->sum('value'),
            'revenues_value_month' => $this->fillMissingMonths($revenues_value_month),
            'expenses_value_month' => $this->fillMissingMonths($expenses_value_month),
        ];
    }

    private function fillMissingMonths(array $data)
    {
        $filled = [];
        for ($month = 1; $month <= 12; $month++) {
            $filled[$month] = $data[$month] ?? 0;
        }
        return array_values($filled); // Return as array of values indexed from 0
    }
}
