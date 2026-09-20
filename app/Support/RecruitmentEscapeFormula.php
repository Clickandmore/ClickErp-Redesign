<?php

namespace App\Support;

use App\Models\Contract;
use App\Models\TravelHistory;
use Carbon\Carbon;

trait RecruitmentEscapeFormula
{
    private function buildEscapeCompensationFormula(Contract $contract, $escapeDate = null): array
    {
        $contract->loadMissing('info', 'maid');

        $startDate = $this->resolveEscapeFormulaStartDate($contract);
        $escapeDate = $escapeDate ? Carbon::parse($escapeDate)->startOfDay() : now()->startOfDay();
        $durationDays = $this->resolveEscapeFormulaDurationDays($contract);
        $totalCost = $this->resolveEscapeFormulaTotalCost($contract);
        $endDate = $startDate && $durationDays > 0 ? $startDate->copy()->addDays($durationDays) : null;
        $remainingDays = $endDate ? max(0, $escapeDate->diffInDays($endDate, false)) : 0;
        $dailyCost = $durationDays > 0 ? $totalCost / $durationDays : 0;
        $amount = $durationDays > 0 ? $dailyCost * $remainingDays : 0;

        return [
            'formula_text' => '(إجمالي تكلفة الاستقدام ÷ مدة عقد عمل العامل المنزلي بالأيام) × المدة المتبقية من مدة عقد عمل العامل المنزلي بالأيام',
            'total_recruitment_cost' => round($totalCost, 2),
            'contract_duration_days' => $durationDays,
            'contract_start_date' => $startDate?->toDateString(),
            'contract_end_date' => $endDate?->toDateString(),
            'escape_date' => $escapeDate->toDateString(),
            'remaining_days' => $remainingDays,
            'daily_cost' => round($dailyCost, 2),
            'amount' => round($amount, 2),
        ];
    }

    private function formatEscapeCompensationFormulaNote(array $formula): string
    {
        return implode("\n", [
            'معادلة احتساب بلاغ هروب العاملة:',
            $formula['formula_text'],
            'إجمالي تكلفة الاستقدام: ' . number_format((float) $formula['total_recruitment_cost'], 2) . ' ريال',
            'مدة عقد عمل العامل المنزلي بالأيام: ' . (int) $formula['contract_duration_days'] . ' يوم',
            'المدة المتبقية من مدة عقد عمل العامل المنزلي بالأيام: ' . (int) $formula['remaining_days'] . ' يوم',
            'الناتج النهائي: ' . number_format((float) $formula['amount'], 2) . ' ريال',
        ]);
    }

    private function resolveEscapeFormulaTotalCost(Contract $contract): float
    {
        foreach ([
            $contract->info?->contract_value,
            $contract->info?->msaned_price,
            $contract->info?->sponsorship_price,
            $contract->maid?->price,
        ] as $value) {
            $amount = (float) $value;
            if ($amount > 0) {
                return $amount;
            }
        }

        return 0.0;
    }

    private function resolveEscapeFormulaDurationDays(Contract $contract): int
    {
        $duration = (int) ($contract->info?->contract_duration ?? 0);
        if ($duration <= 0 && $contract->info?->ingaz_from && $contract->info?->ingaz_to) {
            return max(0, Carbon::parse($contract->info->ingaz_from)->diffInDays(Carbon::parse($contract->info->ingaz_to), false));
        }

        if ($duration > 0 && $duration <= 10) {
            return $duration * 365;
        }

        if ($duration > 10 && $duration <= 60) {
            return $duration * 30;
        }

        return max(0, $duration);
    }

    private function resolveEscapeFormulaStartDate(Contract $contract): ?Carbon
    {
        $travelArrivalDate = TravelHistory::query()
            ->where('order_id', $contract->id)
            ->whereNotNull('arrival_date')
            ->orderBy('arrival_date')
            ->value('arrival_date');

        foreach ([
            $travelArrivalDate,
            $contract->info?->arrival_date,
            $contract->warranty_start_date,
            $contract->info?->ingaz_from,
            $contract->info?->contract_date,
        ] as $date) {
            if ($date) {
                return Carbon::parse($date)->startOfDay();
            }
        }

        return null;
    }

}
