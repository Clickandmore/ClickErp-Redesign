<?php

namespace App\Console\Commands;

use App\Models\ingaz\Maid;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateMaidStatusesToEnglish extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maids:update-statuses-to-english
                            {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update all maid statuses from Arabic to English';

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
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('🔍 Starting maid status update to English...');

        try {
            DB::beginTransaction();

            // Status mapping from Arabic to English
            $statusMapping = [
                'متاحة' => 'available',
                'محجوزة' => 'reserved',
                'تم التعاقد' => 'contracted',
                'داخل الايواء' => 'in_accommodation',
                'باك اوت' => 'backout',
                'غير متاحة' => 'unavailable'
            ];

            $updatedCount = 0;
            $totalCount = 0;

            foreach ($statusMapping as $arabicStatus => $englishStatus) {
                $maids = Maid::where('status', $arabicStatus)->get();
                $count = $maids->count();
                $totalCount += $count;

                if ($count > 0) {
                    $this->info("📝 Found {$count} maids with status '{$arabicStatus}' -> '{$englishStatus}'");

                    if (!$dryRun) {
                        Maid::where('status', $arabicStatus)->update(['status' => $englishStatus]);
                        $this->info("✅ Updated {$count} maids from '{$arabicStatus}' to '{$englishStatus}'");
                    } else {
                        $this->info("🔍 Would update {$count} maids from '{$arabicStatus}' to '{$englishStatus}'");
                    }
                    $updatedCount += $count;
                }
            }

            if ($updatedCount === 0) {
                $this->info("ℹ️ No maids found with Arabic statuses to update");
            } else {
                if (!$dryRun) {
                    $this->info("✅ Successfully updated {$updatedCount} maids out of {$totalCount} total");
                } else {
                    $this->info("🔍 Would update {$updatedCount} maids out of {$totalCount} total");
                }
            }

            DB::commit();

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
    }
}
