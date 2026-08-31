<?php

namespace Tests\Unit;

use App\Console\Commands\RepairDuplicateConstraintCodes;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RepairDuplicateConstraintCodesTest extends TestCase
{
    public function test_it_keeps_the_first_active_constraint_and_uses_numbers_above_the_global_maximum(): void
    {
        $rows = collect([
            (object) ['id' => 10, 'code' => 100, 'deleted_at' => null],
            (object) ['id' => 11, 'code' => 100, 'deleted_at' => null],
            (object) ['id' => 12, 'code' => 100, 'deleted_at' => null],
            (object) ['id' => 13, 'code' => 250, 'deleted_at' => '2026-01-01 00:00:00'],
        ]);

        $plan = $this->buildPlan($rows);

        $this->assertSame([
            [
                'old_code' => 100,
                'keeper_id' => 10,
                'constraint_id' => 11,
                'new_code' => 251,
            ],
            [
                'old_code' => 100,
                'keeper_id' => 10,
                'constraint_id' => 12,
                'new_code' => 252,
            ],
        ], $plan->all());
    }

    public function test_it_does_not_renumber_soft_deleted_duplicates(): void
    {
        $rows = collect([
            (object) ['id' => 20, 'code' => 300, 'deleted_at' => '2026-01-01 00:00:00'],
            (object) ['id' => 21, 'code' => 300, 'deleted_at' => '2026-01-02 00:00:00'],
            (object) ['id' => 22, 'code' => 301, 'deleted_at' => null],
        ]);

        $this->assertTrue($this->buildPlan($rows)->isEmpty());
    }

    private function buildPlan(Collection $rows): Collection
    {
        $method = new ReflectionMethod(RepairDuplicateConstraintCodes::class, 'buildPlan');
        $method->setAccessible(true);

        return $method->invoke(new RepairDuplicateConstraintCodes(), $rows);
    }
}
