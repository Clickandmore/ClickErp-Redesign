<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fn_accounts', function (Blueprint $table) {
            $table->index(['level', 'code'], 'fn_accounts_level_code_index');
        });

        Schema::table('fn_constraint_details', function (Blueprint $table) {
            $table->index(['account_id', 'date', 'deleted_at', 'constraint_id'], 'fn_cd_trial_balance_index');
        });

        Schema::table('fn_constraints', function (Blueprint $table) {
            $table->index(['year_id', 'deleted_at'], 'fn_constraints_year_deleted_index');
        });
    }

    public function down(): void
    {
        Schema::table('fn_constraints', function (Blueprint $table) {
            $table->dropIndex('fn_constraints_year_deleted_index');
        });

        Schema::table('fn_constraint_details', function (Blueprint $table) {
            $table->dropIndex('fn_cd_trial_balance_index');
        });

        Schema::table('fn_accounts', function (Blueprint $table) {
            $table->dropIndex('fn_accounts_level_code_index');
        });
    }
};
