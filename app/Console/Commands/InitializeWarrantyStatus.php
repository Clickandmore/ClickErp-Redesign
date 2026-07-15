<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;

class InitializeWarrantyStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:initialize-warranty';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize warranty status for arrival and completed contracts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Initializing warranty status for arrival and completed contracts...');
        // نفس نطاق شاشة العقود المكتملة: عقود الاستقدام في الوصول أو المكتملة فقط.
        $contracts = Contract::whereIn('status', [
            ContractStatusEnum::Arrival->value,
            ContractStatusEnum::Completed->value,
        ])->whereIn('type', ['recruitment', 'maid'])->get();

        $activeCount = 0;
        $expiredCount = 0;
        $notApplicableCount = 0;

        foreach ($contracts as $contract) {
            $contract->calculateWarrantyStatus();
            $contract->refresh();

            match ($contract->warranty_status) {
                'active' => $activeCount++,
                'expired' => $expiredCount++,
                default => $notApplicableCount++,
            };
        }

        $totalProcessed = $activeCount + $expiredCount + $notApplicableCount;

        $this->info("Warranty status initialization completed!");
        $this->info("Total contracts processed: {$totalProcessed}");
        $this->info("Active warranties: {$activeCount}");
        $this->info("Expired warranties: {$expiredCount}");
        $this->info("Not applicable: {$notApplicableCount}");

        return 0;
    }
}
