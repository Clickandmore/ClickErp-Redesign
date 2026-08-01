<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Services\RentalShelterPresenceService;
use Illuminate\Console\Command;

class ReconcileRentalShelterPresence extends Command
{
    protected $signature = 'shelter:reconcile-rental-presence
        {--apply : Apply safe corrections; without this option the command only previews}
        {--contract= : Limit the reconciliation to one shelter contract ID}';

    protected $description = 'Preview or repair stale rental-delivery flags on shelter contracts';

    public function handle(RentalShelterPresenceService $service): int
    {
        $apply = (bool) $this->option('apply');
        $contractId = $this->option('contract');
        $safeQuery = $service->staleRentalOutsideQuery();
        $reviewQuery = $service->staleRentalOutsideQuery(false);

        if ($contractId !== null) {
            $safeQuery->whereKey((int) $contractId);
            $reviewQuery->whereKey((int) $contractId);
        }

        $safeContracts = $safeQuery
            ->with(['maid:id,name,passport_num,status,available,maid_accommodation_type', 'info'])
            ->orderBy('id')
            ->get();
        $reviewCount = (clone $reviewQuery)
            ->whereNotIn('contracts.id', $safeContracts->pluck('id'))
            ->count();

        if ($safeContracts->isEmpty()) {
            $this->info('No safe stale rental shelter records were found.');
        } else {
            $this->table(
                ['Contract', 'Maid', 'Passport', 'Old rental reference'],
                $safeContracts->map(fn (Contract $contract) => [
                    $contract->id,
                    $contract->maid?->name,
                    $contract->maid?->passport_num,
                    $contract->info?->outside_shelter_reference_id,
                ])->all()
            );
        }

        if (!$apply) {
            $this->warn("Preview only: {$safeContracts->count()} safe record(s) can be repaired; {$reviewCount} record(s) require manual review.");
            $this->line('Run again with --apply to repair only the safe records.');

            return self::SUCCESS;
        }

        $updated = 0;
        foreach ($safeContracts as $contract) {
            if ($service->reconcileContract(
                $contract,
                'تصحيح آلي: إعادة العاملة إلى داخل الإيواء بعد اكتشاف علامة خروج إيجار قديمة دون عقد إيجار نشط'
            )) {
                $updated++;
            }
        }

        $this->info("Repaired {$updated} safe stale rental shelter record(s).");
        if ($reviewCount > 0) {
            $this->warn("{$reviewCount} record(s) were not changed because their maid status requires manual review.");
        }

        return self::SUCCESS;
    }
}
