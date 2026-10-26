<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\ComplaintController;
use App\Models\ingaz\Complaint;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ComplaintClosureTest extends TestCase
{
    public function test_final_statuses_reject_further_actions(): void
    {
        $controller = (new \ReflectionClass(ComplaintController::class))->newInstanceWithoutConstructor();
        $guard = new \ReflectionMethod(ComplaintController::class, 'assertComplaintIsOpen');
        $guard->setAccessible(true);

        foreach (['resolved', 'closed', 'rejected'] as $status) {
            $complaint = new Complaint(['status' => $status]);
            $this->assertTrue($complaint->isClosed());
            try {
                $guard->invoke($controller, $complaint);
                $this->fail('A closed complaint must reject actions.');
            } catch (HttpException $exception) {
                $this->assertSame(409, $exception->getStatusCode());
            }
        }
    }

    public function test_open_statuses_allow_actions(): void
    {
        $controller = (new \ReflectionClass(ComplaintController::class))->newInstanceWithoutConstructor();
        $guard = new \ReflectionMethod(ComplaintController::class, 'assertComplaintIsOpen');
        $guard->setAccessible(true);

        foreach (['pending', 'in_progress'] as $status) {
            $complaint = new Complaint(['status' => $status]);
            $this->assertFalse($complaint->isClosed());
            $guard->invoke($controller, $complaint);
        }
    }
}
