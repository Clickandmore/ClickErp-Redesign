<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('arabia_recruitment_import_rows')
            || !Schema::hasTable('contracts')
            || !Schema::hasTable('orders')
            || !Schema::hasTable('admins')
        ) {
            return;
        }

        $adminIds = [];
        foreach (DB::table('admins')->get(['id', 'name', 'username']) as $admin) {
            foreach ([$admin->name, $admin->username] as $candidate) {
                $normalized = $this->normalize($candidate);
                if ($normalized !== '') {
                    $adminIds[$normalized][] = (int) $admin->id;
                }
            }
        }

        DB::transaction(function () use ($adminIds) {
            DB::table('arabia_recruitment_import_rows')
                ->where('sheet_name', 'تقرير_الطلبات')
                ->whereNotNull('contract_id')
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($adminIds) {
                    foreach ($rows as $row) {
                        $payload = json_decode($row->payload, true) ?: [];
                        $creator = $this->normalize($payload['أنشئ بواسطة'] ?? null);
                        $matches = array_values(array_unique($adminIds[$creator] ?? []));
                        $marketerId = count($matches) === 1 ? $matches[0] : null;
                        $contract = DB::table('contracts')
                            ->where('id', $row->contract_id)
                            ->whereNotNull('arabia_order_number')
                            ->first(['id', 'order_id']);

                        if (!$contract) {
                            continue;
                        }

                        DB::table('contracts')->where('id', $contract->id)->update([
                            'marketer_id' => $marketerId,
                            'updated_at' => now(),
                        ]);

                        if (!$contract->order_id) {
                            continue;
                        }

                        DB::table('orders')->where('id', $contract->order_id)->update([
                            'customer_id' => $marketerId,
                            'updated_at' => now(),
                        ]);

                        if (!Schema::hasTable('order_details')) {
                            continue;
                        }

                        $detailExists = DB::table('order_details')
                            ->where('order_id', $contract->order_id)
                            ->exists();

                        if ($detailExists) {
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
        // Historical marketer assignments cannot be reconstructed safely.
    }

    private function normalize($value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = strtr($value, [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ى' => 'ي', 'ة' => 'ه',
            'ؤ' => 'و', 'ئ' => 'ي', 'ـ' => '',
        ]);

        return preg_replace('/[\s\p{P}\p{S}_]+/u', '', $value) ?: '';
    }
};
