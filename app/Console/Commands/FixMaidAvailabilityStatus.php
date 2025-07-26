<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ingaz\Maid;
use App\Models\Contract;
use Illuminate\Support\Facades\DB;

class FixMaidAvailabilityStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maids:fix-availability {--dry-run : Show what would be changed without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix maid availability status by updating maids with active contracts to reserved status';

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
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 Running in DRY RUN mode - no changes will be made');
        }

        $this->info('🚀 Starting maid availability status fix...');

        // Get all maids with active contracts
        $maidsWithActiveContracts = Maid::whereHas('contracts', function($query) {
            $query->whereNotIn('status', ['back', 'closed', 'completed']);
        })->get();

        $this->info("📊 Found {$maidsWithActiveContracts->count()} maids with active contracts");

        $updatedCount = 0;
        $alreadyCorrectCount = 0;

        foreach ($maidsWithActiveContracts as $maid) {
            $activeContracts = $maid->contracts()->whereNotIn('status', ['back', 'closed', 'completed'])->get();

            $this->line("🔍 Checking maid: {$maid->name} (ID: {$maid->id}, Passport: {$maid->passport_num})");
            $this->line("   Current status: {$maid->status}, Available: " . ($maid->available ? 'true' : 'false'));
            $this->line("   Active contracts: {$activeContracts->count()}");

            // Check if maid should be reserved
            if ($maid->available && $maid->status === 'available' && $activeContracts->count() > 0) {
                if (!$isDryRun) {
                    // Update maid status to reserved
                    $maid->update([
                        'available' => false,
                        'status' => 'reserved'
                    ]);

                    $this->info("   ✅ UPDATED: Status changed to 'reserved', Available: false");
                    $updatedCount++;
                } else {
                    $this->warn("   ⚠️  WOULD UPDATE: Status would change to 'reserved', Available: false");
                    $updatedCount++;
                }
            } else {
                $this->line("   ℹ️  SKIPPED: Status already correct or no active contracts");
                $alreadyCorrectCount++;
            }

            $this->line('');
        }

        // Also check maids with active orders
        $maidsWithActiveOrders = Maid::whereHas('orders', function($query) {
            $query->whereIn('status', ['pending', 'approved', 'in_progress']);
        })->get();

        $this->info("📊 Found {$maidsWithActiveOrders->count()} maids with active orders");

        foreach ($maidsWithActiveOrders as $maid) {
            $activeOrders = $maid->orders()->whereIn('status', ['pending', 'approved', 'in_progress'])->get();

            $this->line("🔍 Checking maid with active orders: {$maid->name} (ID: {$maid->id}, Passport: {$maid->passport_num})");
            $this->line("   Current status: {$maid->status}, Available: " . ($maid->available ? 'true' : 'false'));
            $this->line("   Active orders: {$activeOrders->count()}");

            // Check if maid should be reserved
            if ($maid->available && $maid->status === 'available' && $activeOrders->count() > 0) {
                if (!$isDryRun) {
                    // Update maid status to reserved
                    $maid->update([
                        'available' => false,
                        'status' => 'reserved'
                    ]);

                    $this->info("   ✅ UPDATED: Status changed to 'reserved', Available: false");
                    $updatedCount++;
                } else {
                    $this->warn("   ⚠️  WOULD UPDATE: Status would change to 'reserved', Available: false");
                    $updatedCount++;
                }
            } else {
                $this->line("   ℹ️  SKIPPED: Status already correct or no active orders");
                $alreadyCorrectCount++;
            }

            $this->line('');
        }

        // Summary
        $this->info('📋 SUMMARY:');
        $this->info("   Total maids checked: " . ($maidsWithActiveContracts->count() + $maidsWithActiveOrders->count()));
        $this->info("   Maids updated: {$updatedCount}");
        $this->info("   Maids already correct: {$alreadyCorrectCount}");

        if ($isDryRun) {
            $this->warn('⚠️  This was a DRY RUN. No changes were made.');
            $this->info('💡 To apply changes, run: php artisan maids:fix-availability');
        } else {
            $this->info('✅ Maid availability status fix completed successfully!');
        }

        return 0;
    }
}
