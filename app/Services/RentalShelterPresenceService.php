<?php

namespace App\Services;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\RentalContract;
use App\Models\ShelterPlaces;
use App\Models\ingaz\Maid;
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
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (!$shelterContract) {
                $this->createMissingShelterContract($maidId, $note);

                return true;
            }

            if (!$shelterContract->info) {
                $shelterContract->info()->create([
                    'contract_date' => now()->toDateString(),
                    'msaned_number' => '',
                    'shelter_id' => $this->defaultShelterId(),
                    'wants_to_work' => 'yes',
                    'receipt_source' => 'company',
                    'is_outside_shelter' => false,
                    'is_available_for_instant' => false,
                ]);

                $shelterContract->contract_status_logs()->create([
                    'status' => ContractStatusEnum::Shelter->value,
                    'date' => now(),
                    'notes' => $note,
                ]);

                $this->markMaidAsShelterResident($maidId);

                return true;
            }

            if ($shelterContract->info->is_outside_shelter
                && $shelterContract->info->outside_shelter_reason !== 'rental_delivery') {
                return false;
            }

            if (! $shelterContract->info->is_outside_shelter) {
                if ($shelterContract->info->wants_to_work !== 'yes') {
                    $shelterContract->info->update(['wants_to_work' => 'yes']);
                }

                $this->markMaidAsShelterResident($maidId);

                return true;
            }

            $this->clearRentalOutsideFlag($shelterContract, $note);
            $this->markMaidAsShelterResident($maidId);

            return true;
        });
    }

    private function createMissingShelterContract(int $maidId, string $note): Contract
    {
        $maid = Maid::query()->lockForUpdate()->findOrFail($maidId);
        $existingShelterContract = Contract::query()
            ->where('maid_id', $maid->id)
            ->where('status', ContractStatusEnum::Shelter->value)
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if ($existingShelterContract) {
            return $existingShelterContract;
        }

        $companyClient = app(ShelterCompanyClientResolver::class)->resolve();
        $entryDate = now()->toDateString();

        $contract = Contract::query()->create([
            'user_id' => $companyClient->id,
            'maid_id' => $maid->id,
            'branch_id' => $maid->branch_id,
            'status' => ContractStatusEnum::Shelter->value,
            'contract_date' => $entryDate,
        ]);

        ContractInfo::query()->create([
            'contract_id' => $contract->id,
            'contract_date' => $entryDate,
            'msaned_number' => '',
            'shelter_id' => $this->defaultShelterId(),
            'wants_to_work' => 'yes',
            'receipt_source' => 'company',
            'is_outside_shelter' => false,
            'is_available_for_instant' => false,
        ]);

        $contract->contract_status_logs()->create([
            'status' => ContractStatusEnum::Shelter->value,
            'date' => now(),
            'notes' => $note . ' وتم إنشاء عقد الإيواء تلقائيًا لعدم وجود عقد سابق.',
        ]);

        $maid->forceFill([
            'maid_accommodation_type' => 2,
            'available_at' => $entryDate,
        ])->save();

        return $contract;
    }

    private function defaultShelterId(): ?int
    {
        return ShelterPlaces::query()->whereKey(1)->value('id')
            ?: ShelterPlaces::query()->orderBy('id')->value('id');
    }

    private function canReturnMaidInside(int $maidId): bool
    {
        $maidStatus = Maid::query()->whereKey($maidId)->value('status');
        if ($maidStatus === null || in_array($maidStatus, ['backout', 'deported'], true)) {
            return false;
        }

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

    private function markMaidAsShelterResident(int $maidId): void
    {
        Maid::query()->whereKey($maidId)->update([
            'maid_accommodation_type' => 2,
        ]);
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

        app(ShelterDocumentMovementService::class)->refreshAdmissionData($contract);

        $contract->contract_status_logs()->create([
            'status' => ContractStatusEnum::Shelter->value,
            'date' => now(),
            'notes' => $note,
        ]);
    }
}
