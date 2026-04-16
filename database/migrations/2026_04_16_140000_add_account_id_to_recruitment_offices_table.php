<?php

use App\Services\office\OfficeFinanceAccountService;
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
        Schema::table('recruitment_offices', function (Blueprint $table) {
            $table->foreignId('account_id')
                ->nullable()
                ->after('id')
                ->constrained('fn_accounts')
                ->nullOnDelete();
        });

        if (Schema::hasTable('fn_accounts')) {
            app(OfficeFinanceAccountService::class)->backfillExistingOffices();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recruitment_offices', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};
