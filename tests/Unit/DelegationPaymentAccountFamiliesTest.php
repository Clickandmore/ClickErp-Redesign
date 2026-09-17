<?php

namespace Tests\Unit;

use App\Services\ingaz\DelegationCostService;
use PHPUnit\Framework\TestCase;

class DelegationPaymentAccountFamiliesTest extends TestCase
{
    public function test_bank_groups_and_generic_descendants_are_included_without_expense_accounts(): void
    {
        $accounts = collect([
            ['id' => 1, 'parent_id' => null, 'type' => 14, 'code' => 122, 'name' => 'البنوك'],
            ['id' => 2, 'parent_id' => 1, 'type' => 1, 'code' => 1221, 'name' => 'حساب فرعي'],
            ['id' => 3, 'parent_id' => 2, 'type' => 1, 'code' => 12211, 'name' => 'حساب نهائي'],
            ['id' => 4, 'parent_id' => null, 'type' => 1, 'code' => 52113, 'name' => 'عمولات بنكية'],
        ])->map(fn ($a) => (object) $a);
        $this->assertEqualsCanonicalizing([1, 2, 3], DelegationCostService::accountFamilyIds($accounts, 'bank'));
    }

    public function test_legacy_custodies_in_other_groups_and_boxes_are_included(): void
    {
        $accounts = collect([
            ['id' => 1, 'parent_id' => null, 'type' => 1, 'code' => 1216, 'name' => 'عهد وذمم على الموظفين'],
            ['id' => 2, 'parent_id' => 1, 'type' => 1, 'code' => 1261, 'name' => 'باسم'],
            ['id' => 3, 'parent_id' => null, 'type' => 17, 'code' => 1213, 'name' => 'عهدة موظف'],
            ['id' => 4, 'parent_id' => null, 'type' => 17, 'code' => 1212, 'name' => 'الصندوق الرئيسي'],
            ['id' => 5, 'parent_id' => null, 'type' => 28, 'code' => 1269, 'name' => 'موظف'],
        ])->map(fn ($a) => (object) $a);
        $this->assertEqualsCanonicalizing([1, 2, 3, 5], DelegationCostService::accountFamilyIds($accounts, 'custody'));
    }
}
