<?php

namespace App\Models;

/** Source details reached only through transfer-specific authorization. */
class TransferSourceContract extends Contract
{
    protected $table = 'contracts';

    public function getMorphClass()
    {
        return (new Contract)->getMorphClass();
    }

    public function getForeignKey()
    {
        return (new Contract)->getForeignKey();
    }

    public function info()
    {
        return parent::info()->withoutBranchScope();
    }

    public function maid()
    {
        return parent::maid()->withoutBranchScope();
    }

    public function escapeReports()
    {
        return $this->hasMany(ContractEscapeReport::class, 'contract_id');
    }

    public function scopeInsideShelter($query)
    {
        return parent::scopeInsideShelter($query)
            ->whereDoesntHave('maid.rentalContracts', fn ($q) => $q->withoutBranchScope()
                ->whereIn('status', \App\Services\RentalShelterPresenceService::OUTSIDE_RENTAL_STATUSES)->holdingMaid())
            ->whereDoesntHave('sponsorshipTransfers', fn ($q) => $q->withoutBranchScope()
                ->whereNotIn('status', ['failed', 'rejected', 'cancelled', 'transferred', 'completed']));
    }
}
