<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Nami\Finance\Http\Controllers\ConstraintController;
use Nami\Finance\Models\Constraint;
use ReflectionMethod;
use Tests\TestCase;

class FinanceConstraintFiltersTest extends TestCase
{
    /** @test */
    public function it_normalizes_arabic_digits_and_searches_the_displayed_fallback_id(): void
    {
        $query = Constraint::query();
        $request = Request::create('/finance/constraints', 'GET', [
            'keyword' => '#٦٣١',
        ]);

        $this->applyFilters($query, $request);

        $this->assertStringContainsString('`fn_constraints`.`id` = ?', $query->toSql());
        $this->assertContains(631, $query->getBindings());
    }

    /** @test */
    public function it_accepts_the_legacy_datatable_search_parameter(): void
    {
        $query = Constraint::query();
        $request = Request::create('/finance/constraints', 'GET', [
            'sSearch' => 'رواتب',
        ]);

        $this->applyFilters($query, $request);

        $sql = $query->toSql();
        $this->assertStringContainsString('`fn_constraints`.`statement` like ?', $sql);
        $this->assertStringContainsString('exists', $sql);
        $this->assertContains('%رواتب%', $query->getBindings());
    }

    private function applyFilters($query, Request $request): void
    {
        $controller = app(ConstraintController::class);
        $method = new ReflectionMethod($controller, 'applyConstraintFilters');
        $method->setAccessible(true);
        $method->invoke($controller, $query, $request);
    }
}
