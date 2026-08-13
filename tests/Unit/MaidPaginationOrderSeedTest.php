<?php

namespace Tests\Unit;

use App\Http\Actions\ingaz\MaidAction;
use Illuminate\Http\Request;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class MaidPaginationOrderSeedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        session()->forget('all_workers_order_seed');
    }

    public function test_api_pages_use_the_same_order_seed_for_the_same_filters(): void
    {
        $firstPageSeed = $this->resolveSeed(Request::create('/api/maids', 'GET', [
            'type' => 'estqdam',
            'nationality_id' => 2,
            'page' => 1,
        ]));
        $secondPageSeed = $this->resolveSeed(Request::create('/api/maids', 'GET', [
            'page' => 2,
            'nationality_id' => 2,
            'type' => 'estqdam',
        ]));

        $this->assertSame($firstPageSeed, $secondPageSeed);
    }

    public function test_different_filters_produce_different_default_order_seeds(): void
    {
        $recruitmentSeed = $this->resolveSeed(Request::create('/api/maids', 'GET', [
            'type' => 'estqdam',
            'page' => 1,
        ]));
        $rentalSeed = $this->resolveSeed(Request::create('/api/maids', 'GET', [
            'type' => 'egar',
            'page' => 1,
        ]));

        $this->assertNotSame($recruitmentSeed, $rentalSeed);
    }

    public function test_explicit_order_seed_is_preserved_across_pages(): void
    {
        $seed = $this->resolveSeed(Request::create('/api/maids', 'GET', [
            'type' => 'transfer',
            'order_seed' => 'external-site-session-15',
            'page' => 2,
        ]));

        $this->assertSame('external-site-session-15', $seed);
    }

    private function resolveSeed(Request $request): string
    {
        $action = (new ReflectionClass(MaidAction::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(MaidAction::class, 'resolvePaginationOrderSeed');
        $method->setAccessible(true);

        return $method->invoke($action, $request);
    }
}
