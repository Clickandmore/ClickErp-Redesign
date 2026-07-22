<?php

use App\Enums\ingaz\OrderEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasTable('contracts')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('maid_id')->nullable()->change();
        });

        DB::transaction(function () {
            DB::table('contracts')
                ->whereIn('type', ['recruitment', 'maid'])
                ->whereNotNull('user_id')
                ->orderBy('id')
                ->chunkById(200, function ($contracts) {
                    foreach ($contracts as $contract) {
                        $orderId = $contract->order_id;

                        if (!$orderId) {
                            $orderCode = $contract->arabia_order_number
                                ? 'ARB-' . $contract->arabia_order_number
                                : 'CONTRACT-' . $contract->id;

                            $existingOrder = DB::table('orders')
                                ->where('order_code', $orderCode)
                                ->first();

                            if ($existingOrder) {
                                $orderId = $existingOrder->id;
                                DB::table('orders')->where('id', $orderId)->update(['deleted_at' => null]);
                            } else {
                                $orderId = DB::table('orders')->insertGetId([
                                    'maid_id' => $contract->maid_id,
                                    'user_id' => $contract->user_id,
                                    'payment_method' => 'cash',
                                    'order_code' => $orderCode,
                                    'job_type_id' => 1,
                                    'status' => OrderEnum::Completed->value,
                                    'customer_id' => $contract->marketer_id,
                                    'branch_id' => $contract->branch_id,
                                    'status_changed_at' => now(),
                                    'deadline_at' => null,
                                    'created_at' => $contract->created_at ?: now(),
                                    'updated_at' => now(),
                                ]);
                            }

                            DB::table('contracts')->where('id', $contract->id)->update([
                                'order_id' => $orderId,
                                'updated_at' => now(),
                            ]);
                        }

                        DB::table('orders')->where('id', $orderId)->update([
                            'maid_id' => $contract->maid_id,
                            'user_id' => $contract->user_id,
                            'job_type_id' => 1,
                            'status' => OrderEnum::Completed->value,
                            'customer_id' => $contract->marketer_id,
                            'branch_id' => $contract->branch_id,
                            'deadline_at' => null,
                            'deleted_at' => null,
                            'updated_at' => now(),
                        ]);

                        if ($contract->marketer_id && Schema::hasTable('order_details')) {
                            $detailExists = DB::table('order_details')
                                ->where('order_id', $orderId)
                                ->exists();

                            if ($detailExists) {
                                DB::table('order_details')->where('order_id', $orderId)->update([
                                    'admin_id' => $contract->marketer_id,
                                    'updated_at' => now(),
                                ]);
                            } else {
                                DB::table('order_details')->insert([
                                    'order_id' => $orderId,
                                    'admin_id' => $contract->marketer_id,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }
                }, 'id');
        });
    }

    public function down(): void
    {
        // Data reconciliation is intentionally irreversible and deletes no records.
    }
};
