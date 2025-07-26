<?php

namespace App\Console\Commands;

use App\Models\ingaz\Maid;
use Illuminate\Console\Command;

class SetMaidStatusToUnavailable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maids:set-unavailable
                            {maid-id : ID of the maid to set as unavailable}
                            {--reason= : Reason for setting as unavailable}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set a specific maid status to unavailable';

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
        $maidId = $this->argument('maid-id');
        $reason = $this->option('reason') ?? 'Manual update';

        $this->info("🔍 Setting maid ID {$maidId} to unavailable status...");

        try {
            $maid = Maid::find($maidId);

            if (!$maid) {
                $this->error("❌ Maid with ID {$maidId} not found");
                return 1;
            }

            $this->info("📝 Maid: {$maid->name}");
            $this->info("📝 Current status: {$maid->status}");
            $this->info("📝 Current available: " . ($maid->available ? 'Yes' : 'No'));
            $this->info("📝 Reason: {$reason}");

            if ($this->confirm('Are you sure you want to set this maid as unavailable?') || $this->option('no-interaction')) {
                $maid->update([
                    'status' => 'backout',
                    'available' => false
                ]);

                $this->info("✅ Successfully updated maid {$maid->name} to unavailable status");

                // Log the action
                \Log::info('Maid status manually set to unavailable', [
                    'maid_id' => $maidId,
                    'maid_name' => $maid->name,
                    'reason' => $reason,
                    'updated_by' => 'Artisan Command'
                ]);

                return 0;
            } else {
                $this->info("❌ Operation cancelled");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
    }
}
