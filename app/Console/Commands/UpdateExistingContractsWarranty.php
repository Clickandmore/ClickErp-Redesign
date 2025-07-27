<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contract;
use App\Models\TravelHistory;
use Carbon\Carbon;

class UpdateExistingContractsWarranty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:update-existing-warranty';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update warranty status for existing completed contracts';

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
        $this->info('Starting warranty status update for existing contracts...');

        // جلب العقود المكتملة
        $contracts = Contract::where('status', 'completed')->get();

        $updatedCount = 0;
        $noTravelCount = 0;

        foreach ($contracts as $contract) {
            $this->info("Processing contract ID: {$contract->id}");

            // حساب حالة الضمان تلقائياً
            $contract->calculateWarrantyStatus();

            $warrantyStatus = $contract->warranty_status ?? 'not_applicable';

            switch ($warrantyStatus) {
                case 'active':
                    $remainingDays = $contract->warranty_remaining_days ?? 0;
                    $this->info("  - Warranty: Active ({$remainingDays} days remaining)");
                    break;
                case 'expired':
                    $this->warn("  - Warranty: Expired");
                    break;
                case 'not_applicable':
                    $this->warn("  - Warranty: Not applicable (no travel history)");
                    $noTravelCount++;
                    break;
                default:
                    $this->warn("  - Warranty: Unknown status");
                    break;
            }

            $updatedCount++;
        }

        $this->info("Warranty status update completed!");
        $this->info("Total contracts processed: {$updatedCount}");
        $this->info("Contracts without travel history: {$noTravelCount}");

        return 0;
    }
}
