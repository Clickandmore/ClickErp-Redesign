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
            'policy' => $period !== null ? 'remaining_days' : $this->current(),
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

        $terms = $this->snapshot($contract, $period);
        if (isset($billing['settled_commission_base'])) {
            $terms['settled_commission_base'] = (float) $billing['settled_commission_base'];
        }
        return $terms;
    }

    public function terms(CommissionBatchItem $item, RentalContract $contract, ?RentalContractExtension $extension = null): array
    {
        if ($item->rental_commission_terms !== null) {
            $terms = $item->rental_commission_terms;
            if (isset($terms['settled_commission_base'])) {
                $contract->loadMissing(['info', 'extensions', 'financialAdjustments', 'financeInvoice']);
                $operation = app(CommissionOperationService::class)->rentalContractOperations($contract)
                    ->first(fn ($operation) => $operation['source_type'] === $item->source_type
                        && (int) $operation['source_id'] === (int) $item->source_id);
                $terms['current_settled_base'] = (float) ($operation['base_amount'] ?? 0);
            }
            // Extension commission is earned only for the days served in its own period.
            if (str_starts_with((string) $item->source_type, CommissionOperationService::SOURCE_RENTAL_EXTENSION_PERIOD_PREFIX)) {
                $terms['policy'] = 'remaining_days';
            }
            return $terms;
        }
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
        if (isset($terms['settled_commission_base'], $terms['current_settled_base'])
            && (float) $terms['settled_commission_base'] > 0) {
            // This commission was approved on the net revenue AFTER closure.
            // Only a later reduction of that revenue can reduce it again.
            $ratio = min(1, max(0, (float) $terms['current_settled_base'] / (float) $terms['settled_commission_base']));
            $retained = round($commission * $ratio, 2);
            return ['amount' => round($commission - $retained, 2), 'retained' => $retained,
                'used_days' => null, 'days' => null, 'error' => null];
        }
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
