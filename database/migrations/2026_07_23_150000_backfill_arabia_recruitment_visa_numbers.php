<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('arabia_recruitment_import_rows')
            || ! Schema::hasTable('contracts')
            || ! Schema::hasTable('contract_infos')
        ) {
            return;
        }

        $visaNumbers = [];
        $conflicts = [];

        DB::table('arabia_recruitment_import_rows')
            ->whereIn('sheet_name', ['مواعيد_الوصول', 'الملاحظات'])
            ->whereNotNull('arabia_order_number')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$visaNumbers, &$conflicts) {
                foreach ($rows as $row) {
                    $payload = json_decode($row->payload, true) ?: [];
                    $visaNumber = $this->visaNumber($payload['رقم التأشيرة'] ?? null);
                    $orderNumber = (int) $row->arabia_order_number;
                    if (! $orderNumber || ! $visaNumber) {
                        continue;
                    }

                    if (isset($visaNumbers[$orderNumber]) && $visaNumbers[$orderNumber] !== $visaNumber) {
                        $conflicts[$orderNumber] = true;
                        continue;
                    }

                    $visaNumbers[$orderNumber] = $visaNumber;
                }
            }, 'id');

        foreach (array_keys($conflicts) as $orderNumber) {
            unset($visaNumbers[$orderNumber]);
        }

        DB::transaction(function () use ($visaNumbers) {
            DB::table('contracts')
                ->whereNotNull('arabia_order_number')
                ->orderBy('id')
                ->chunkById(200, function ($contracts) use ($visaNumbers) {
                    foreach ($contracts as $contract) {
                        $visaNumber = $visaNumbers[(int) $contract->arabia_order_number] ?? null;
                        if (! $visaNumber) {
                            continue;
                        }

                        $contractVisaNumber = trim((string) $contract->visa_number);
                        $infoVisaNumber = trim((string) DB::table('contract_infos')
                            ->where('contract_id', $contract->id)
                            ->value('visa_number'));
                        $resolvedVisaNumber = $infoVisaNumber ?: ($contractVisaNumber ?: $visaNumber);

                        if ($contractVisaNumber === '') {
                            DB::table('contracts')->where('id', $contract->id)->update([
                                'visa_number' => $resolvedVisaNumber,
                                'updated_at' => now(),
                            ]);
                        }

                        DB::table('contract_infos')
                            ->where('contract_id', $contract->id)
                            ->where(function ($query) {
                                $query->whereNull('visa_number')->orWhere('visa_number', '');
                            })
                            ->update([
                                'visa_number' => $resolvedVisaNumber,
                                'updated_at' => now(),
                            ]);
                    }
                }, 'id');
        });
    }

    public function down(): void
    {
        // Source-backed visa numbers are intentionally retained.
    }

    private function visaNumber($value): ?string
    {
        $value = strtr(trim((string) $value), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
        $value = preg_replace('/\D+/u', '', $value);

        return $value !== '' ? $value : null;
    }
};
