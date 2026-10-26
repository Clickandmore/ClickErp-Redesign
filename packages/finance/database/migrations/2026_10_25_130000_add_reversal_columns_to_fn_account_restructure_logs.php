<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * التراجع عن الدمج: يُعلَّم سجل الدمج الأصلي بأنه معكوس ومن عكسه ولماذا.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fn_account_restructure_logs') || Schema::hasColumn('fn_account_restructure_logs', 'reversed_at')) {
            return;
        }

        Schema::table('fn_account_restructure_logs', function (Blueprint $table) {
            $table->timestamp('reversed_at')->nullable()->after('created_by_id');
            $table->unsignedBigInteger('reversed_by_id')->nullable()->after('reversed_at');
            $table->string('reversal_reason', 1000)->nullable()->after('reversed_by_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('fn_account_restructure_logs', 'reversed_at')) {
            return;
        }

        Schema::table('fn_account_restructure_logs', function (Blueprint $table) {
            $table->dropColumn(['reversed_at', 'reversed_by_id', 'reversal_reason']);
        });
    }
};
