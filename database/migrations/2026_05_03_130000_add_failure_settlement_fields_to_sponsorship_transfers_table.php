<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('sponsorship_transfers', 'failure_settlement_type')) {
                $table->string('failure_settlement_type')->nullable()->after('failure_reason');
            }

            if (!Schema::hasColumn('sponsorship_transfers', 'failure_settlement_amount')) {
                $table->decimal('failure_settlement_amount', 12, 2)->default(0)->after('failure_settlement_type');
            }

            if (!Schema::hasColumn('sponsorship_transfers', 'failure_clear_remaining_amount')) {
                $table->decimal('failure_clear_remaining_amount', 12, 2)->default(0)->after('failure_settlement_amount');
            }

            if (!Schema::hasColumn('sponsorship_transfers', 'failure_client_iban')) {
                $table->string('failure_client_iban', 34)->nullable()->after('failure_clear_remaining_amount');
            }

            if (!Schema::hasColumn('sponsorship_transfers', 'failure_client_iban_attachment_path')) {
                $table->string('failure_client_iban_attachment_path')->nullable()->after('failure_client_iban');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $columns = [
                'failure_client_iban_attachment_path',
                'failure_client_iban',
                'failure_clear_remaining_amount',
                'failure_settlement_amount',
                'failure_settlement_type',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('sponsorship_transfers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
