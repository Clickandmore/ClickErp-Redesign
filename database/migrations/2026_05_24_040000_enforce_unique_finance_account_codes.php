<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fn_accounts')) {
            return;
        }

        DB::transaction(function () {
            $duplicateCodes = DB::table('fn_accounts')
                ->select('code')
                ->groupBy('code')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('code');

            foreach ($duplicateCodes as $code) {
                $accounts = DB::table('fn_accounts')
                    ->where('code', $code)
                    ->orderBy('id')
                    ->get(['id', 'parent_id']);

                foreach ($accounts->slice(1) as $account) {
                    DB::table('fn_accounts')
                        ->where('id', $account->id)
                        ->update(['code' => $this->nextAvailableCode($account->parent_id, $account->id)]);
                }
            }
        });

        Schema::table('fn_accounts', function (Blueprint $table) {
            $table->unique('code', 'fn_accounts_code_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('fn_accounts')) {
            return;
        }

        Schema::table('fn_accounts', function (Blueprint $table) {
            $table->dropUnique('fn_accounts_code_unique');
        });
    }

    private function nextAvailableCode(?int $parentId, int $excludeId): int
    {
        $start = 1;

        if ($parentId !== null) {
            $parentCode = DB::table('fn_accounts')->where('id', $parentId)->value('code');
            $start = ((int) $parentCode) * 100;
        }

        $usedCodes = DB::table('fn_accounts')
            ->where('id', '<>', $excludeId)
            ->orderBy('code')
            ->pluck('code')
            ->map(fn ($code) => (int) $code)
            ->all();

        while (in_array($start, $usedCodes, true)) {
            $start++;
        }

        return $start;
    }
};
