<?php

namespace Tests\Unit;

use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\MaidExpertiseAction;
use App\Models\ingaz\Maid;
use App\Services\ingaz\RentalMaidAccountingService;
use App\Services\ingaz\RentalMaidFinanceService;
use Illuminate\Database\QueryException;
use Mockery;
use PDOException;
use ReflectionMethod;
use Tests\TestCase;

class MaidActionAddedByFallbackTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_create_maid_record_retries_without_added_by_after_foreign_key_violation(): void
    {
        $createdMaid = Mockery::mock(Maid::class);
        $model = Mockery::mock(Maid::class);
        $model->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (array $data) => ($data['added_by'] ?? null) === 15))
            ->andThrow($this->makeAddedByForeignKeyException());
        $model->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn (array $data) => array_key_exists('added_by', $data) && $data['added_by'] === null))
            ->andReturn($createdMaid);

        $action = Mockery::mock(
            MaidAction::class,
            [
                $model,
                Mockery::mock(MaidExpertiseAction::class),
                Mockery::mock(RentalMaidFinanceService::class),
                Mockery::mock(RentalMaidAccountingService::class),
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();

        $action->shouldReceive('resolveValidMaidAddedBy')->andReturn(15);

        $method = new ReflectionMethod(MaidAction::class, 'createMaidRecord');
        $method->setAccessible(true);

        $result = $method->invoke($action, [
            'name' => 'Pascale Bird',
            'status' => 'available',
            'added_by' => 15,
        ]);

        $this->assertSame($createdMaid, $result);
    }

    private function makeAddedByForeignKeyException(): QueryException
    {
        return new QueryException(
            'insert into `maids` (`added_by`) values (?)',
            [15],
            new PDOException(
                'SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`maids`, CONSTRAINT `maids_added_by_foreign` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE SET NULL)'
            )
        );
    }
}
