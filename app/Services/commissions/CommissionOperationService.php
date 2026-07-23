<?php

namespace App\Services\commissions;

use App\Models\Contract;
use App\Models\IngazDelegation;
use App\Models\RentalContract;
use App\Models\TransferServiceContract;
use App\Models\commissions\CommissionBatchItem;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CommissionOperationService
{
    public const SOURCE_RECRUITMENT_CONTRACT = 'recruitment_contract';
    public const SOURCE_RENTAL_CONTRACT = 'rental_contract';
    public const SOURCE_TRANSFER_CONTRACT = 'transfer_service_contract';
    public const SOURCE_DELEGATION = 'ingaz_delegation';

    public function getOperations(int $adminId, array $filters = []): Collection
    {
        $operations = collect()
            ->concat($this->recruitmentContracts($adminId))
            ->concat($this->rentalContracts($adminId))
            ->concat($this->transferContracts($adminId))
            ->concat($this->delegations($adminId));

        $commissioned = CommissionBatchItem::query()
            ->whereNotNull('source_type')
            ->whereNotNull('source_id')
            ->whereIn('source_type', $operations->pluck('source_type')->unique())
            ->whereIn('source_id', $operations->pluck('source_id')->unique())
            ->with('batch:id,status')
            ->get()
            ->keyBy(fn (CommissionBatchItem $item) => $this->key($item->source_type, $item->source_id));

        $operations = $operations->map(function (array $operation) use ($commissioned) {
            $item = $commissioned->get($operation['key']);
            $operation['commissioned'] = $item !== null;
            $operation['commission_batch_id'] = $item?->batch_id;
            $operation['commission_amount'] = (float) ($item?->commission_amount ?? 0);
            $operation['commission_paid_historically'] =
                $item?->legacy_source === 'alrafedin_paid_commissions';

            return $operation;
        });

        if (! empty($filters['service_type'])) {
            $operations = $operations->where('service_type', $filters['service_type']);
        }

        if (($filters['commission_status'] ?? '') === 'pending') {
            $operations = $operations->where('commissioned', false);
        } elseif (($filters['commission_status'] ?? '') === 'commissioned') {
            $operations = $operations->where('commissioned', true);
        }

        $from = $this->filterStartDate($filters);
        $to = $this->filterEndDate($filters);

        if ($from) {
            $operations = $operations->filter(fn (array $item) => $item['operation_date'] && $item['operation_date']->gte($from));
        }
        if ($to) {
            $operations = $operations->filter(fn (array $item) => $item['operation_date'] && $item['operation_date']->lte($to));
        }

        return $operations->sortByDesc('operation_date')->values();
    }

    public function findSelected(int $adminId, array $keys): Collection
    {
        $wanted = collect($keys)->filter(fn ($key) => is_string($key) && str_contains($key, ':'))->unique();

        return $this->getOperations($adminId)
            ->whereIn('key', $wanted)
            ->where('commissioned', false)
            ->values();
    }

    public function summary(int $adminId): array
    {
        $operations = $this->getOperations($adminId);
        $serviceSummary = function (string $serviceType) use ($operations): array {
            $items = $operations->where('service_type', $serviceType);

            return [
                'total' => $items->count(),
                'pending' => $items->where('commissioned', false)->count(),
                'commissioned' => $items->where('commissioned', true)->count(),
            ];
        };

        $recruitment = $serviceSummary('recruitment');
        $rental = $serviceSummary('rental');
        $transfer = $serviceSummary('service_transfer');
        $delegation = $serviceSummary('electronic_ingaz_delegation');

        return [
            'total' => $operations->count(),
            'pending' => $operations->where('commissioned', false)->count(),
            'commissioned' => $operations->where('commissioned', true)->count(),
            'commissioned_amount' => round((float) $operations->sum('commission_amount'), 2),
            'recruitment' => $recruitment['total'],
            'recruitment_pending' => $recruitment['pending'],
            'recruitment_commissioned' => $recruitment['commissioned'],
            'rental' => $rental['total'],
            'rental_pending' => $rental['pending'],
            'rental_commissioned' => $rental['commissioned'],
            'transfer' => $transfer['total'],
            'transfer_pending' => $transfer['pending'],
            'transfer_commissioned' => $transfer['commissioned'],
            'delegation' => $delegation['total'],
            'delegation_pending' => $delegation['pending'],
            'delegation_commissioned' => $delegation['commissioned'],
        ];
    }

    public function key(string $sourceType, int $sourceId): string
    {
        return $sourceType . ':' . $sourceId;
    }

    private function recruitmentContracts(int $adminId): Collection
    {
        $query = Contract::query();
        $this->scopeByCommissionMarketer(
            $query,
            'contracts',
            self::SOURCE_RECRUITMENT_CONTRACT,
            $adminId,
            function (Builder $query) use ($adminId) {
                $query->where('marketer_id', $adminId)
                    ->orWhere(function (Builder $fallback) use ($adminId) {
                        $fallback->whereNull('marketer_id')
                            ->whereHas('order', fn (Builder $order) => $order->whereResponsibleAdmin($adminId));
                    });
            }
        );

        return $query
            ->with(['user:id,name', 'order:id,order_code', 'invoice', 'info'])
            ->get()
            ->map(function (Contract $contract) {
                $base = (float) ($contract->invoice?->total_due
                    ?? $contract->info?->contract_value
                    ?? $contract->contract_cost
                    ?? 0);

                return $this->operation(
                    self::SOURCE_RECRUITMENT_CONTRACT,
                    $contract->id,
                    'recruitment',
                    'عقد استقدام',
                    (string) $contract->id,
                    $contract->order?->order_code,
                    $contract->user?->name,
                    $this->contractStatus($contract->status),
                    $base,
                    $contract->invoice?->is_paid,
                    $contract->contract_date ?: $contract->created_at,
                    $contract->order_id,
                    $contract->id
                );
            });
    }

    private function rentalContracts(int $adminId): Collection
    {
        $query = RentalContract::query();
        $this->scopeByCommissionMarketer(
            $query,
            'rental_contracts',
            self::SOURCE_RENTAL_CONTRACT,
            $adminId,
            function (Builder $query) use ($adminId) {
                $query->where('marketer_id', $adminId)
                    ->orWhere(function (Builder $fallback) use ($adminId) {
                        $fallback->whereNull('marketer_id')
                            ->whereHas('order', fn (Builder $order) => $order->whereResponsibleAdmin($adminId));
                    });
            }
        );

        return $query
            ->with(['user:id,name', 'order:id,order_code', 'financeInvoice', 'info'])
            ->get()
            ->map(function (RentalContract $contract) {
                $base = (float) ($contract->financeInvoice?->total_due
                    ?? $contract->info?->contract_value
                    ?? $contract->info?->package_total_with_tax
                    ?? 0);

                return $this->operation(
                    self::SOURCE_RENTAL_CONTRACT,
                    $contract->id,
                    'rental',
                    'عقد تأجير',
                    $contract->contract_number ?: (string) $contract->id,
                    $contract->order?->order_code,
                    $contract->user?->name,
                    $this->rentalStatus($contract->status),
                    $base,
                    $contract->financeInvoice?->is_paid,
                    $contract->contract_date ?: $contract->created_at,
                    $contract->order_id,
                    $contract->id
                );
            });
    }

    private function transferContracts(int $adminId): Collection
    {
        $query = TransferServiceContract::query();
        $this->scopeByCommissionMarketer(
            $query,
            'transfer_service_contracts',
            self::SOURCE_TRANSFER_CONTRACT,
            $adminId,
            fn (Builder $query) => $query->where('created_by', $adminId)
        );

        return $query
            ->with(['newSponsor:id,name'])
            ->get()
            ->map(fn (TransferServiceContract $contract) => $this->operation(
                self::SOURCE_TRANSFER_CONTRACT,
                $contract->id,
                'service_transfer',
                'عقد نقل خدمة',
                $contract->contract_number ?: (string) $contract->id,
                null,
                $contract->newSponsor?->name,
                $this->transferStatus($contract->status),
                (float) $contract->contract_value,
                (float) $contract->remaining_amount <= 0,
                $contract->contract_start_date ?: $contract->created_at,
                null,
                $contract->contract_id
            ));
    }

    private function delegations(int $adminId): Collection
    {
        $query = IngazDelegation::query();
        $this->scopeByCommissionMarketer(
            $query,
            'ingaz_delegations',
            self::SOURCE_DELEGATION,
            $adminId,
            fn (Builder $query) => $query->where('marketer_id', $adminId)
        );

        return $query
            ->with(['user:id,name', 'invoice'])
            ->get()
            ->map(fn (IngazDelegation $delegation) => $this->operation(
                self::SOURCE_DELEGATION,
                $delegation->id,
                'electronic_ingaz_delegation',
                'تفويض إنجاز',
                $delegation->ingaz_number ?: (string) $delegation->id,
                null,
                $delegation->user?->name,
                (string) ($delegation->status ?: '-'),
                (float) ($delegation->invoice?->total_due ?? 0),
                $delegation->invoice?->is_paid,
                $delegation->delegation_date_gregorian ?: $delegation->created_at,
                null,
                null
            ));
    }

    private function scopeByCommissionMarketer(
        Builder $query,
        string $table,
        string $sourceType,
        int $adminId,
        Closure $operationalMarketerScope
    ): void {
        $commissionItemExists = function ($subquery, ?int $marketerId = null) use ($table, $sourceType) {
            $subquery->selectRaw('1')
                ->from('commission_batch_items as marketer_commission_items')
                ->join(
                    'commission_batches as marketer_commission_batches',
                    'marketer_commission_batches.id',
                    '=',
                    'marketer_commission_items.batch_id'
                )
                ->where('marketer_commission_items.source_type', $sourceType)
                ->whereColumn('marketer_commission_items.source_id', "{$table}.id");

            if ($marketerId !== null) {
                $subquery->where('marketer_commission_batches.marketer_id', $marketerId);
            }
        };

        $query->where(function (Builder $scope) use (
            $adminId,
            $commissionItemExists,
            $operationalMarketerScope
        ) {
            $scope->whereExists(fn ($subquery) => $commissionItemExists($subquery, $adminId))
                ->orWhere(function (Builder $pending) use (
                    $commissionItemExists,
                    $operationalMarketerScope
                ) {
                    $pending->whereNotExists(fn ($subquery) => $commissionItemExists($subquery))
                        ->where($operationalMarketerScope);
                });
        });
    }

    private function operation(
        string $sourceType,
        int $sourceId,
        string $serviceType,
        string $sourceLabel,
        ?string $reference,
        ?string $orderReference,
        ?string $clientName,
        string $status,
        float $baseAmount,
        ?bool $isPaid,
        mixed $date,
        ?int $orderId,
        ?int $contractId
    ): array {
        return [
            'key' => $this->key($sourceType, $sourceId),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_label' => $sourceLabel,
            'reference' => $reference ?: '-',
            'order_reference' => $orderReference ?: '-',
            'service_type' => $serviceType,
            'client_name' => $clientName ?: '-',
            'status' => $status,
            'base_amount' => round($baseAmount, 2),
            'is_paid' => $isPaid,
            'operation_date' => $date ? Carbon::parse($date)->startOfDay() : null,
            'order_id' => $orderId,
            'contract_id' => $contractId,
        ];
    }

    private function filterStartDate(array $filters): ?Carbon
    {
        if (($filters['date_filter'] ?? '') === 'range' && ! empty($filters['from_date'])) {
            return Carbon::parse($filters['from_date'])->startOfDay();
        }
        if (($filters['date_filter'] ?? '') === 'month_year' && ! empty($filters['year'])) {
            return Carbon::create((int) $filters['year'], (int) ($filters['month'] ?: 1), 1)->startOfDay();
        }

        return null;
    }

    private function filterEndDate(array $filters): ?Carbon
    {
        if (($filters['date_filter'] ?? '') === 'range' && ! empty($filters['to_date'])) {
            return Carbon::parse($filters['to_date'])->endOfDay();
        }
        if (($filters['date_filter'] ?? '') === 'month_year' && ! empty($filters['year'])) {
            $date = Carbon::create((int) $filters['year'], (int) ($filters['month'] ?: 12), 1);
            return ! empty($filters['month']) ? $date->endOfMonth() : $date->endOfYear();
        }

        return null;
    }

    private function contractStatus(?string $status): string
    {
        return \App\Support\RecruitmentContractStageLabels::label((string) $status, app()->getLocale())
            ?? ($status ?: '-');
    }

    private function rentalStatus(?string $status): string
    {
        return ['active' => 'ساري', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'][$status] ?? ($status ?: '-');
    }

    private function transferStatus(?string $status): string
    {
        return [
            'draft' => 'مسودة',
            'sent' => 'مرسل',
            'signed' => 'موقع',
            'active' => 'ساري',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
        ][$status] ?? ($status ?: '-');
    }
}
