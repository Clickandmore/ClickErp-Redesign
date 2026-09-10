<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\ComplaintController;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\RecruitmentOffices;
use App\Models\auth\Admin;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\ComplaintComment;
use App\Services\Complaints\ComplaintAssignmentNotifier;
use App\Services\Complaints\ComplaintHistory;
use App\Support\AdminRoutePermissionMatrix;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ComplaintManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('superadmin.modules.enabled_map', ['branches' => false], 60);
    }

    private function complaint(): Complaint
    {
        return Complaint::withoutEvents(fn () => Complaint::create([
            'subject' => 'اختبار إدارة الشكوى', 'message' => 'تفاصيل الاختبار',
            'status' => 'pending', 'priority' => 'medium',
        ]));
    }

    public function test_manual_contract_office_is_displayed_even_without_a_worker(): void
    {
        $office = new RecruitmentOffices();
        $info = (new ContractInfo())->setRelation('manualMaidOffice', $office);
        $contract = (new Contract())->setRelation('maid', null)->setRelation('info', $info);
        $complaint = (new Complaint())->setRelation('recruitmentOffice', null)->setRelation('contract', $contract);
        $this->assertSame($office, $complaint->external_office);
    }

    public function test_schedule_is_saved_cleared_and_audited(): void
    {
        $complaint = $this->complaint();
        $controller = app(ComplaintController::class);
        $due = now()->addDays(2)->format('Y-m-d H:i:s');
        $controller->schedule(Request::create('/', 'POST', ['due_at' => $due, 'schedule_notes' => 'متابعة الإدارة']), $complaint);
        $this->assertSame($due, $complaint->fresh()->due_at->format('Y-m-d H:i:s'));
        $events = app(ComplaintHistory::class)->forComplaint($complaint);
        $this->assertTrue(\Carbon\Carbon::parse($due)->equalTo(\Carbon\Carbon::parse($events->first()->properties['attributes']['due_at'])));
        $controller->schedule(Request::create('/', 'POST', ['due_at' => null, 'schedule_notes' => null]), $complaint);
        $this->assertNull($complaint->fresh()->due_at);
    }

    public function test_past_schedule_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(ComplaintController::class)->schedule(Request::create('/', 'POST', ['due_at' => '2020-01-01']), $this->complaint());
    }

    public function test_linking_does_not_require_reentering_old_complainant_details(): void
    {
        $complaint = $this->complaint();
        app(ComplaintController::class)->linkRecord(Request::create('/', 'POST', [
            'relation_type' => 'manual', 'against_name' => 'طرف الشكوى', 'relation_reference' => 'عقد تأجير 123',
        ]), $complaint);
        $this->assertSame('manual', $complaint->fresh()->relation_type);
        $this->assertSame('عقد تأجير 123', $complaint->fresh()->relation_reference);
        $this->assertSame('اختبار إدارة الشكوى', $complaint->fresh()->subject);
    }

    public function test_deleted_comments_remain_in_the_history(): void
    {
        $complaint = $this->complaint();
        $admin = Admin::factory()->create();
        $comment = ComplaintComment::create(['complaint_id' => $complaint->id, 'added_by' => $admin->id, 'comment' => 'سجل قبل الحذف']);
        $comment->delete();
        $history = app(ComplaintHistory::class)->forComplaint($complaint);
        $this->assertTrue($history->contains(fn ($event) => $event->event === 'deleted' && $event->subject_id === $comment->id));
    }

    public function test_only_selected_notification_channel_is_used(): void
    {
        Mail::shouldReceive('raw')->once()->andReturnNull();
        $complaint = $this->complaint()->setRelation('assignedTo', (new Admin())->forceFill(['id' => 1, 'name' => 'موظف', 'email' => 'test@example.com']));
        $this->mock(\App\Services\AdminNotificationService::class)->shouldNotReceive('sendToAdmin');
        $results = app(ComplaintAssignmentNotifier::class)->send($complaint, ['email']);
        $this->assertSame(['email'], array_keys($results));
        $this->assertStringContainsString('تم تسليم', $results['email']);
    }

    public function test_notification_failure_is_recorded_without_undoing_assignment(): void
    {
        $complaint = $this->complaint()->setRelation('assignedTo', (new Admin())->forceFill(['id' => 1, 'email' => null]));
        $results = app(ComplaintAssignmentNotifier::class)->send($complaint, ['email']);
        $this->assertStringContainsString('تعذر', $results['email']);
        $event = app(ComplaintHistory::class)->forComplaint($complaint)->first();
        $this->assertSame($results, $event->properties['notification_results']);
    }

    public function test_creation_history_does_not_repeat_the_complaint_fields(): void
    {
        $event = new \App\Models\AuditActivity([
            'event' => 'created', 'description' => 'created', 'subject_type' => (new Complaint)->getMorphClass(),
            'properties' => ['attributes' => ['subject' => 'شكوى', 'external_ref' => null, 'is_read' => 0, 'message' => 'تفاصيل']],
        ]);
        $entry = ComplaintHistory::present($event);
        $this->assertSame('أضاف الشكوى', $entry['title']);
        $this->assertSame([], $entry['changes']);
    }

    public function test_history_shows_real_changes_and_cleared_values_without_duplicate_text(): void
    {
        $event = new \App\Models\AuditActivity([
            'event' => 'updated', 'subject_type' => (new Complaint)->getMorphClass(),
            'properties' => [
                'old' => ['status' => 'pending', 'details' => 'قديم', 'message' => 'قديم', 'schedule_notes' => 'متابعة'],
                'attributes' => ['status' => 'in_progress', 'details' => 'جديد', 'message' => 'جديد', 'schedule_notes' => null, 'is_read' => 1, 'external_ref' => null],
            ],
        ]);
        $changes = ComplaintHistory::present($event)['changes'];
        $this->assertCount(3, $changes);
        $this->assertSame('قيد المعالجة', $changes[0]['after']);
        $this->assertTrue($changes[1]['removed']);
        $this->assertSame('جديد', $changes[2]['after']);
    }

    public function test_processing_duration_stops_when_the_complaint_is_resolved(): void
    {
        $complaint = $this->complaint();
        $complaint->forceFill(['created_at' => now()->subDays(10), 'status' => 'resolved', 'resolved_at' => now()->subDays(8)]);
        $summary = app(ComplaintHistory::class)->summary($complaint);
        $this->assertSame('مدة المعالجة', $summary['duration_label']);
        $this->assertSame($complaint->created_at->locale('ar')->diffForHumans($complaint->resolved_at, [
            'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2,
        ]), $summary['duration']);
    }

    public function test_new_actions_require_edit_or_control_permission(): void
    {
        foreach (['complaints.link', 'complaints.schedule'] as $route) {
            $this->assertSame([PermKeys::COMPLAINT_EDIT, PermKeys::COMPLAINT_CONTROL], AdminRoutePermissionMatrix::resolve($route));
        }
    }
}
