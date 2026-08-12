<?php

use App\Services\commissions\LegacyCommissionMarketerResolver;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('admins')
            || ! Schema::hasTable('contracts')
            || ! Schema::hasTable('arabia_recruitment_import_rows')
        ) {
            return;
        }

        $resolver = app(LegacyCommissionMarketerResolver::class);

        DB::transaction(function () use ($resolver) {
            DB::table('arabia_recruitment_import_rows')
                ->where('sheet_name', 'تقرير_الطلبات')
                ->whereNotNull('contract_id')
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($resolver) {
                    foreach ($rows as $row) {
                        $payload = json_decode($row->payload, true) ?: [];
                        $legacyMarketer = trim((string) ($payload['مسوق'] ?? ''));
                        $marketerId = $legacyMarketer !== '' && $legacyMarketer !== 'غير محدد'
                            ? $resolver->resolve($legacyMarketer)?->id
                            : null;

                        $contract = DB::table('contracts')
                            ->where('id', $row->contract_id)
                            ->whereNotNull('arabia_order_number')
                            ->first(['id', 'order_id']);

                        if (! $contract) {
                            continue;
                        }

                        DB::table('contracts')->where('id', $contract->id)->update([
                            'marketer_id' => $marketerId,
                            'updated_at' => now(),
                        ]);

                        if (! $contract->order_id || ! Schema::hasTable('orders')) {
                            continue;
                        }

                        DB::table('orders')->where('id', $contract->order_id)->update([
                            'customer_id' => $marketerId,
                            'updated_at' => now(),
                        ]);

                        if (! Schema::hasTable('order_details')) {
                            continue;
                        }

                        if (DB::table('order_details')->where('order_id', $contract->order_id)->exists()) {
                            DB::table('order_details')->where('order_id', $contract->order_id)->update([
                                'admin_id' => $marketerId,
                                'updated_at' => now(),
                            ]);
                        } elseif ($marketerId) {
                            DB::table('order_details')->insert([
                                'order_id' => $contract->order_id,
                                'admin_id' => $marketerId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }, 'id');
        });
    }

    public function down(): void
    {
        // The incorrect creator-based assignment is intentionally not restored.
    }

};
