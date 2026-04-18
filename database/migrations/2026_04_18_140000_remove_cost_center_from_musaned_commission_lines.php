<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('musaned_transfers')
            || !Schema::hasTable('musaned_transfer_items')
            || !Schema::hasTable('fn_constraint_details')
        ) {
            return;
        }

        $transfers = DB::table('musaned_transfers')
            ->whereNotNull('settlement_constraint_id')
            ->select([
                'id',
                'reference_number',
                'settlement_constraint_id',
                'commission_account_id',
                'expense_tax_account_id',
            ])
            ->get();

        foreach ($transfers as $transfer) {
            $reference = $transfer->reference_number ?: 'بدون مرجع';

            $items = DB::table('musaned_transfer_items')
                ->where('musaned_transfer_id', $transfer->id)
                ->select([
                    'contract_id',
                    'cost_center_id',
                    'source_account_id',
                    'commission_amount',
                    'expense_tax_amount',
                    'notes',
                ])
                ->get();

            foreach ($items as $item) {
                if (!$item->cost_center_id) {
                    continue;
                }

                $statement = 'تسوية تحويل مساند / العقد #' . $item->contract_id
                    . ' / مساند ' . ($item->notes ?: '-')
                    . ' / مرجع ' . $reference;

                $commissionAmount = number_format((float) $item->commission_amount, 2, '.', '');
                $expenseTaxAmount = number_format((float) $item->expense_tax_amount, 2, '.', '');

                if ((float) $commissionAmount > 0 && $transfer->commission_account_id && $item->source_account_id) {
                    DB::table('fn_constraint_details')
                        ->where('constraint_id', $transfer->settlement_constraint_id)
                        ->where('statement', $statement)
                        ->where('cost_center_id', $item->cost_center_id)
                        ->where(function ($query) use ($transfer, $item, $commissionAmount) {
                            $query->where(function ($subQuery) use ($transfer, $commissionAmount) {
                                $subQuery->where('account_id', $transfer->commission_account_id)
                                    ->where('type', 'debtor')
                                    ->where('debtor_value', $commissionAmount);
                            })->orWhere(function ($subQuery) use ($item, $commissionAmount) {
                                $subQuery->where('account_id', $item->source_account_id)
                                    ->where('type', 'creditor')
                                    ->where('creditor_value', $commissionAmount);
                            });
                        })
                        ->update(['cost_center_id' => null]);
                }

                if ((float) $expenseTaxAmount > 0 && $transfer->expense_tax_account_id && $transfer->commission_account_id) {
                    DB::table('fn_constraint_details')
                        ->where('constraint_id', $transfer->settlement_constraint_id)
                        ->where('statement', $statement)
                        ->where('cost_center_id', $item->cost_center_id)
                        ->where(function ($query) use ($transfer, $expenseTaxAmount) {
                            $query->where(function ($subQuery) use ($transfer, $expenseTaxAmount) {
                                $subQuery->where('account_id', $transfer->expense_tax_account_id)
                                    ->where('type', 'debtor')
                                    ->where('debtor_value', $expenseTaxAmount);
                            })->orWhere(function ($subQuery) use ($transfer, $expenseTaxAmount) {
                                $subQuery->where('account_id', $transfer->commission_account_id)
                                    ->where('type', 'creditor')
                                    ->where('creditor_value', $expenseTaxAmount);
                            });
                        })
                        ->update(['cost_center_id' => null]);
                }
            }
        }
    }

    public function down(): void
    {
        // Irreversible data correction.
    }
};
