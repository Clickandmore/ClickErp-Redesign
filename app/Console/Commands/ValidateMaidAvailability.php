<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ingaz\Maid;
use App\Models\Contract;
use Illuminate\Support\Facades\DB;

class ValidateMaidAvailability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maids:validate-availability {--fix : Automatically fix any inconsistencies found}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate maid availability status and check for inconsistencies';

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
        $shouldFix = $this->option('fix');

        $this->info('🔍 Validating maid availability status...');

        $issues = [];
        $fixedCount = 0;

        // Check 1: Maids with active contracts but status is available
        $maidsWithActiveContractsButAvailable = Maid::whereHas('contracts', function($query) {
            $query->whereNotIn('status', ['back', 'closed', 'completed']);
        })->where(function($query) {
            $query->where('available', true)
                  ->orWhere('status', 'available');
        })->get();

        foreach ($maidsWithActiveContractsButAvailable as $maid) {
            $activeContracts = $maid->contracts()->whereNotIn('status', ['back', 'closed', 'completed'])->get();

            $issue = [
                'type' => 'Active contract but available status',
                'maid_id' => $maid->id,
                'maid_name' => $maid->name,
                'passport' => $maid->passport_num,
                'current_status' => $maid->status,
                'available' => $maid->available,
                'active_contracts' => $activeContracts->count(),
                'contract_ids' => $activeContracts->pluck('id')->toArray()
            ];

            $issues[] = $issue;

            if ($shouldFix) {
                $maid->update([
                    'available' => false,
                    'status' => 'reserved'
                ]);
                $fixedCount++;
                $this->info("   ✅ Fixed: {$maid->name} (ID: {$maid->id})");
            }
        }

        // Check 2: Maids with no active contracts but status is reserved
        $maidsWithNoActiveContractsButReserved = Maid::whereDoesntHave('contracts', function($query) {
            $query->whereNotIn('status', ['back', 'closed', 'completed']);
        })->whereDoesntHave('orders', function($query) {
            $query->whereIn('status', ['pending', 'approved', 'in_progress']);
        })->where(function($query) {
            $query->where('available', false)
                  ->orWhere('status', 'reserved');
        })->get();

        foreach ($maidsWithNoActiveContractsButReserved as $maid) {
            $issue = [
                'type' => 'No active contracts but reserved status',
                'maid_id' => $maid->id,
                'maid_name' => $maid->name,
                'passport' => $maid->passport_num,
                'current_status' => $maid->status,
                'available' => $maid->available
            ];

            $issues[] = $issue;

            if ($shouldFix) {
                $maid->update([
                    'available' => true,
                    'status' => 'available'
                ]);
                $fixedCount++;
                $this->info("   ✅ Fixed: {$maid->name} (ID: {$maid->id})");
            }
        }

        // Check 3: Maids with active orders but status is available
        $maidsWithActiveOrdersButAvailable = Maid::whereHas('orders', function($query) {
            $query->whereIn('status', ['pending', 'approved', 'in_progress']);
        })->where(function($query) {
            $query->where('available', true)
                  ->orWhere('status', 'available');
        })->get();

        foreach ($maidsWithActiveOrdersButAvailable as $maid) {
            $activeOrders = $maid->orders()->whereIn('status', ['pending', 'approved', 'in_progress'])->get();

            $issue = [
                'type' => 'Active order but available status',
                'maid_id' => $maid->id,
                'maid_name' => $maid->name,
                'passport' => $maid->passport_num,
                'current_status' => $maid->status,
                'available' => $maid->available,
                'active_orders' => $activeOrders->count(),
                'order_ids' => $activeOrders->pluck('id')->toArray()
            ];

            $issues[] = $issue;

            if ($shouldFix) {
                $maid->update([
                    'available' => false,
                    'status' => 'reserved'
                ]);
                $fixedCount++;
                $this->info("   ✅ Fixed: {$maid->name} (ID: {$maid->id})");
            }
        }

        // Summary
        $this->info('📋 VALIDATION SUMMARY:');
        $this->info("   Total issues found: " . count($issues));

        if (count($issues) > 0) {
            $this->warn('⚠️  Issues found:');

            foreach ($issues as $issue) {
                $this->line("   • {$issue['type']}: {$issue['maid_name']} (ID: {$issue['maid_id']}, Passport: {$issue['passport']})");
                $this->line("     Current: Status={$issue['current_status']}, Available=" . ($issue['available'] ? 'true' : 'false'));

                if (isset($issue['active_contracts'])) {
                    $this->line("     Active contracts: {$issue['active_contracts']} (IDs: " . implode(', ', $issue['contract_ids']) . ")");
                }

                if (isset($issue['active_orders'])) {
                    $this->line("     Active orders: {$issue['active_orders']} (IDs: " . implode(', ', $issue['order_ids']) . ")");
                }

                $this->line('');
            }

            if ($shouldFix) {
                $this->info("✅ Fixed {$fixedCount} issues automatically");
            } else {
                $this->info('💡 To fix issues automatically, run: php artisan maids:validate-availability --fix');
            }
        } else {
            $this->info('✅ No issues found! All maid availability statuses are consistent.');
        }

        return count($issues) > 0 ? 1 : 0;
    }
}
