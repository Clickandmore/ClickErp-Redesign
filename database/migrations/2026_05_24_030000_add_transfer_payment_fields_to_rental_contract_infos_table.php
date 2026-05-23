<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contract_infos', function (Blueprint $table) {
            if (!Schema::hasColumn('rental_contract_infos', 'sponsorship_transfer_payment_type')) {
                $table->string('sponsorship_transfer_payment_type')->nullable()->after('sponsorship_transfer_remaining_amount');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'sponsorship_transfer_payment_status')) {
                $table->string('sponsorship_transfer_payment_status')->nullable()->after('sponsorship_transfer_payment_type');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'sponsorship_transfer_payment_receipt_path')) {
                $table->string('sponsorship_transfer_payment_receipt_path')->nullable()->after('sponsorship_transfer_payment_status');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'sponsorship_transfer_attachments')) {
                $table->json('sponsorship_transfer_attachments')->nullable()->after('sponsorship_transfer_payment_receipt_path');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'sponsorship_transfer_payment_confirmed_at')) {
                $table->timestamp('sponsorship_transfer_payment_confirmed_at')->nullable()->after('sponsorship_transfer_attachments');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'sponsorship_transfer_payment_confirmed_by')) {
                $table->unsignedBigInteger('sponsorship_transfer_payment_confirmed_by')
                    ->nullable()
                    ->after('sponsorship_transfer_payment_confirmed_at');
            }
        });

        if (
            Schema::hasColumn('rental_contract_infos', 'sponsorship_transfer_payment_confirmed_by')
            && !$this->foreignKeyExists('rental_contract_infos', 'rci_st_pay_by_fk')
        ) {
            Schema::table('rental_contract_infos', function (Blueprint $table) {
                $table->foreign('sponsorship_transfer_payment_confirmed_by', 'rci_st_pay_by_fk')
                    ->references('id')
                    ->on('admins')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('rental_contract_infos', function (Blueprint $table) {
            if ($this->foreignKeyExists('rental_contract_infos', 'rci_st_pay_by_fk')) {
                $table->dropForeign('rci_st_pay_by_fk');
            }

            foreach ([
                'sponsorship_transfer_payment_confirmed_by',
                'sponsorship_transfer_payment_confirmed_at',
                'sponsorship_transfer_attachments',
                'sponsorship_transfer_payment_receipt_path',
                'sponsorship_transfer_payment_status',
                'sponsorship_transfer_payment_type',
            ] as $column) {
                if (Schema::hasColumn('rental_contract_infos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $database = DB::getDatabaseName();

        return !empty(DB::select(
            'select constraint_name from information_schema.table_constraints where table_schema = ? and table_name = ? and constraint_name = ? and constraint_type = ?',
            [$database, $table, $constraint, 'FOREIGN KEY']
        ));
    }
};
