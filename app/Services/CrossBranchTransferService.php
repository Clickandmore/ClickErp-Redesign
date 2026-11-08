<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\NewSettings;
use App\Models\SponsorshipTransfer;
use App\Models\TransferSourceContract;
use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CrossBranchTransferService
{
    private const KEY = 'cross_branch_transfer_policies';

    public function policies(): array
    {
        $raw = NewSettings::query()->where('key', self::KEY)->value('value');
        return is_string($raw) ? (json_decode($raw, true) ?: []) : [];
    }

    public function sync(array $policies): void
    {
        $valid = Branch::query()->active()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $normalized = [];
        foreach ($policies as $source => $destinations) {
            foreach ((array) $destinations as $destination => $enabled) {
                if ($enabled && (int) $source !== (int) $destination
                    && in_array((int) $source, $valid, true) && in_array((int) $destination, $valid, true)) {
                    $normalized[(int) $source][(int) $destination] = true;
                }
            }
        }
        NewSettings::query()->updateOrCreate(['key' => self::KEY], [
            'value' => json_encode($normalized, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function sourceBranchIds(): array
    {
        $access = app(BranchAccessService::class);
        $admin = auth('admin')->user();
        if (!$access->enabled() || !$admin || !$access->adminBranchId($admin)
            || !AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [PermKeys::SPONSOR_TRANSFER_CROSS_BRANCH])) {
            return [];
        }
        $destination = $access->adminBranchId($admin);
        $active = Branch::query()->active()->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (!in_array($destination, $active, true)) {
            return [];
        }
        $policies = $this->policies();
        return array_values(array_filter(array_map('intval', array_keys($policies)),
            fn ($source) => in_array($source, $active, true) && !empty($policies[$source][$destination])));
    }

    public function candidates(): Builder
    {
        $query = TransferSourceContract::withoutBranchScope()->insideShelter()
            ->with(['maid.jobTypes', 'user', 'info', 'branch'])
            ->whereHas('info', fn ($q) => $q->where('wants_to_work', 'yes')
                ->where(fn ($reason) => $reason->whereNull('outside_shelter_reason')->orWhere('outside_shelter_reason', '!=', 'escape_report')))
            ->whereDoesntHave('escapeReports')
            ->whereNotExists(function ($busy) {
                $busy->selectRaw('1')->from('sponsorship_transfers as reserved')
                    ->join('contracts as reserved_source', 'reserved_source.id', '=', 'reserved.contract_id')
                    ->whereNull('reserved.deleted_at')
                    ->whereNotIn('reserved.status', ['failed', 'rejected', 'cancelled', 'transferred', 'completed'])
                    ->whereColumn('reserved_source.maid_id', 'contracts.maid_id');
            });
        if (\Illuminate\Support\Facades\Schema::hasTable('rental_sponsorship_returns')) {
            $query->whereNotExists(function ($busy) {
                $busy->selectRaw('1')->from('rental_sponsorship_returns')
                    ->whereColumn('rental_sponsorship_returns.maid_id', 'contracts.maid_id')
                    ->whereNotIn('rental_sponsorship_returns.status', ['transferred', 'cancelled']);
            });
        }
        $access = app(BranchAccessService::class);
        $branchId = $access->selectedBranchId(request());
        if ($branchId) {
            $access->applyBranchIdToQuery($query, $branchId, 'contracts', null, $this->sourceBranchIds());
        }
        return $query;
    }

    /** Called inside a transaction; serialize reservations across all branches. */
    public function reserve(int $contractId, ?SponsorshipTransfer $existing = null): TransferSourceContract
    {
        $same = $existing && (int) $existing->contract_id === $contractId;
        $contract = $same
            ? $existing->contract()->lockForUpdate()->first()
            : $this->candidates()->lockForUpdate()->find($contractId);
        if (!$contract) {
            throw ValidationException::withMessages(['contract_id' => 'العاملة غير متاحة للنقل أو غير مسموحة لفرعك.']);
        }
        app(\App\Services\ingaz\TransferCurrentSponsor::class)->validate($contract);
        if ($contract->maid_id) {
            DB::table('maids')->where('id', $contract->maid_id)->lockForUpdate()->first();
        }
        $busy = SponsorshipTransfer::withoutBranchScope()
            ->whereNotIn('status', ['failed', 'rejected', 'cancelled', 'transferred', 'completed'])
            ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
            ->where(function ($q) use ($contract) {
                $q->where('contract_id', $contract->id);
                if ($contract->maid_id) {
                    $q->orWhereHas('contract', fn ($source) => $source->where('maid_id', $contract->maid_id));
                }
            })->lockForUpdate()->first(['sponsorship_transfers.id']);
        if ($busy) {
            throw ValidationException::withMessages(['contract_id' => 'العاملة محجوزة بالفعل على طلب نقل خدمات نشط.']);
        }
        return $contract;
    }
}
