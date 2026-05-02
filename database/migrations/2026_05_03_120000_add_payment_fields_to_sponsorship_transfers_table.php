<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('sponsorship_transfers', 'payment_type')) {
                $table->string('payment_type')->nullable()->after('remaining_amount');
            }

            if (!Schema::hasColumn('sponsorship_transfers', 'payment_status')) {
                $table->string('payment_status')->nullable()->after('payment_type');
            }

            if (!Schema::hasColumn('sponsorship_transfers', 'payment_receipt_path')) {
                $table->string('payment_receipt_path')->nullable()->after('payment_status');
            }

            if (!Schema::hasColumn('sponsorship_transfers', 'attachments')) {
                $table->json('attachments')->nullable()->after('payment_receipt_path');
            }

            if (!Schema::hasColumn('sponsorship_transfers', 'payment_confirmed_at')) {
                $table->timestamp('payment_confirmed_at')->nullable()->after('attachments');
            }

            if (!Schema::hasColumn('sponsorship_transfers', 'payment_confirmed_by')) {
                $table->unsignedBigInteger('payment_confirmed_by')->nullable()->after('payment_confirmed_at');
                $table->foreign('payment_confirmed_by')
                    ->references('id')
                    ->on('admins')
                    ->nullOnDelete();
            }

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
            if (Schema::hasColumn('sponsorship_transfers', 'payment_confirmed_by')) {
                $table->dropForeign(['payment_confirmed_by']);
            }

            $columns = [
                'payment_confirmed_by',
                'payment_confirmed_at',
                'attachments',
                'payment_receipt_path',
                'payment_status',
                'payment_type',
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
