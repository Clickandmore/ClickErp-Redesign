<?php

namespace App\Services;

use App\Models\ingaz\User;
use App\Models\ingaz\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ClientDeletionService
{
    public function assertDeletable(User $client, bool $permanent = false): void
    {
        $reasons = [];
        if (!$permanent && Schema::hasTable('known_service_requests')) {
            $ids = DB::table('known_service_requests')->where('user_id', $client->id)
                ->where(function ($query) {
                    $query->whereNull('status')->orWhere('status', '!=', 'rejected');
                })->pluck('id');
            if ($ids->isNotEmpty()) {
                $reasons[] = 'طلبات خدمة معروفة نشطة: #' . $ids->take(10)->implode('، #');
            }
        }
        $orders = DB::table('orders')->where('user_id', $client->id);
        if (!$permanent) {
            $orders->whereNull('deleted_at')->where(function ($query) {
                $query->whereNull('status')->orWhereNotIn('status', Order::nonBlockingMaidAssignmentStatuses());
            });
        }
        $ids = $orders->pluck('id');
        if ($ids->isNotEmpty()) {
            $reasons[] = ($permanent ? 'طلبات مرتبطة، بما فيها المحذوفة' : 'طلبات نشطة') . ': #' . $ids->take(10)->implode('، #');
        }
        foreach ([
            'contracts' => [['user_id'], 'عقود استقدام أو إيواء'],
            'rental_contracts' => [['user_id'], 'عقود إيجار'],
            'sponsorship_transfers' => [['old_sponsor_id', 'new_sponsor_id'], 'عقود نقل خدمات'],
            'transfer_service_contracts' => [['old_sponsor_id', 'new_sponsor_id'], 'عقود نقل خدمات'],
        ] as $table => [$columns, $label]) {
            if (!Schema::hasTable($table)) { continue; }
            $columns = array_filter($columns, fn ($column) => Schema::hasColumn($table, $column));
            if (!$columns) { continue; }
            $ids = DB::table($table)->where(function ($query) use ($columns, $client) {
                foreach ($columns as $column) { $query->orWhere($column, $client->id); }
            })->pluck('id');
            if ($ids->isNotEmpty()) { $reasons[] = $label . ': #' . $ids->take(10)->implode('، #'); }
        }
        if ($permanent) {
            if (!$client->trashed()) { $reasons[] = 'يجب نقل العميل إلى المحذوفين أولاً'; }
            if (DB::getDriverName() === 'mysql') {
                $references = DB::table('information_schema.KEY_COLUMN_USAGE')
                    ->where('TABLE_SCHEMA', DB::getDatabaseName())
                    ->where('REFERENCED_TABLE_NAME', 'users')->where('REFERENCED_COLUMN_NAME', 'id')
                    ->get(['TABLE_NAME', 'COLUMN_NAME']);
                foreach ($references as $reference) {
                    if (DB::table($reference->TABLE_NAME)->where($reference->COLUMN_NAME, $client->id)->exists()) {
                        $reasons[] = 'بيانات مرتبطة بالعميل يجب الاحتفاظ بها';
                    }
                }
            }
            // Preserve all historical references, including tables without foreign keys.
            foreach ([
                'invoices' => ['client_id'], 'complaints' => ['user_id', 'related_user_id'],
                'travel_histories' => ['user_id'], 'financial_requests' => ['client_id'],
                'rental_order_payments' => ['user_id'], 'ingaz_delegations' => ['user_id'],
                'recruitment_requests' => ['user_id'], 'known_service_requests' => ['user_id'],
                'contract_return_requests' => ['user_id'], 'return_requests' => ['user_id'],
            ] as $table => $columns) {
                if (!Schema::hasTable($table)) { continue; }
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column) && DB::table($table)->where($column, $client->id)->exists()) {
                        $reasons[] = 'سجل تعاملات مرتبط بالعميل يجب الاحتفاظ به';
                        break;
                    }
                }
            }
            if ($client->account_id) {
                foreach (['fn_constraint_details' => ['account_id'], 'fn_bonds' => ['debit_account_id', 'credit_account_id']] as $table => $columns) {
                    if (!Schema::hasTable($table)) { continue; }
                    foreach ($columns as $column) {
                        if (Schema::hasColumn($table, $column) && DB::table($table)->where($column, $client->account_id)->exists()) {
                            $reasons[] = 'قيود أو سندات مالية مرتبطة بحساب العميل';
                            break 2;
                        }
                    }
                }
            }
        }
        if ($reasons) {
            throw ValidationException::withMessages(['client' => 'لا يمكن ' . ($permanent ? 'الحذف النهائي' : 'حذف العميل') . ': ' . implode('؛ ', array_unique($reasons)) . '.']);
        }
    }
}
