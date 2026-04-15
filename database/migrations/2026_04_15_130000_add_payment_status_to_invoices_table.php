<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'is_paid')) {
                $table->boolean('is_paid')
                    ->default(true)
                    ->after('total_due')
                    ->comment('حالة السداد');
            }

            if (!Schema::hasColumn('invoices', 'paid_at')) {
                $table->timestamp('paid_at')
                    ->nullable()
                    ->after('is_paid')
                    ->comment('تاريخ السداد');
            }
        });

        DB::table('invoices')->update([
            'is_paid' => true,
            'paid_at' => DB::raw('COALESCE(paid_at, created_at, CURRENT_TIMESTAMP)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'paid_at')) {
                $table->dropColumn('paid_at');
            }

            if (Schema::hasColumn('invoices', 'is_paid')) {
                $table->dropColumn('is_paid');
            }
        });
    }
};
