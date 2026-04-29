<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('musaned_transfer_items')) {
            return;
        }

        Schema::table('musaned_transfer_items', function (Blueprint $table) {
            if (Schema::hasColumn('musaned_transfer_items', 'contract_id')) {
                $table->unsignedBigInteger('contract_id')->nullable()->change();
            }

            if (!Schema::hasColumn('musaned_transfer_items', 'ingaz_delegation_id')) {
                $table->foreignId('ingaz_delegation_id')
                    ->nullable()
                    ->after('contract_id')
                    ->constrained('ingaz_delegations')
                    ->cascadeOnDelete();

                $table->index('ingaz_delegation_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('musaned_transfer_items')) {
            return;
        }

        Schema::table('musaned_transfer_items', function (Blueprint $table) {
            if (Schema::hasColumn('musaned_transfer_items', 'ingaz_delegation_id')) {
                $table->dropConstrainedForeignId('ingaz_delegation_id');
            }
        });
    }
};
