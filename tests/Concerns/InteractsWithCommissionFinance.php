<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\FnYear;

trait InteractsWithCommissionFinance
{
    protected function setUpCommissionFinance(): void
    {
        DB::table('fn_constraint_code_sequences')->updateOrInsert(['id' => 1], [
            'last_code' => (int) DB::table('fn_constraints')->max('code'), 'created_at' => now(), 'updated_at' => now(),
        ]);
        FnYear::query()->create([
            'title' => 'اختبار عمولات', 'from_date' => now()->startOfYear()->toDateString(),
            'to_date' => now()->endOfYear()->toDateString(), 'status' => 1,
        ]);
        Account::query()->create([
            'name' => 'عمولات مسوقين', 'code' => 5417, 'status' => 'debtor',
            'type' => AccountTypesEnum::MarketerExpenses->value, 'level' => 1,
        ]);
        Account::query()->create([
            'name' => 'المسوقين', 'code' => 144, 'status' => 'creditor',
            'type' => AccountTypesEnum::Marketers->value, 'level' => 1,
        ]);
        DB::table('fn_confirm_settings')->where('title', 'daily_constraints')->delete();
    }
}
