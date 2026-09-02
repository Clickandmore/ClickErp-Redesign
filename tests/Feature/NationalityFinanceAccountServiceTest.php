<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Services\ingaz\NationalityFinanceAccountService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Account;
use ReflectionMethod;
use Tests\TestCase;

class NationalityFinanceAccountServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_next_child_code_skips_a_code_used_by_another_parent(): void
    {
        $admin = Admin::factory()->create();
        $baseCode = ((int) Account::query()->max('code')) + 1000;
        $parent = $this->createAccount($admin->id, null, $baseCode, 1, 'Test parent');
        $otherParent = $this->createAccount($admin->id, null, $baseCode + 1, 1, 'Other test parent');
        $lastSiblingCode = $baseCode + 1000;

        $this->createAccount($admin->id, $parent->id, $lastSiblingCode, 2, 'Test sibling');
        $this->createAccount($admin->id, $otherParent->id, $lastSiblingCode + 1, 2, 'Test global collision');

        $method = new ReflectionMethod(NationalityFinanceAccountService::class, 'nextChildCode');
        $method->setAccessible(true);

        $nextCode = $method->invoke(new NationalityFinanceAccountService(), $parent);

        $this->assertSame($lastSiblingCode + 2, $nextCode);
    }

    private function createAccount(
        int $adminId,
        ?int $parentId,
        int $code,
        int $level,
        string $name
    ): Account
    {
        $accountId = DB::table('fn_accounts')->insertGetId([
            'name' => $name,
            'operation_type' => 1,
            'status' => 'debtor',
            'statement_type' => 'income_statement',
            'level' => $level,
            'code' => $code,
            'type' => 1,
            'parent_id' => $parentId,
            'created_by_id' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Account::query()->findOrFail($accountId);
    }
}
