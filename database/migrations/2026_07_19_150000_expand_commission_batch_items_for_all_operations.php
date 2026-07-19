<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commission_batch_items', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('ingaz_delegation_id');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->unsignedBigInteger('order_id')->nullable()->after('source_id');
            $table->unsignedBigInteger('contract_id')->nullable()->after('order_id');
            $table->string('operation_reference')->nullable()->after('contract_id');
            $table->string('client_name')->nullable()->after('operation_reference');
            $table->date('operation_date')->nullable()->after('client_name');
        });

        DB::table('commission_batch_items')
            ->whereNotNull('ingaz_delegation_id')
            ->update([
                'source_type' => 'ingaz_delegation',
                'source_id' => DB::raw('ingaz_delegation_id'),
            ]);

        Schema::table('commission_batch_items', function (Blueprint $table) {
            $table->unique(['source_type', 'source_id'], 'commission_items_source_unique');
            $table->index('order_id', 'commission_items_order_idx');
            $table->index('contract_id', 'commission_items_contract_idx');
        });
    }

    public function down(): void
    {
        Schema::table('commission_batch_items', function (Blueprint $table) {
            $table->dropUnique('commission_items_source_unique');
            $table->dropIndex('commission_items_order_idx');
            $table->dropIndex('commission_items_contract_idx');
            $table->dropColumn([
                'source_type',
                'source_id',
                'order_id',
                'contract_id',
                'operation_reference',
                'client_name',
                'operation_date',
            ]);
        });
    }
};
