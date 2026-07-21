<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('receipt_account_id')->nullable()->after('payment_type');
            $table->unsignedBigInteger('receipt_bond_id')->nullable()->after('receipt_account_id');
            $table->unsignedBigInteger('receipt_constraint_id')->nullable()->after('receipt_bond_id');
            $table->unsignedBigInteger('refund_account_id')->nullable()->after('receipt_constraint_id');
            $table->unsignedBigInteger('refund_bond_id')->nullable()->after('refund_account_id');
            $table->unsignedBigInteger('refund_constraint_id')->nullable()->after('refund_bond_id');

            $table->foreign('receipt_account_id', 'sponsor_transfer_receipt_account_fk')
                ->references('id')->on('fn_accounts')->nullOnDelete();
            $table->foreign('receipt_bond_id', 'sponsor_transfer_receipt_bond_fk')
                ->references('id')->on('fn_bonds')->nullOnDelete();
            $table->foreign('receipt_constraint_id', 'sponsor_transfer_receipt_constraint_fk')
                ->references('id')->on('fn_constraints')->nullOnDelete();
            $table->foreign('refund_account_id', 'sponsor_transfer_refund_account_fk')
                ->references('id')->on('fn_accounts')->nullOnDelete();
            $table->foreign('refund_bond_id', 'sponsor_transfer_refund_bond_fk')
                ->references('id')->on('fn_bonds')->nullOnDelete();
            $table->foreign('refund_constraint_id', 'sponsor_transfer_refund_constraint_fk')
                ->references('id')->on('fn_constraints')->nullOnDelete();
        });

        Schema::table('fn_bonds', function (Blueprint $table) {
            $table->unsignedBigInteger('sponsorship_transfer_id')->nullable()->after('rental_contract_id');
            $table->foreign('sponsorship_transfer_id', 'fn_bonds_sponsor_transfer_fk')
                ->references('id')->on('sponsorship_transfers')->nullOnDelete();
            $table->index(['sponsorship_transfer_id', 'type'], 'fn_bonds_sponsor_transfer_type_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fn_bonds', function (Blueprint $table) {
            $table->dropForeign('fn_bonds_sponsor_transfer_fk');
            $table->dropIndex('fn_bonds_sponsor_transfer_type_idx');
            $table->dropColumn('sponsorship_transfer_id');
        });

        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->dropForeign('sponsor_transfer_receipt_account_fk');
            $table->dropForeign('sponsor_transfer_receipt_bond_fk');
            $table->dropForeign('sponsor_transfer_receipt_constraint_fk');
            $table->dropForeign('sponsor_transfer_refund_account_fk');
            $table->dropForeign('sponsor_transfer_refund_bond_fk');
            $table->dropForeign('sponsor_transfer_refund_constraint_fk');
            $table->dropColumn([
                'receipt_account_id', 'receipt_bond_id', 'receipt_constraint_id',
                'refund_account_id', 'refund_bond_id', 'refund_constraint_id',
            ]);
        });
    }
};
