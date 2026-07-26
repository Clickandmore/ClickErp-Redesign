<?php

namespace App\Services\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\ShelterPlaces;
use App\Models\TravelHistory;

class RentalMaidArrivalService
{
    public function shouldMoveToShelter(Contract $contract): bool
    {
        if (!$contract->maid) {
            return false;
        }

        $contract->maid->loadMissing('jobTypes');

        return $contract->maid->jobTypes->contains(function ($jobType) {
            if ((int) $jobType->id === 2) {
                return true;
            }

            $title = mb_strtolower(trim((string) ($jobType->title ?? '')));

            return str_contains($title, 'rent')
                || str_contains($title, 'إيجار')
                || str_contains($title, 'ايجار')
                || str_contains($title, 'تأجير');
        });
    }

    public function moveToShelter(Contract $contract, TravelHistory $travelHistory): bool
    {
        if (!$this->shouldMoveToShelter($contract)) {
            return false;
        }

        $arrivalDate = $travelHistory->arrival_date ?: now()->toDateString();
        $shelterId = $contract->info?->shelter_id ?: ShelterPlaces::query()->value('id');

        $contract->info()->updateOrCreate(
            ['contract_id' => $contract->id],
            [
                'shelter_id' => $shelterId,
                'receipt_source' => 'company',
                'arrival_date' => $arrivalDate,
                'maid_date_back' => $arrivalDate,
                'wants_to_work' => 'yes',
                'is_available_for_instant' => false,
                'is_outside_shelter' => false,
            ]
        );

        $contract->update(['status' => ContractStatusEnum::Shelter->value]);

        $contract->maid->update([
            'status' => 'available',
            'available' => true,
            'available_at' => $arrivalDate,
            'arrival_date' => $arrivalDate,
            'maid_accommodation_type' => 2,
        ]);

        $contract->contract_status_logs()->create([
            'status' => ContractStatusEnum::Shelter->value,
            'date' => $arrivalDate,
            'notes' => 'تم نقل عاملة الإيجار إلى الإيواء وإتاحتها للتأجير بعد اكتمال رحلة الوصول',
        ]);

        return true;
    }
}
