<?php

namespace Tests\Feature;

use App\Services\ingaz\NationalityFinanceAccountService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Account;
use ReflectionMethod;
use Tests\TestCase;

class NationalityFinanceAccountServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_next_child_code_skips_a_code_used_under_another_parent(): void
    {
        $highestCode = (int) DB::table('fn_accounts')->max('code');
        $parentCode = $highestCode + 1000;
        $conflictingCode = ($parentCode * 100) + 1;
        $attributes = [
            'operation_type' => 1,
            'status' => 'debtor',
            'statement_type' => 'income_statement',
            'type' => 1,
            'created_by_id' => DB::table('admins')->value('id') ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $parentId = DB::table('fn_accounts')->insertGetId(array_merge($attributes, [
            'name' => 'Nationality account code test parent',
            'parent_id' => null,
            'level' => 1,
            'code' => $parentCode,
        ]));

        DB::table('fn_accounts')->insert(array_merge($attributes, [
            'name' => 'Account using another parent candidate',
            'parent_id' => null,
            'level' => 1,
            'code' => $conflictingCode,
        ]));

        $method = new ReflectionMethod(NationalityFinanceAccountService::class, 'nextChildCode');
        $nextCode = DB::transaction(fn () => $method->invoke(
            app(NationalityFinanceAccountService::class),
            Account::query()->findOrFail($parentId)
        ));

        $this->assertSame($conflictingCode + 1, $nextCode);
    }
}
