<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'offer_amount')) {
                $table->decimal('offer_amount', 10, 2)->nullable()->after('price_with_tax');
            }

            if (!Schema::hasColumn('packages', 'offer_starts_at')) {
                $table->date('offer_starts_at')->nullable()->after('offer_amount');
            }

            if (!Schema::hasColumn('packages', 'offer_ends_at')) {
                $table->date('offer_ends_at')->nullable()->after('offer_starts_at');
            }

            if (!Schema::hasColumn('packages', 'offer_is_active')) {
                $table->boolean('offer_is_active')->default(false)->after('offer_ends_at');
            }

            if (!Schema::hasColumn('packages', 'offer_description')) {
                $table->string('offer_description', 500)->nullable()->after('offer_is_active');
            }
        });

        if (Schema::hasColumn('packages', 'discount_amount') && Schema::hasColumn('packages', 'offer_amount')) {
            DB::table('packages')
                ->whereNull('offer_amount')
                ->where('discount_amount', '>', 0)
                ->update([
                    'offer_amount' => DB::raw('discount_amount'),
                    'offer_is_active' => true,
                ]);
        }

        DB::table('packages')
            ->orderBy('id')
            ->select(['id', 'original_price'])
            ->chunk(100, function ($packages) {
                foreach ($packages as $package) {
                    $original = round((float) $package->original_price, 2);

                    DB::table('packages')
                        ->where('id', $package->id)
                        ->update([
                            'discount_amount' => 0,
                            'price_after_discount' => $original,
                            'price_with_tax' => round($original * 1.15, 2),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            foreach ([
                'offer_description',
                'offer_is_active',
                'offer_ends_at',
                'offer_starts_at',
                'offer_amount',
            ] as $column) {
                if (Schema::hasColumn('packages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
