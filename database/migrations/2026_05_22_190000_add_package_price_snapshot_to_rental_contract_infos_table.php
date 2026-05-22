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
            if (!Schema::hasColumn('rental_contract_infos', 'package_original_price')) {
                $table->decimal('package_original_price', 10, 2)->nullable()->after('package_id');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'package_discount_amount')) {
                $table->decimal('package_discount_amount', 10, 2)->nullable()->after('package_original_price');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'package_price_after_discount')) {
                $table->decimal('package_price_after_discount', 10, 2)->nullable()->after('package_discount_amount');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'package_vat_amount')) {
                $table->decimal('package_vat_amount', 10, 2)->nullable()->after('package_price_after_discount');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'package_total_with_tax')) {
                $table->decimal('package_total_with_tax', 10, 2)->nullable()->after('package_vat_amount');
            }
        });

        DB::table('rental_contract_infos')
            ->leftJoin('packages', 'rental_contract_infos.package_id', '=', 'packages.id')
            ->whereNull('rental_contract_infos.package_total_with_tax')
            ->orderBy('rental_contract_infos.id')
            ->select([
                'rental_contract_infos.id',
                'rental_contract_infos.contract_value',
                'packages.original_price',
                'packages.price_after_discount',
                'packages.price_with_tax',
            ])
            ->chunk(100, function ($rows) {
                foreach ($rows as $row) {
                    $gross = round((float) ($row->contract_value ?? $row->price_with_tax ?? 0), 2);
                    $packageTotal = $row->price_with_tax !== null ? round((float) $row->price_with_tax, 2) : null;
                    $net = $gross > 0 ? round($gross / 1.15, 2) : 0.0;
                    $original = $net;

                    if ($packageTotal !== null && abs($gross - $packageTotal) < 0.05) {
                        $original = round((float) $row->original_price, 2);
                        $net = round((float) $row->price_after_discount, 2);
                        $gross = $packageTotal;
                    }

                    DB::table('rental_contract_infos')
                        ->where('id', $row->id)
                        ->update([
                            'package_original_price' => $original,
                            'package_discount_amount' => round(max(0, $original - $net), 2),
                            'package_price_after_discount' => $net,
                            'package_vat_amount' => round(max(0, $gross - $net), 2),
                            'package_total_with_tax' => $gross,
                        ]);
                }
            });

        DB::table('packages')
            ->orderBy('id')
            ->select(['id', 'original_price', 'price_after_discount'])
            ->chunk(100, function ($packages) {
                foreach ($packages as $package) {
                    $original = round((float) $package->original_price, 2);
                    $net = round((float) $package->price_after_discount, 2);

                    if ($net <= 0) {
                        $net = $original;
                    }

                    DB::table('packages')
                        ->where('id', $package->id)
                        ->update([
                            'price_after_discount' => $net,
                            'price_with_tax' => round($net * 1.15, 2),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('rental_contract_infos', function (Blueprint $table) {
            foreach ([
                'package_total_with_tax',
                'package_vat_amount',
                'package_price_after_discount',
                'package_discount_amount',
                'package_original_price',
            ] as $column) {
                if (Schema::hasColumn('rental_contract_infos', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
