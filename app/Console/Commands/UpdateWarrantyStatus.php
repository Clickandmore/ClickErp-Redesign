<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;

class UpdateWarrantyStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:update-warranty-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update warranty status for arrival contracts and close them after warranty expiry';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting warranty status update...');
        // جلب العقود في مرحلة الوصول أو المكتملة التي قد تحتاج إعادة تقييم
        $query = Contract::whereIn('status', [
                ContractStatusEnum::Arrival->value,
                ContractStatusEnum::Completed->value,
            ])
            ->whereIn('type', ['recruitment', 'maid']);

        $updatedCount = 0;
        $expiredCount = 0;

        $query->chunkById(100, function ($contracts) use (&$updatedCount, &$expiredCount): void {
            foreach ($contracts as $contract) {
                $contract->calculateWarrantyStatus();
                $contract->refresh();
                $updatedCount++;

                if ($contract->warranty_status === 'expired') {
                    $expiredCount++;
                }
            }
        });

        $this->info("Warranty status update completed!");
        $this->info("Total contracts processed: {$updatedCount}");
        $this->info("Expired warranties: {$expiredCount}");

        return 0;
    }
}
