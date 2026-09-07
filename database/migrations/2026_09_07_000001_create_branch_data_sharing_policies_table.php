<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branch_data_sharing_policies')) {
            Schema::create('branch_data_sharing_policies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->string('entity_type', 40);
                $table->string('access_level', 20)->default('view');
                $table->timestamps();

                $table->unique(['branch_id', 'entity_type'], 'branch_data_sharing_policy_unique');
                $table->index(['entity_type', 'access_level'], 'branch_data_sharing_policy_lookup');
            });
        }

        $this->preserveExistingCompanySharing();
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_data_sharing_policies');
    }

    /**
     * The replaced company-wide switches exposed every source branch. Seed one
     * source policy per existing branch so deploying this migration does not
     * unexpectedly hide client or external-office data.
     */
    private function preserveExistingCompanySharing(): void
    {
        if (! Schema::hasTable('new_settings') || ! Schema::hasTable('branches')) {
            return;
        }

        $raw = DB::table('new_settings')->where('key', 'branch_sharing_settings')->value('value');
        $settings = is_string($raw) ? json_decode($raw, true) : null;
        if (! is_array($settings)) {
            return;
        }

        $entities = [];
        if (filter_var($settings['share_clients'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $entities[] = 'clients';
        }
        if (filter_var($settings['share_external_offices'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $entities[] = 'external_offices';
        }
        if ($entities === []) {
            return;
        }

        $now = now();
        $rows = [];
        foreach (DB::table('branches')->pluck('id') as $branchId) {
            foreach ($entities as $entityType) {
                $rows[] = [
                    'branch_id' => $branchId,
                    'entity_type' => $entityType,
                    'access_level' => 'view',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('branch_data_sharing_policies')->insertOrIgnore($rows);
    }
};
