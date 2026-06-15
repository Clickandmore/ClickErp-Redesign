<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('financial_requests')) {
            return;
        }

        Schema::table('financial_requests', function (Blueprint $table) {
            if (! $this->indexExists('financial_requests', 'fr_type_status_priority_idx')) {
                $table->index(['type', 'status', 'priority'], 'fr_type_status_priority_idx');
            }

            if (! $this->indexExists('financial_requests', 'fr_contract_client_creator_idx')) {
                $table->index(['contract_number', 'client_identity', 'created_by_id'], 'fr_contract_client_creator_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('financial_requests')) {
            return;
        }

        Schema::table('financial_requests', function (Blueprint $table) {
            if ($this->indexExists('financial_requests', 'fr_contract_client_creator_idx')) {
                $table->dropIndex('fr_contract_client_creator_idx');
            }

            if ($this->indexExists('financial_requests', 'fr_type_status_priority_idx')) {
                $table->dropIndex('fr_type_status_priority_idx');
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))->isNotEmpty();
    }
};
