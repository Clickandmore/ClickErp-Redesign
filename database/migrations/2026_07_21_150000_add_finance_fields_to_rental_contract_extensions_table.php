<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rental_contract_extensions')) {
            return;
        }

        Schema::table('rental_contract_extensions', function (Blueprint $table) {
            if (!Schema::hasColumn('rental_contract_extensions', 'extension_value')) {
                $table->decimal('extension_value', 12, 2)->nullable()->after('extension_days');
            }
            if (!Schema::hasColumn('rental_contract_extensions', 'payment_type')) {
                $table->string('payment_type')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('rental_contract_extensions', 'payment_amount')) {
                $table->decimal('payment_amount', 12, 2)->nullable()->after('payment_type');
            }
            if (!Schema::hasColumn('rental_contract_extensions', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('payment_amount');
            }
            if (!Schema::hasColumn('rental_contract_extensions', 'receipt_account_id')) {
                $table->unsignedBigInteger('receipt_account_id')->nullable()->after('payment_date');
            }
            if (!Schema::hasColumn('rental_contract_extensions', 'receipt_bond_id')) {
                $table->unsignedBigInteger('receipt_bond_id')->nullable()->after('receipt_account_id');
            }
            if (!Schema::hasColumn('rental_contract_extensions', 'receipt_constraint_id')) {
                $table->unsignedBigInteger('receipt_constraint_id')->nullable()->after('receipt_bond_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('rental_contract_extensions')) {
            return;
        }

        $columns = collect([
            'payment_type',
            'payment_amount',
            'payment_date',
            'receipt_account_id',
            'receipt_bond_id',
            'receipt_constraint_id',
        ])->filter(fn (string $column) => Schema::hasColumn('rental_contract_extensions', $column))->all();

        if ($columns !== []) {
            Schema::table('rental_contract_extensions', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
