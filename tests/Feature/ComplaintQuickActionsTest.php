<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\ComplaintController;
use App\Models\ingaz\Complaint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ComplaintQuickActionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('superadmin.modules.enabled_map', ['branches' => false], 60);
    }

    private function complaint(string $status = 'pending'): Complaint
    {
        return Complaint::withoutEvents(fn () => Complaint::create([
            'subject' => 'اختبار الإجراءات السريعة', 'message' => 'تفاصيل الاختبار',
            'status' => $status, 'priority' => 'medium',
        ]));
    }

    public function test_quick_response_without_text_returns_a_clear_required_message(): void
    {
        $complaint = $this->complaint();
        $request = Request::create('/', 'POST', ['complaint_id' => $complaint->id, 'response' => '', 'status' => 'resolved']);

        try {
            app(ComplaintController::class)->quickResponse($request);
            $this->fail('Quick response without text should fail validation.');
        } catch (ValidationException $e) {
            $this->assertSame(__('ingaz.quick_response_required'), $e->errors()['response'][0]);
        }

        $this->assertSame('pending', $complaint->fresh()->status);
    }

    public function test_closed_status_is_no_longer_accepted(): void
    {
        $complaint = $this->complaint();

        $this->expectException(ValidationException::class);
        app(ComplaintController::class)->updateStatus(
            Request::create('/', 'POST', ['complaint_id' => $complaint->id, 'status' => 'closed'])
        );
    }

    public function test_reject_quick_action_sets_rejected_status(): void
    {
        $complaint = $this->complaint();

        app(ComplaintController::class)->updateStatus(
            Request::create('/', 'POST', ['complaint_id' => $complaint->id, 'status' => 'rejected'])
        );

        $this->assertSame('rejected', $complaint->fresh()->status);
    }

    public function test_cards_view_is_paginated(): void
    {
        foreach (range(1, 7) as $ignored) {
            $this->complaint();
        }

        $payload = app(ComplaintController::class)
            ->cards(Request::create('/', 'GET', ['per_page' => 6, 'page' => 2]))
            ->getData(true);

        $this->assertSame(2, $payload['meta']['current_page']);
        $this->assertSame(6, $payload['meta']['per_page']);
        $this->assertGreaterThanOrEqual(7, $payload['meta']['total']);
        $this->assertGreaterThanOrEqual(2, $payload['meta']['last_page']);
        $this->assertLessThanOrEqual(6, count($payload['data']));
        $this->assertNotEmpty($payload['data']);
    }
}
