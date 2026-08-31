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

    public function __construct(
        private readonly DelegationCommissionService $delegationCommissionService,
        private readonly RecruitmentCommissionService $recruitmentCommissionService
    ) {}

    public function getOperations(int $adminId, array $filters = []): Collection
    {
        $serviceType = $filters['service_type'] ?? null;
        $operations = collect();

        if (! $serviceType || $serviceType === 'recruitment') {
            $operations = $operations->concat($this->recruitmentContracts($adminId));
        }
        if (! $serviceType || $serviceType === 'rental') {
            $operations = $operations->concat($this->rentalContracts($adminId));
        }
        if (! $serviceType || $serviceType === 'service_transfer') {
            $operations = $operations->concat($this->transferContracts($adminId));
        }
        if (! $serviceType || $serviceType === 'electronic_ingaz_delegation') {
            $operations = $operations->concat($this->delegations($adminId));
        }

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
            $operation['commission_item_id'] = $item?->id;
            $operation['commission_reversed'] = (bool) $item?->commission_reversal_transaction_id;
            $operation['return_settlement_status'] = $operation['commission_reversed']
                ? RecruitmentCommissionService::SETTLEMENT_DEDUCTED
                : ($item && $this->recruitmentCommissionService->isZeroed($item)
                    ? RecruitmentCommissionService::SETTLEMENT_ZEROED
                    : (! $operation['commission_eligible'] && $item && (float) $item->commission_amount > 0
                        ? RecruitmentCommissionService::SETTLEMENT_PENDING
                        : null));
            $operation['commission_amount'] = ($operation['commission_reversed']
                || $operation['return_settlement_status'] === RecruitmentCommissionService::SETTLEMENT_ZEROED)
                ? 0.0
                : (float) ($item?->commission_amount ?? 0);
            $operation['commission_paid_historically'] =
                $item?->legacy_source === 'alrafedin_paid_commissions';

            return $operation;
        });

        if (($filters['commission_status'] ?? '') === 'pending') {
            $operations = $operations
                ->where('commissioned', false)
                ->where('commission_eligible', true);
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
            ->where('commission_eligible', true)
            ->values();
    }

    public function summary(int $adminId): array
    {
        $operations = $this->getOperations($adminId);
        $serviceSummary = function (string $serviceType) use ($operations): array {
            $items = $operations->where('service_type', $serviceType);

            return [
                'total' => $items->count(),
                'pending' => $items
                    ->where('commissioned', false)
                    ->where('commission_eligible', true)
                    ->count(),
                'commissioned' => $items->where('commissioned', true)->count(),
            ];
        };

        $recruitment = $serviceSummary('recruitment');
        $rental = $serviceSummary('rental');
        $transfer = $serviceSummary('service_transfer');
        $delegation = $serviceSummary('electronic_ingaz_delegation');

        return [
            'total' => $operations->count(),
            'pending' => $operations
                ->where('commissioned', false)
                ->where('commission_eligible', true)
                ->count(),
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

                $operation = $this->operation(
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

                $operation['commission_eligible'] = $this->recruitmentCommissionService->isEligible($contract);
                $operation['commission_ineligible_reason'] = $operation['commission_eligible']
                    ? null
                    : 'عقد مسترد/مرتجع — العمولة صفر';

                return $operation;
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
            ->with([
                'user:id,name',
                'order:id,order_code',
                'financeInvoice',
                'info',
                'extensions',
                'financialAdjustments',
            ])
            ->get()
            ->map(function (RentalContract $contract) {
                $invoice = $contract->financeInvoice;
                $invoiceAmount = (float) ($invoice?->total_due ?? 0);
                $creditNoteAmount = (float) $contract->financialAdjustments
                    ->where('type', 'expense')
                    ->whereIn('context', ['closure', 'manual_credit_note', 'closure_correction'])
                    ->sum('amount');
                $extensions = $contract->extensions;
                $extensionAmount = (float) $extensions->sum(
                    fn ($extension) => (float) ($extension->extension_value ?? 0)
                );
                $extensionDays = (int) $extensions->sum(function ($extension) {
                    $days = (int) ($extension->extension_days ?? 0);

                    return $days > 0
                        ? $days
                        : ((int) ($extension->extension_months ?? 0) * 30);
                });
                $base = round(max(0, $invoiceAmount + $extensionAmount - $creditNoteAmount), 2);

                $operation = $this->operation(
                    self::SOURCE_RENTAL_CONTRACT,
                    $contract->id,
                    'rental',
                    'عقود التأجير',
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

                $operation['billing'] = [
                    'invoiced' => $invoice !== null,
                    'invoice_number' => $invoice?->id,
                    'invoice_amount' => round($invoiceAmount, 2),
                    'credit_note_amount' => round($creditNoteAmount, 2),
                    'extension_count' => $extensions->count(),
                    'extension_days' => $extensionDays,
                    'extension_amount' => round($extensionAmount, 2),
                    'net_amount' => $base,
                ];
                // لا تُحتسب عمولة عقد التأجير قبل إصدار فاتورته، أو إذا تم عكس كامل
                // القيمة بإشعارات دائنة. التمديدات تدخل ضمن صافي الفوترة عند وجودها.
                $operation['commission_eligible'] = $invoice !== null && $base > 0;
                $operation['commission_ineligible_reason'] = $invoice === null
                    ? 'لم تُصدر فاتورة للعميل بعد'
                    : ($base <= 0 ? 'تمت تسوية قيمة الفاتورة بالكامل بإشعار دائن' : null);

                return $operation;
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
            ->with(['user:id,name', 'invoice.recruitmentCreditNotes'])
            ->get()
            ->map(function (IngazDelegation $delegation) {
                $operation = $this->operation(
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
                );

                $operation['commission_ineligible_reason'] =
                    $this->delegationCommissionService->ineligibilityReason($delegation);
                $operation['commission_eligible'] = $operation['commission_ineligible_reason'] === null;

                return $operation;
            });
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
            'commission_eligible' => true,
            'commission_ineligible_reason' => null,
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
