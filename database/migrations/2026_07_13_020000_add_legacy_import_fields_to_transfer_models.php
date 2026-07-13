<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->string('external_source', 50)->nullable()->after('id');
            $table->string('external_operation_number', 100)->nullable()->after('external_source');
            $table->unique(
                ['external_source', 'external_operation_number'],
                'sponsorship_transfers_external_operation_unique'
            );
        });

        Schema::table('transfer_service_contracts', function (Blueprint $table) {
            $table->string('external_source', 50)->nullable()->after('id');
            $table->string('external_operation_number', 100)->nullable()->after('external_source');
            $table->unsignedBigInteger('legacy_worker_number')->nullable()->index()->after('external_operation_number');
            $table->unsignedBigInteger('legacy_invoice_number')->nullable()->index()->after('legacy_worker_number');
            $table->decimal('trial_cost', 12, 2)->nullable()->after('legacy_invoice_number');
            $table->string('old_sponsor_compensation_type')->nullable()->after('trial_cost');
            $table->decimal('old_sponsor_compensation_amount', 12, 2)->nullable()->after('old_sponsor_compensation_type');
            $table->string('worker_replacement_status')->nullable()->after('old_sponsor_compensation_amount');
            $table->string('profession_change_status')->nullable()->after('worker_replacement_status');
            $table->string('legacy_status')->nullable()->after('profession_change_status');
            $table->string('legacy_trial_status')->nullable()->after('legacy_status');
            $table->string('musaned_status')->nullable()->after('legacy_trial_status');
            $table->string('conversion_status')->nullable()->after('musaned_status');
            $table->decimal('commission_amount', 12, 2)->nullable()->after('conversion_status');
            $table->decimal('refundable_tax_amount', 12, 2)->nullable()->after('commission_amount');
            $table->date('conversion_date')->nullable()->after('refundable_tax_amount');
            $table->string('arrival_warranty_status')->nullable()->after('conversion_date');
            $table->string('delivery_warranty_status')->nullable()->after('arrival_warranty_status');
            $table->string('source_created_by')->nullable()->after('delivery_warranty_status');
            $table->string('source_marketer')->nullable()->after('source_created_by');
            $table->timestamp('source_created_at')->nullable()->after('source_marketer');
            $table->json('legacy_payload')->nullable()->after('source_created_at');
            $table->unique(
                ['external_source', 'external_operation_number'],
                'transfer_service_contracts_external_operation_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('transfer_service_contracts', function (Blueprint $table) {
            $table->dropUnique('transfer_service_contracts_external_operation_unique');
            $table->dropIndex(['legacy_worker_number']);
            $table->dropIndex(['legacy_invoice_number']);
            $table->dropColumn([
                'external_source',
                'external_operation_number',
                'legacy_worker_number',
                'legacy_invoice_number',
                'trial_cost',
                'old_sponsor_compensation_type',
                'old_sponsor_compensation_amount',
                'worker_replacement_status',
                'profession_change_status',
                'legacy_status',
                'legacy_trial_status',
                'musaned_status',
                'conversion_status',
                'commission_amount',
                'refundable_tax_amount',
                'conversion_date',
                'arrival_warranty_status',
                'delivery_warranty_status',
                'source_created_by',
                'source_marketer',
                'source_created_at',
                'legacy_payload',
            ]);
        });

        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->dropUnique('sponsorship_transfers_external_operation_unique');
            $table->dropColumn(['external_source', 'external_operation_number']);
        });
    }
};
