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
            if (!Schema::hasColumn('packages', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0)->after('original_price');
            }
        });

        if (Schema::hasColumn('packages', 'discount_amount')) {
            DB::table('packages')
                ->orderBy('id')
                ->select(['id', 'original_price', 'price_after_discount'])
                ->chunk(100, function ($packages) {
                    foreach ($packages as $package) {
                        $original = round((float) $package->original_price, 2);
                        $net = round((float) $package->price_after_discount, 2);

                        DB::table('packages')
                            ->where('id', $package->id)
                            ->update([
                                'discount_amount' => round(max(0, $original - $net), 2),
                            ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            if (Schema::hasColumn('packages', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
        });
    }
};
