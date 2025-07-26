<?php

namespace App\Console\Commands;

use App\Models\ingaz\Maid;
use App\Models\Contract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddMaidToUnavailableList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maids:add-to-unavailable
                            {maid-id : ID of the maid to add to unavailable list}
                            {--contract-id= : Contract ID where the change happened}
                            {--reason= : Reason for making unavailable}
                            {--admin-id= : Admin who made the change}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add a maid to unavailable list with reason from contract changes';

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
        $contractId = $this->option('contract-id');
        $reason = $this->option('reason') ?? 'تم تغيير العاملة من عقد';
        $adminId = $this->option('admin-id');

        $this->info("🔍 Adding maid ID {$maidId} to unavailable list...");

        try {
            DB::beginTransaction();

            $maid = Maid::find($maidId);

            if (!$maid) {
                $this->error("❌ Maid with ID {$maidId} not found");
                return 1;
            }

            $this->info("📝 Maid: {$maid->name}");
            $this->info("📝 Current status: {$maid->status}");
            $this->info("📝 Reason: {$reason}");

            // Update maid status
            $maid->update([
                'status' => 'backout',
                'available' => false
            ]);

            // Create or update maid unavailable record
            $unavailableData = [
                'maid_id' => $maidId,
                'reason' => $reason,
                'contract_id' => $contractId,
                'admin_id' => $adminId,
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Check if we need to create a new table for unavailable maids
            if (!DB::getSchemaBuilder()->hasTable('maid_unavailable_records')) {
                $this->info("📝 Creating maid_unavailable_records table...");

                DB::statement('
                    CREATE TABLE maid_unavailable_records (
                        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                        maid_id BIGINT UNSIGNED NOT NULL,
                        reason TEXT,
                        contract_id BIGINT UNSIGNED NULL,
                        admin_id BIGINT UNSIGNED NULL,
                        created_at TIMESTAMP NULL,
                        updated_at TIMESTAMP NULL,
                        INDEX maid_unavailable_records_maid_id_index (maid_id),
                        INDEX maid_unavailable_records_contract_id_index (contract_id),
                        INDEX maid_unavailable_records_admin_id_index (admin_id)
                    )
                ');
            }

            // Insert record
            DB::table('maid_unavailable_records')->insert($unavailableData);

            DB::commit();

            $this->info("✅ Successfully added maid {$maid->name} to unavailable list");

            // Log the action
            \Log::info('Maid added to unavailable list', [
                'maid_id' => $maidId,
                'maid_name' => $maid->name,
                'contract_id' => $contractId,
                'reason' => $reason,
                'admin_id' => $adminId,
                'updated_by' => 'Artisan Command'
            ]);

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error: " . $e->getMessage());
            return 1;
        }
    }
}
