<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ingaz\Maid;
use App\Models\Contract;
use Illuminate\Support\Facades\DB;

class UpdateMaidStatusOnContract extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maids:update-status-on-contract {contract_id : The contract ID to update maid status for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update maid status when a contract is created or updated';

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
        $contractId = $this->argument('contract_id');

        $this->info("🚀 Updating maid status for contract ID: {$contractId}");

        $contract = Contract::find($contractId);

        if (!$contract) {
            $this->error("❌ Contract with ID {$contractId} not found!");
            return 1;
        }

        if (!$contract->maid_id) {
            $this->warn("⚠️  Contract {$contractId} has no maid assigned");
            return 0;
        }

        $maid = Maid::find($contract->maid_id);

        if (!$maid) {
            $this->error("❌ Maid with ID {$contract->maid_id} not found!");
            return 1;
        }

        $this->info("🔍 Maid: {$maid->name} (ID: {$maid->id}, Passport: {$maid->passport_num})");
        $this->info("   Contract status: {$contract->status}");
        $this->info("   Current maid status: {$maid->status}, Available: " . ($maid->available ? 'true' : 'false'));

        // Check if contract is active
        $isActiveContract = !in_array($contract->status, ['back', 'closed', 'completed']);

        if ($isActiveContract) {
            // Contract is active, maid should be reserved
            if ($maid->available || $maid->status === 'متاحة') {
                $maid->update([
                    'available' => false,
                    'status' => 'محجوزة'
                ]);

                $this->info("   ✅ UPDATED: Maid status changed to 'محجوزة', Available: false");
            } else {
                $this->info("   ℹ️  Maid status already correct");
            }
        } else {
            // Contract is not active, check if maid has other active contracts
            $activeContracts = $maid->contracts()->whereNotIn('status', ['back', 'closed', 'completed'])->get();
            $activeOrders = $maid->orders()->whereIn('status', ['pending', 'approved', 'in_progress'])->get();

            if ($activeContracts->count() > 0 || $activeOrders->count() > 0) {
                $this->info("   ℹ️  Maid has other active contracts/orders, keeping reserved status");
            } else {
                // No active contracts or orders, maid can be available
                $maid->update([
                    'available' => true,
                    'status' => 'متاحة'
                ]);

                $this->info("   ✅ UPDATED: Maid status changed to 'متاحة', Available: true");
            }
        }

        $this->info("✅ Maid status update completed for contract {$contractId}");

        return 0;
    }
}
