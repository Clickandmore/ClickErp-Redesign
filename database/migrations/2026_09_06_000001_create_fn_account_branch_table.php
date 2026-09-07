<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fn_account_branch')) {
            Schema::create('fn_account_branch', function (Blueprint $table) {
                $table->id();
                $table->foreignId('account_id')->constrained('fn_accounts')->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['account_id', 'branch_id']);
                $table->index('branch_id');
            });
        }

        if (Schema::hasTable('fn_accounts') && Schema::hasColumn('fn_accounts', 'branch_id')) {
            DB::table('fn_accounts')
                ->whereNotNull('branch_id')
                ->orderBy('id')
                ->chunkById(500, function ($accounts): void {
                    $rows = $accounts
                        ->map(fn ($account) => [
                            'account_id' => $account->id,
                            'branch_id' => $account->branch_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ])
                        ->all();

                    DB::table('fn_account_branch')->insertOrIgnore($rows);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fn_account_branch');
    }
};
