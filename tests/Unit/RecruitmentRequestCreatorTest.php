<?php

namespace Tests\Unit;

use App\Models\auth\Admin;
use App\Models\ingaz\RecruitmentRequest;
use Tests\TestCase;

class RecruitmentRequestCreatorTest extends TestCase
{
    private function requestModel(): RecruitmentRequest
    {
        return new class extends RecruitmentRequest {
            protected function orderBranchesFeatureAvailable(): bool
            {
                return false;
            }

            protected static function generateUniqueCode()
            {
                return '12345';
            }

            public function runCreating(): void
            {
                $this->fireModelEvent('creating');
            }
        };
    }

    public function test_internal_booking_records_actor_independently_of_responsible_employee(): void
    {
        $actor = new Admin();
        $actor->forceFill(['id' => 17]);
        $this->actingAs($actor, 'admin');
        $request = $this->requestModel();
        $request->forceFill(['customer_id' => 29, 'created_by_admin_id' => 99]);
        $request->runCreating();

        $this->assertSame(17, $request->created_by_admin_id);
        $this->assertSame(29, $request->customer_id);
        $this->assertSame('new', $request->status);
    }

    public function test_creation_without_employee_authentication_has_no_employee_creator(): void
    {
        auth('admin')->logout();
        $request = $this->requestModel();
        $request->forceFill(['created_by_admin_id' => 99]);
        $request->runCreating();

        $this->assertNull($request->created_by_admin_id);
    }
}