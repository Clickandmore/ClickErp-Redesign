<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\MaidController;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class RentalMaidTypePresentationTest extends TestCase
{
    private MaidController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = app(MaidController::class);
    }

    public function test_rental_page_displays_only_the_rental_type_for_a_legacy_multi_type_maid(): void
    {
        $maid = new Maid();
        $maid->setRelation('jobTypes', new EloquentCollection([
            new JobType(['id' => 1, 'title' => 'استقدام']),
            new JobType(['id' => 2, 'title' => 'تأجير']),
            new JobType(['id' => 3, 'title' => 'استلام فوري']),
        ]));

        $title = $this->invoke('getMaidJobTypeTitle', $maid, 2);

        $this->assertSame('تأجير', $title);
    }

    public function test_rental_form_receives_only_the_rental_type_option(): void
    {
        $types = new Collection([
            new JobType(['id' => 1, 'title' => 'استقدام']),
            new JobType(['id' => 2, 'title' => 'تأجير']),
            new JobType(['id' => 3, 'title' => 'استلام فوري']),
        ]);

        $filtered = $this->invoke('filterJobTypesById', $types, 2);

        $this->assertSame([2], $filtered->pluck('id')->all());
    }

    private function invoke(string $method, mixed ...$arguments): mixed
    {
        $reflection = new ReflectionMethod($this->controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($this->controller, ...$arguments);
    }
}
