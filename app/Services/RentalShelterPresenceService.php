<?php

namespace App\Services;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\RentalContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RentalShelterPresenceService
{
    public const OUTSIDE_RENTAL_STATUSES = [
        'maid_delivered',
        'active',
        'suspended',
        'frozen',
    ];

    public const OUTSIDE_TRANSFER_STATUSES = [
        'maid_delivered',
        'trial',
        'trial_started',
    ];

    public function markOutside(RentalContract $rentalContract): bool
    {
        if (!$rentalContract->maid_id) {
            return false;
        }

        return DB::transaction(function () use ($rentalContract) {
            $shelterContract = Contract::query()
                ->with('info')
                ->where('maid_id', $rentalContract->maid_id)
                ->where('status', ContractStatusEnum::Shelter->value)
                ->where(function ($query) {
                    $query->whereDoesntHave('info')
                        ->orWhereHas('info', function ($infoQuery) {
                            $infoQuery->where(function ($outsideQuery) {
                                $outsideQuery->whereNull('is_outside_shelter')
                                    ->orWhere('is_outside_shelter', false)
                                    ->orWhere('outside_shelter_reason', 'rental_delivery');
                            });
                        });
                })
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$shelterContract) {
                return false;
            }

            $shelterContract->info()->updateOrCreate([], [
                'is_outside_shelter' => true,
                'outside_shelter_at' => $rentalContract->maid_delivered_at ?: now(),
                'outside_shelter_reason' => 'rental_delivery',
                'outside_shelter_reference_type' => RentalContract::class,
                'outside_shelter_reference_id' => $rentalContract->id,
                'outside_shelter_by' => auth('admin')->id() ?: auth()->id(),
            ]);

            $shelterContract->contract_status_logs()->create([
                'status' => ContractStatusEnum::Shelter->value,
                'date' => now(),
                'notes' => 'تم إخراج العاملة من داخل الإيواء بسبب تسليم عقد إيجار رقم ' . $rentalContract->id,
            ]);

            return true;
        });
    }

    public function returnInside(RentalContract $rentalContract): bool
    {
        if (!$rentalContract->maid_id) {
            return false;
        }

        return $this->returnMaidInside(
            (int) $rentalContract->maid_id,
            'تمت إعادة العاملة إلى داخل الإيواء بعد إنهاء عقد إيجار رقم ' . $rentalContract->id
        );
    }

    public function returnReplacedMaidInside(int $maidId, RentalContract $rentalContract): bool
    {
        return $this->returnMaidInside(
            $maidId,
            'تمت إعادة العاملة إلى داخل الإيواء بعد استبدالها في عقد إيجار رقم ' . $rentalContract->id
        );
    }

    public function reconcileContract(Contract $contract, string $note): bool
    {
        if (!$contract->maid_id || !$this->canReturnMaidInside((int) $contract->maid_id)) {
            return false;
        }

        return DB::transaction(function () use ($contract, $note) {
            $lockedContract = Contract::query()
                ->with('info')
                ->whereKey($contract->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedContract?->info
                || !$lockedContract->info->is_outside_shelter
                || $lockedContract->info->outside_shelter_reason !== 'rental_delivery') {
                return false;
            }

            $this->clearRentalOutsideFlag($lockedContract, $note);

            return true;
        });
    }

    public function staleRentalOutsideQuery(bool $safeOnly = true): Builder
    {
        $query = Contract::query()
            ->where('status', ContractStatusEnum::Shelter->value)
            ->whereHas('info', function ($infoQuery) {
                $infoQuery->where('is_outside_shelter', true)
                    ->where('outside_shelter_reason', 'rental_delivery');
            })
            ->whereDoesntHave('maid.rentalContracts', function ($rentalQuery) {
                $rentalQuery->whereIn('status', self::OUTSIDE_RENTAL_STATUSES);
            })
            ->whereDoesntHave('sponsorshipTransfers', function ($transferQuery) {
                $transferQuery->whereIn('status', self::OUTSIDE_TRANSFER_STATUSES);
            });

        if ($safeOnly) {
            $query->whereHas('maid', function ($maidQuery) {
                $maidQuery->where('status', 'available')
                    ->where('available', true)
                    ->where('maid_accommodation_type', 2);
            });
        }

        return $query;
    }

    private function returnMaidInside(int $maidId, string $note): bool
    {
        if (!$this->canReturnMaidInside($maidId)) {
            return false;
        }

        return DB::transaction(function () use ($maidId, $note) {
            $shelterContract = Contract::query()
                ->with('info')
                ->where('maid_id', $maidId)
                ->where('status', ContractStatusEnum::Shelter->value)
                ->whereHas('info', function ($infoQuery) {
                    $infoQuery->where('is_outside_shelter', true)
                        ->where('outside_shelter_reason', 'rental_delivery');
                })
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$shelterContract?->info) {
                return false;
            }

            $this->clearRentalOutsideFlag($shelterContract, $note);

            return true;
        });
    }

    private function canReturnMaidInside(int $maidId): bool
    {
        $hasOutsideRental = RentalContract::query()
            ->where('maid_id', $maidId)
            ->whereIn('status', self::OUTSIDE_RENTAL_STATUSES)
            ->exists();

        if ($hasOutsideRental) {
            return false;
        }

        return !Contract::query()
            ->where('maid_id', $maidId)
            ->whereHas('sponsorshipTransfers', function ($transferQuery) {
                $transferQuery->whereIn('status', self::OUTSIDE_TRANSFER_STATUSES);
            })
            ->exists();
    }

    private function clearRentalOutsideFlag(Contract $contract, string $note): void
    {
        // العاملة العائدة من عقد إيجار منتهٍ تعود متاحة داخل الإيواء وراغبة بالعمل،
        // حتى تظهر مباشرة في قوائم الإيواء والتأجير دون تعديل يدوي.
        $wantsToWorkRestored = $contract->info->wants_to_work !== 'yes';

        $contract->info->update([
            'is_outside_shelter' => false,
            'outside_shelter_at' => null,
            'outside_shelter_reason' => null,
            'outside_shelter_reference_type' => null,
            'outside_shelter_reference_id' => null,
            'outside_shelter_by' => null,
            'wants_to_work' => 'yes',
        ]);

        if ($wantsToWorkRestored) {
            $note .= ' وتم ضبط رغبة العمل على "ترغب بالعمل".';
        }

        $contract->contract_status_logs()->create([
            'status' => ContractStatusEnum::Shelter->value,
            'date' => now(),
            'notes' => $note,
        ]);
    }
}
