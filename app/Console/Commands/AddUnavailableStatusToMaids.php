<?php

namespace App\Console\Commands;

use App\Models\ingaz\Maid;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddUnavailableStatusToMaids extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maids:add-unavailable-status
                            {--maid-id= : ID of specific maid to update}
                            {--all : Update all maids that should be unavailable}
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add unavailable status to maids based on various conditions';

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
        $this->info('🔍 Starting maid status update process...');

        $maidId = $this->option('maid-id');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        try {
            if ($maidId) {
                $this->updateSpecificMaid($maidId, $dryRun);
            } elseif ($all) {
                $this->updateAllUnavailableMaids($dryRun);
            } else {
                $this->error('❌ Please specify either --maid-id or --all option');
                return 1;
            }

            $this->info('✅ Process completed successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Update a specific maid
     */
    private function updateSpecificMaid($maidId, $dryRun)
    {
        $maid = Maid::find($maidId);

        if (!$maid) {
            $this->error("❌ Maid with ID {$maidId} not found");
            return;
        }

        $this->info("🔍 Processing maid: {$maid->name} (ID: {$maid->id})");

        $shouldBeUnavailable = $this->shouldMaidBeUnavailable($maid);

        if ($shouldBeUnavailable) {
            $this->info("📝 Maid should be unavailable. Current status: {$maid->status}, Available: " . ($maid->available ? 'Yes' : 'No'));

            if (!$dryRun) {
                $maid->update([
                    'status' => 'backout',
                    'available' => false
                ]);
                $this->info("✅ Updated maid {$maid->name} to unavailable");
            } else {
                $this->info("🔍 Would update maid {$maid->name} to unavailable");
            }
        } else {
            $this->info("ℹ️ Maid {$maid->name} is already properly configured");
        }
    }

    /**
     * Update all maids that should be unavailable
     */
    private function updateAllUnavailableMaids($dryRun)
    {
        $this->info('🔍 Finding maids that should be unavailable...');

        // Get all maids
        $maids = Maid::with(['contracts', 'orders'])->get();

        $updatedCount = 0;
        $totalCount = $maids->count();

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        foreach ($maids as $maid) {
            $shouldBeUnavailable = $this->shouldMaidBeUnavailable($maid);

            if ($shouldBeUnavailable) {
                if (!$dryRun) {
                    $maid->update([
                        'status' => 'backout',
                        'available' => false
                    ]);
                }
                $updatedCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        if ($dryRun) {
            $this->info("🔍 Found {$updatedCount} maids that would be updated to unavailable");
        } else {
            $this->info("✅ Updated {$updatedCount} maids to unavailable status");
        }
    }

    /**
     * Check if a maid should be unavailable
     */
    private function shouldMaidBeUnavailable($maid)
    {
        // Check if maid has active contracts
        $hasActiveContracts = $maid->contracts()
            ->whereIn('status', [
                'new', 'msaned', 'tafwed', 'taffez', 'tazker', 'arrival'
            ])
            ->exists();

        // Check if maid has active orders
        $hasActiveOrders = $maid->orders()
            ->whereIn('status', [
                'new', 'under_process', 'contracted'
            ])
            ->exists();

        // Maid should be unavailable if:
        // 1. Has active contracts or orders
        // 2. Current status is not 'backout' or available is true
        return ($hasActiveContracts || $hasActiveOrders) &&
               ($maid->status !== 'backout' || $maid->available === true);
    }
}
