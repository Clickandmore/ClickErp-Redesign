<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fn_principle_perished', function (Blueprint $table) {
            $table->string('account_type')->nullable()->after('period');
            $table->foreignId('expense_account_id')->nullable()->after('account_type')->constrained('fn_accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_principle_perished', function (Blueprint $table) {
            $table->dropForeign(['expense_account_id']);
            $table->dropColumn('expense_account_id');
            $table->dropColumn('account_type');
        });
    }
};
