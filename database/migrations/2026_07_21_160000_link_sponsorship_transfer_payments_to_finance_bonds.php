<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sponsorship_transfers')) {
            return;
        }

        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            if (!Schema::hasColumn('sponsorship_transfers', 'receipt_account_id')) {
                $table->unsignedBigInteger('receipt_account_id')->nullable()->after('payment_type');
            }

            if (!Schema::hasColumn('sponsorship_transfers', 'receipt_bond_id')) {
                $table->unsignedBigInteger('receipt_bond_id')->nullable()->after('receipt_account_id');
            }

            if (!Schema::hasColumn('sponsorship_transfers', 'receipt_constraint_id')) {
                $table->unsignedBigInteger('receipt_constraint_id')->nullable()->after('receipt_bond_id');
            }

            if (!Schema::hasColumn('sponsorship_transfers', 'refund_account_id')) {
                $table->unsignedBigInteger('refund_account_id')->nullable()->after('receipt_constraint_id');
            }

            if (!Schema::hasColumn('sponsorship_transfers', 'refund_bond_id')) {
                $table->unsignedBigInteger('refund_bond_id')->nullable()->after('refund_account_id');
            }

            if (!Schema::hasColumn('sponsorship_transfers', 'refund_constraint_id')) {
                $table->unsignedBigInteger('refund_constraint_id')->nullable()->after('refund_bond_id');
            }
        });

        if (Schema::hasTable('fn_accounts') && Schema::hasTable('fn_bonds') && Schema::hasTable('fn_constraints')) {
            Schema::table('sponsorship_transfers', function (Blueprint $table) {
                if (!$this->hasForeignKey('sponsorship_transfers', 'sponsor_transfer_receipt_account_fk')) {
                    $table->foreign('receipt_account_id', 'sponsor_transfer_receipt_account_fk')
                        ->references('id')->on('fn_accounts')->nullOnDelete();
                }

                if (!$this->hasForeignKey('sponsorship_transfers', 'sponsor_transfer_receipt_bond_fk')) {
                    $table->foreign('receipt_bond_id', 'sponsor_transfer_receipt_bond_fk')
                        ->references('id')->on('fn_bonds')->nullOnDelete();
                }

                if (!$this->hasForeignKey('sponsorship_transfers', 'sponsor_transfer_receipt_constraint_fk')) {
                    $table->foreign('receipt_constraint_id', 'sponsor_transfer_receipt_constraint_fk')
                        ->references('id')->on('fn_constraints')->nullOnDelete();
                }

                if (!$this->hasForeignKey('sponsorship_transfers', 'sponsor_transfer_refund_account_fk')) {
                    $table->foreign('refund_account_id', 'sponsor_transfer_refund_account_fk')
                        ->references('id')->on('fn_accounts')->nullOnDelete();
                }

                if (!$this->hasForeignKey('sponsorship_transfers', 'sponsor_transfer_refund_bond_fk')) {
                    $table->foreign('refund_bond_id', 'sponsor_transfer_refund_bond_fk')
                        ->references('id')->on('fn_bonds')->nullOnDelete();
                }

                if (!$this->hasForeignKey('sponsorship_transfers', 'sponsor_transfer_refund_constraint_fk')) {
                    $table->foreign('refund_constraint_id', 'sponsor_transfer_refund_constraint_fk')
                        ->references('id')->on('fn_constraints')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('fn_bonds') && !Schema::hasColumn('fn_bonds', 'sponsorship_transfer_id')) {
            Schema::table('fn_bonds', function (Blueprint $table) {
                $table->unsignedBigInteger('sponsorship_transfer_id')->nullable()->after('rental_contract_id');
                $table->foreign('sponsorship_transfer_id', 'fn_bonds_sponsor_transfer_fk')
                    ->references('id')->on('sponsorship_transfers')->nullOnDelete();
                $table->index(['sponsorship_transfer_id', 'type'], 'fn_bonds_sponsor_transfer_type_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fn_bonds') && Schema::hasColumn('fn_bonds', 'sponsorship_transfer_id')) {
            Schema::table('fn_bonds', function (Blueprint $table) {
                if ($this->hasForeignKey('fn_bonds', 'fn_bonds_sponsor_transfer_fk')) {
                    $table->dropForeign('fn_bonds_sponsor_transfer_fk');
                }

                $table->dropIndex('fn_bonds_sponsor_transfer_type_idx');
                $table->dropColumn('sponsorship_transfer_id');
            });
        }

        if (!Schema::hasTable('sponsorship_transfers')) {
            return;
        }

        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            foreach ([
                'sponsor_transfer_receipt_account_fk',
                'sponsor_transfer_receipt_bond_fk',
                'sponsor_transfer_receipt_constraint_fk',
                'sponsor_transfer_refund_account_fk',
                'sponsor_transfer_refund_bond_fk',
                'sponsor_transfer_refund_constraint_fk',
            ] as $foreignKey) {
                if ($this->hasForeignKey('sponsorship_transfers', $foreignKey)) {
                    $table->dropForeign($foreignKey);
                }
            }

            $columns = collect([
                'receipt_account_id', 'receipt_bond_id', 'receipt_constraint_id',
                'refund_account_id', 'refund_bond_id', 'refund_constraint_id',
            ])->filter(fn ($column) => Schema::hasColumn('sponsorship_transfers', $column))->all();

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }

    private function hasForeignKey(string $table, string $foreignKey): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $foreignKey)
            ->exists();
    }
};
