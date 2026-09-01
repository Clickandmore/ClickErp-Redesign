<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const UNIQUE_INDEX = 'fn_constraints_active_code_unique';

    public function up(): void
    {
        $duplicate = DB::table('fn_constraints')
            ->whereNull('deleted_at')
            ->select('code')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate) {
            throw new \RuntimeException(
                'Active duplicate constraint codes exist. Run "php artisan finance:repair-duplicate-constraint-codes --apply" before migrating.'
            );
        }

        // MySQL permits repeated NULL values in a unique index. This generated
        // column therefore enforces uniqueness for active constraints without
        // rewriting or restricting soft-deleted historical records.
        Schema::table('fn_constraints', function (Blueprint $table) {
            $table->unsignedBigInteger('active_code')
                ->nullable()
                ->storedAs('IF(`deleted_at` IS NULL, `code`, NULL)');
            $table->unique('active_code', self::UNIQUE_INDEX);
        });
    }

    public function down(): void
    {
        Schema::table('fn_constraints', function (Blueprint $table) {
            $table->dropUnique(self::UNIQUE_INDEX);
            $table->dropColumn('active_code');
        });
    }
};
