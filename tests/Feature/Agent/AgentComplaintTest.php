<?php

namespace Tests\Feature\Agent;

use App\Enums\ingaz\ComplaintSubmissionMethodEnum;
use App\Http\Controllers\Agent\AgentComplaintController;
use App\Models\Contract;
use App\Models\RecruitmentOffices;
use App\Models\auth\Admin;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\ComplaintType;
use App\Models\ingaz\Maid;
use App\Models\office\AdminOffice;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * بوابة شكاوى المكتب الخارجي: العزل بين المكاتب هو جوهر الشاشة —
 * الوكيل يرى شكاوى مكتبه ويربطها بعقود مكتبه فقط.
 *
 * قراءات الصفحات تُستدعى من الكنترولر مباشرة لأن AgentLocalizationMiddleware
 * يحوّل طلبات GET إلى مسار اللغة، وهو مسار غير مسجّل داخل عملية الاختبار.
 */
class AgentComplaintTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('superadmin.modules.enabled_map', ['branches' => false], 60);
    }

    public function test_index_lists_only_the_office_complaints(): void
    {
        [$agent, $office] = $this->agentWithOffice();
        $otherOffice = $this->office();

        $ownComplaint = $this->complaint(['recruitment_office_id' => $office->id]);
        $foreignComplaint = $this->complaint(['recruitment_office_id' => $otherOffice->id]);

        $listed = $this->listedComplaintIds($agent);

        $this->assertTrue($listed->contains($ownComplaint->id));
        $this->assertFalse($listed->contains($foreignComplaint->id));
    }

    public function test_complaint_on_an_office_contract_is_visible_even_without_the_office_stamp(): void
    {
        [$agent, $office] = $this->agentWithOffice();
        $contract = $this->contractForOffice($office);

        $complaint = $this->complaint(['contract_id' => $contract->id, 'relation_type' => 'contract']);

        $this->assertTrue($this->listedComplaintIds($agent)->contains($complaint->id));
    }

    public function test_index_statistics_count_only_office_complaints(): void
    {
        [$agent, $office] = $this->agentWithOffice();

        $this->complaint(['recruitment_office_id' => $office->id, 'status' => 'pending']);
        $this->complaint(['recruitment_office_id' => $office->id, 'status' => 'resolved']);
        $this->complaint(['recruitment_office_id' => $this->office()->id, 'status' => 'pending']);

        $stats = $this->indexView($agent)->getData()['stats'];

        $this->assertSame(2, $stats['total']);
        $this->assertSame(1, $stats['pending']);
        $this->assertSame(1, $stats['resolved']);
    }

    public function test_agent_files_a_complaint_linked_to_an_office_contract(): void
    {
        [$agent, $office] = $this->agentWithOffice();
        $contract = $this->contractForOffice($office);
        $type = ComplaintType::create([
            'name' => 'تأخر صرف مستحقات',
            'against_type' => 'internal_office',
            'is_active' => true,
        ]);

        $response = $this->actingAs($agent, 'agent')->post(route('agent.complaints.store'), [
            'complainant_name' => 'مسؤول المكتب',
            'complainant_phone' => '00963111222',
            'subject' => 'تأخر التحويل',
            'complaint_type_id' => $type->id,
            'details' => 'لم يتم تحويل مستحقات العقد حتى الآن.',
            'priority' => 'high',
            'relation_type' => 'contract',
            'contract_id' => $contract->id,
        ]);

        $complaint = Complaint::where('subject', 'تأخر التحويل')->firstOrFail();

        $response->assertRedirect(route('agent.complaints.show', $complaint->id));
        $this->assertSame('pending', $complaint->status);
        $this->assertSame('external_office', $complaint->complainant_type);
        $this->assertSame('internal_office', $complaint->against_type);
        $this->assertSame(ComplaintSubmissionMethodEnum::AgentPortal->value, $complaint->submission_method);
        $this->assertSame($office->id, (int) $complaint->recruitment_office_id);
        $this->assertSame($contract->id, (int) $complaint->contract_id);
        $this->assertSame('contract', $complaint->relation_type);
        // message يبقى مرآة لـ details حتى تعمل الشاشات القديمة التي تقرأ الحقل الأول.
        $this->assertSame($complaint->details, $complaint->message);
    }

    public function test_manual_party_complaint_keeps_the_typed_party_and_the_office_stamp(): void
    {
        [$agent, $office] = $this->agentWithOffice();
        $type = ComplaintType::create(['name' => 'نوع اختباري', 'is_active' => true]);

        $this->actingAs($agent, 'agent')
            ->post(route('agent.complaints.store'), [
                'complainant_name' => 'مسؤول المكتب',
                'subject' => 'طرف يدوي',
                'complaint_type_id' => $type->id,
                'details' => 'تفاصيل',
                'priority' => 'medium',
                'relation_type' => 'manual',
                'against_name' => 'شركة النقل',
                'against_phone' => '0551234567',
            ])
            ->assertRedirect();

        $complaint = Complaint::where('subject', 'طرف يدوي')->firstOrFail();

        $this->assertSame('شركة النقل', $complaint->against_name);
        $this->assertSame('0551234567', $complaint->against_phone);
        $this->assertSame('other', $complaint->against_type);
        $this->assertNull($complaint->contract_id);
        $this->assertSame($office->id, (int) $complaint->recruitment_office_id);
    }

    public function test_manual_relation_requires_the_party_name(): void
    {
        [$agent] = $this->agentWithOffice();
        $type = ComplaintType::create(['name' => 'نوع اختباري', 'is_active' => true]);

        $this->actingAs($agent, 'agent')
            ->post(route('agent.complaints.store'), [
                'complainant_name' => 'مسؤول المكتب',
                'subject' => 'بدون طرف',
                'complaint_type_id' => $type->id,
                'details' => 'تفاصيل',
                'priority' => 'medium',
                'relation_type' => 'manual',
            ])
            ->assertSessionHasErrors('against_name');

        $this->assertDatabaseMissing('complaints', ['subject' => 'بدون طرف']);
    }

    public function test_multi_office_agent_files_the_complaint_under_the_chosen_office(): void
    {
        [$agent] = $this->agentWithOffice();
        $secondOffice = $this->office();
        AdminOffice::create(['admin_id' => $agent->id, 'office_id' => $secondOffice->id]);
        $type = ComplaintType::create(['name' => 'نوع اختباري', 'is_active' => true]);

        $payload = [
            'complainant_name' => 'مسؤول المكتب',
            'complaint_type_id' => $type->id,
            'details' => 'تفاصيل',
            'priority' => 'medium',
            'relation_type' => 'none',
        ];

        $this->actingAs($agent->fresh(), 'agent')
            ->post(route('agent.complaints.store'), $payload + [
                'subject' => 'شكوى المكتب الثاني',
                'office_id' => $secondOffice->id,
            ])
            ->assertRedirect();

        $this->assertSame(
            $secondOffice->id,
            (int) Complaint::where('subject', 'شكوى المكتب الثاني')->value('recruitment_office_id')
        );

        // مكتب غير مرتبط بالحساب يُرفَض، ولا يُستبدل صامتاً بمكتب الوكيل.
        $this->actingAs($agent->fresh(), 'agent')
            ->post(route('agent.complaints.store'), $payload + [
                'subject' => 'شكوى بمكتب غريب',
                'office_id' => $this->office()->id,
            ])
            ->assertSessionHasErrors('office_id');

        $this->assertDatabaseMissing('complaints', ['subject' => 'شكوى بمكتب غريب']);
    }

    public function test_agent_cannot_link_a_complaint_to_a_contract_of_another_office(): void
    {
        [$agent] = $this->agentWithOffice();
        $foreignContract = $this->contractForOffice($this->office());
        $type = ComplaintType::create(['name' => 'نوع اختباري', 'is_active' => true]);

        $this->actingAs($agent, 'agent')
            ->post(route('agent.complaints.store'), [
                'complainant_name' => 'مسؤول المكتب',
                'subject' => 'محاولة ربط بعقد مكتب آخر',
                'complaint_type_id' => $type->id,
                'details' => 'تفاصيل',
                'priority' => 'low',
                'relation_type' => 'contract',
                'contract_id' => $foreignContract->id,
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('complaints', ['subject' => 'محاولة ربط بعقد مكتب آخر']);
    }

    public function test_contract_search_returns_only_office_contracts(): void
    {
        [$agent, $office] = $this->agentWithOffice();
        $ownContract = $this->contractForOffice($office);
        $foreignContract = $this->contractForOffice($this->office());

        $this->actingAs($agent, 'agent');
        $response = app(AgentComplaintController::class)->searchContracts(Request::create('/', 'GET'));
        $ids = collect(json_decode($response->getContent(), true)['results'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $this->assertTrue($ids->contains($ownContract->id));
        $this->assertFalse($ids->contains($foreignContract->id));
    }

    public function test_agent_cannot_open_a_complaint_of_another_office(): void
    {
        [$agent] = $this->agentWithOffice();
        $foreignComplaint = $this->complaint(['recruitment_office_id' => $this->office()->id]);

        $this->actingAs($agent, 'agent');

        $this->expectException(ModelNotFoundException::class);
        app(AgentComplaintController::class)->show($foreignComplaint->id);
    }

    public function test_agent_reply_is_stored_against_the_complaint(): void
    {
        [$agent, $office] = $this->agentWithOffice();
        $complaint = $this->complaint(['recruitment_office_id' => $office->id]);

        $this->actingAs($agent, 'agent')
            ->post(route('agent.complaints.comments.store', $complaint->id), ['comment' => 'العاملة وصلت بالفعل.'])
            ->assertRedirect(route('agent.complaints.show', $complaint->id));

        $this->assertDatabaseHas('complaint_comments', [
            'complaint_id' => $complaint->id,
            'added_by' => $agent->id,
            'comment' => 'العاملة وصلت بالفعل.',
        ]);
    }

    public function test_agent_cannot_reply_to_a_complaint_of_another_office(): void
    {
        [$agent] = $this->agentWithOffice();
        $foreignComplaint = $this->complaint(['recruitment_office_id' => $this->office()->id]);

        $this->actingAs($agent, 'agent')
            ->post(route('agent.complaints.comments.store', $foreignComplaint->id), ['comment' => 'تعليق'])
            ->assertNotFound();

        $this->assertDatabaseMissing('complaint_comments', ['complaint_id' => $foreignComplaint->id]);
    }

    public function test_the_three_screens_render(): void
    {
        [$agent, $office] = $this->agentWithOffice();
        $contract = $this->contractForOffice($office);
        ComplaintType::create(['name' => 'نوع اختباري', 'complainant_type' => 'external_office', 'is_active' => true]);
        $complaint = $this->complaint([
            'recruitment_office_id' => $office->id,
            'contract_id' => $contract->id,
            'relation_type' => 'contract',
            'response' => 'تمت المتابعة مع العميل.',
        ]);

        $this->actingAs($agent, 'agent');
        // ShareErrorsFromSession جزء من مجموعة web وحدها، والعرض هنا يتم خارج دورة الطلب.
        view()->share('errors', new ViewErrorBag());
        $controller = app(AgentComplaintController::class);

        $indexHtml = $controller->index(Request::create('/', 'GET'))->render();
        $createHtml = $controller->create()->render();
        $showHtml = $controller->show($complaint->id)->render();

        $this->assertStringContainsString('#' . $complaint->id, $indexHtml);
        $this->assertStringContainsString('نوع اختباري', $createHtml);
        $this->assertStringContainsString('تمت المتابعة مع العميل.', $showHtml);
    }

    private function indexView(Admin $agent): \Illuminate\View\View
    {
        $this->actingAs($agent, 'agent');

        return app(AgentComplaintController::class)->index(Request::create('/', 'GET'));
    }

    private function listedComplaintIds(Admin $agent): Collection
    {
        return collect($this->indexView($agent)->getData()['complaints']->items())->pluck('id');
    }

    /** @return array{0: Admin, 1: RecruitmentOffices} */
    private function agentWithOffice(): array
    {
        $office = $this->office();
        $agent = Admin::factory()->create(['admin_type' => 'agent']);
        AdminOffice::create(['admin_id' => $agent->id, 'office_id' => $office->id]);

        return [$agent->fresh(), $office];
    }

    private function office(): RecruitmentOffices
    {
        return RecruitmentOffices::create([
            'nationality_id' => 1,
            'phone_office' => '0501234567',
            'ar' => ['title' => 'مكتب اختبار ' . uniqid()],
            'en' => ['title' => 'Test Office'],
        ])->refresh();
    }

    private function contractForOffice(RecruitmentOffices $office): Contract
    {
        $maid = Maid::create([
            'name' => 'عاملة اختبار',
            'office_id' => $office->id,
            'passport_num' => 'P' . random_int(100000, 999999),
        ]);

        return Contract::create(['maid_id' => $maid->id, 'type' => 'recruitment', 'status' => 'new']);
    }

    private function complaint(array $attributes = []): Complaint
    {
        return Complaint::withoutEvents(fn () => Complaint::create($attributes + [
            'subject' => 'شكوى اختبارية',
            'details' => 'تفاصيل الشكوى',
            'message' => 'تفاصيل الشكوى',
            'status' => 'pending',
            'priority' => 'medium',
            'complainant_type' => 'external_office',
        ]));
    }
}
