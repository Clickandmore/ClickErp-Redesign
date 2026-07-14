<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fn_cost_centers', function (Blueprint $table) {
            $table->unsignedSmallInteger('legacy_level')->nullable()->after('code');
            $table->text('legacy_full_path')->nullable()->after('legacy_level');
            $table->decimal('legacy_debtor_total', 18, 2)->nullable()->after('legacy_full_path');
            $table->decimal('legacy_creditor_total', 18, 2)->nullable()->after('legacy_debtor_total');
            $table->decimal('legacy_balance', 18, 2)->nullable()->after('legacy_creditor_total');
            $table->string('legacy_balance_type', 20)->nullable()->after('legacy_balance');
            $table->decimal('legacy_opening_balance', 18, 2)->nullable()->after('legacy_balance_type');
        });
    }

    public function down(): void
    {
        Schema::table('fn_cost_centers', function (Blueprint $table) {
            $table->dropColumn([
                'legacy_level',
                'legacy_full_path',
                'legacy_debtor_total',
                'legacy_creditor_total',
                'legacy_balance',
                'legacy_balance_type',
                'legacy_opening_balance',
            ]);
        });
    }
};
