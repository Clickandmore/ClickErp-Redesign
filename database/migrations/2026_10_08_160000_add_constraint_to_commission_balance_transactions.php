<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConstraintToCommissionBalanceTransactions extends Migration
{
    public function up(): void
    {
        Schema::table('commission_balance_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('constraint_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('commission_balance_transactions', fn (Blueprint $table) => $table->dropColumn('constraint_id'));
    }
}
