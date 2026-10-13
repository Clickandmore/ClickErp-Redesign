<?php

namespace App\Services\commissions;

use App\Models\RentalContract;
use App\Models\RentalContractExtension;
use App\Models\commissions\CommissionBatchItem;
use App\Models\commissions\CommissionSetting;
use App\Support\RentalContractDays;
use Carbon\Carbon;

class RentalCommissionReturnPolicy
{
    public function current(): string
    {
        return CommissionSetting::query()->where('service_type', 'rental')->value('rental_return_policy') ?? 'full';
    }

    public function snapshot(RentalContract $contract, ?array $period = null): array
    {
        $start = $period ? ($period['start_date'] ?? null) : ($contract->info?->ingaz_from ?: $contract->contract_date);
        $days = $period ? (int) $period['days'] : (int) ($contract->info?->contract_duration_days
            ?: (($contract->info?->contract_duration_months ?? 0) * 30));
        if (!$period && $days <= 0 && $start && $contract->info?->ingaz_to) {
            $days = max(0, (int) Carbon::parse($start)->diffInDays($contract->info->ingaz_to, false)
                - (int) $contract->extensions->sum('extension_days'));
        }

        return [
            'policy' => $this->current(),
            'start_date' => $start ? Carbon::parse($start)->toDateString() : null,
            'days' => $days,
        ];
    }

    public function snapshotForOperation(array $operation): ?array
    {
        if ($operation['service_type'] !== 'rental') return null;
        $contract = RentalContract::query()->with(['info', 'extensions'])->findOrFail($operation['contract_id']);
        $billing = $operation['billing'] ?? [];
        $period = ($billing['kind'] ?? null) === 'extension_period'
            ? ['start_date' => $billing['period_start'], 'days' => $billing['period_days']]
            : null;

        return $this->snapshot($contract, $period);
    }

    public function terms(CommissionBatchItem $item, RentalContract $contract, ?RentalContractExtension $extension = null): array
    {
        if ($item->rental_commission_terms !== null) return $item->rental_commission_terms;
        $period = null;
        if (str_starts_with((string) $item->source_type, CommissionOperationService::SOURCE_RENTAL_EXTENSION_PERIOD_PREFIX)) {
            $sequence = (int) substr($item->source_type, strlen(CommissionOperationService::SOURCE_RENTAL_EXTENSION_PERIOD_PREFIX));
            $period = collect($extension?->commissionPeriods() ?? [])->firstWhere('sequence', $sequence)
                ?? ['start_date' => null, 'days' => 0];
        }

        return $this->snapshot($contract, $period);
    }

    public function calculate(float $commission, array $terms, ?Carbon $returnedAt): array
    {
        $commission = max(0, round($commission, 2));
        if (($terms['policy'] ?? 'full') === 'full') {
            return ['amount' => $commission, 'retained' => 0.0, 'used_days' => null, 'days' => null, 'error' => null];
        }
        $days = (int) ($terms['days'] ?? 0);
        if (!$returnedAt || empty($terms['start_date']) || $days <= 0) {
            return ['amount' => null, 'retained' => null, 'used_days' => null, 'days' => $days,
                'error' => 'يلزم تسجيل بداية الفترة ومدتها وتاريخ الإنهاء لحساب خصم المدة المتبقية.'];
        }
        $start = Carbon::parse($terms['start_date'])->startOfDay();
        $usage = RentalContractDays::calculate($days, $start, $start->copy()->addDays($days - 1), $returnedAt);
        $retained = round($commission * $usage['used_days'] / $days, 2);

        return ['amount' => round($commission - $retained, 2), 'retained' => $retained,
            'used_days' => $usage['used_days'], 'days' => $days, 'error' => null];
    }

    public function returnedAt(RentalContract $contract): ?Carbon
    {
        // A credit note alone is not evidence that service stopped on its issue date.
        if ($contract->closed_at) return Carbon::parse($contract->closed_at);
        $date = $contract->info?->closure_pricing['closure_date'] ?? null;
        return $date ? Carbon::parse($date) : null;
    }
}
