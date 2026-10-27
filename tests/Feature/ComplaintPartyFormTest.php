<?php

namespace Tests\Feature;

use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\ingaz\ComplaintController;
use App\Models\Contract;
use App\Models\RecruitmentOffices;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\ComplaintType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

/**
 * نموذج الأطراف (party_form=1): مقدم الشكوى ثم المشتكى عليه حسب تصنيف الشكوى،
 * مع بقاء الطلبات القديمة والشكاوى المستوردة تعمل كما كانت.
 */
class ComplaintPartyFormTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule(false);
    }

    private function type(?string $from, ?string $against): ComplaintType
    {
        return ComplaintType::withoutEvents(fn () => ComplaintType::create([
            'name' => 'Party type ' . uniqid(), 'complainant_type' => $from, 'against_type' => $against,
        ]));
    }

    private function client(): User
    {
        return User::withoutEvents(fn () => User::create(['name' => 'عميل أطراف ' . uniqid(), 'phone' => '5' . random_int(10000000, 99999999)]));
    }

    private function maid(): Maid
    {
        $id = DB::table('maids')->insertGetId([
            'name' => 'Party worker', 'passport_num' => 'PARTY' . strtoupper(substr(uniqid(), -8)),
            'nationality_id' => DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]),
            'religion_id' => DB::table('religions')->insertGetId(['created_at' => now(), 'updated_at' => now()]),
            'cv_file' => '', 'height' => '', 'weight' => '', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return Maid::withoutBranchScope()->findOrFail($id);
    }

    private function office(): RecruitmentOffices
    {
        return RecruitmentOffices::create([
            'nationality_id' => 1, 'phone_office' => '0509999999',
            'ar' => ['title' => 'مكتب أطراف ' . uniqid()], 'en' => ['title' => 'Party office'],
        ])->refresh();
    }

    private function save(ComplaintType $type, array $extra, ?Complaint $existing = null)
    {
        $request = Request::create('/', $existing ? 'PUT' : 'POST', $extra + [
            'party_form' => '1', 'subject' => 'Party test', 'complaint_type_id' => $type->id,
            'details' => 'Complaint details', 'priority' => 'medium', 'relation_type' => 'none', 'status' => 'pending',
        ]);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        return Complaint::withoutEvents(fn () => $existing
            ? app(ComplaintController::class)->update($request, $existing->id)
            : app(ComplaintController::class)->store($request, app(UserAction::class)));
    }

    private function assertSaved($response): Complaint
    {
        $this->assertContains($response->getData(true)['code'] ?? null, [200, 201], $response->getContent());

        return Complaint::latest('id')->firstOrFail();
    }

    public function test_worker_against_client_takes_worker_then_client_data(): void
    {
        $client = $this->client();
        $complaint = $this->assertSaved($this->save($this->type('worker', 'client'), [
            'maid_registration_status' => 'unregistered', 'maid_name' => 'Unregistered worker', 'maid_passport' => 'ab123456',
            'related_user_id' => $client->id,
        ]));

        $this->assertSame('worker', $complaint->complainant_type);
        $this->assertSame('Unregistered worker', $complaint->complainant_name);
        $this->assertNull($complaint->user_id);
        $this->assertSame('client', $complaint->against_type);
        $this->assertSame($client->id, (int) $complaint->related_user_id);
        $this->assertSame($client->name, $complaint->against_name);
        $this->assertSame('AB123456', $complaint->maid_passport);
    }

    public function test_client_against_registered_worker(): void
    {
        $maid = $this->maid();
        $complaint = $this->assertSaved($this->save($this->type('client', 'worker'), [
            'complainant_name' => 'Manual client', 'complainant_phone' => '0551234567',
            'maid_registration_status' => 'registered', 'maid_id' => $maid->id,
        ]));

        $this->assertSame('Manual client', $complaint->complainant_name);
        $this->assertSame('551234567', $complaint->complainant_phone);
        $this->assertSame($maid->id, (int) $complaint->maid_id);
        $this->assertSame($maid->name, $complaint->against_name);
        $this->assertNull($complaint->related_user_id);
    }

    public function test_external_office_against_internal_office(): void
    {
        $office = $this->office();
        $complaint = $this->assertSaved($this->save($this->type('external_office', 'internal_office'), [
            'complainant_office_id' => $office->id,
        ]));

        $this->assertSame($office->id, (int) $complaint->recruitment_office_id);
        $this->assertSame($office->title, $complaint->complainant_name);
        $this->assertSame('المكتب', $complaint->against_name);
        $this->assertSame('none', $complaint->relation_type);
    }

    public function test_type_classification_overrides_submitted_party_types(): void
    {
        $complaint = $this->assertSaved($this->save($this->type('client', 'other'), [
            'complainant_type' => 'worker', 'against_type' => 'employee',
            'complainant_name' => 'Client', 'complainant_phone' => '0551234567',
        ] + ['against_admin_id' => $this->globalAdmin()->id]));

        // «أخرى» في التصنيف تترك صفة المشتكى عليه لاختيار الموظف، وصفة مقدم الشكوى مفروضة
        $this->assertSame('client', $complaint->complainant_type);
        $this->assertSame('employee', $complaint->against_type);
    }

    public function test_missing_party_records_are_rejected(): void
    {
        $this->assertSame(422, $this->save($this->type('worker', 'client'), [])->status());
        $this->assertSame(422, $this->save($this->type('client', 'client'), [
            'complainant_name' => 'Client', 'complainant_phone' => '0551234567',
        ])->status());
        $this->assertSame(422, $this->save($this->type('external_office', 'internal_office'), [])->status());
        $this->assertSame(422, $this->save($this->type('client', 'worker'), [
            'complainant_name' => 'Client', // client complainant needs a phone
        ])->status());
    }

    public function test_client_against_party_falls_back_to_linked_contract_client(): void
    {
        $client = $this->client();
        $contract = Contract::withoutEvents(fn () => Contract::create(['user_id' => $client->id, 'type' => 'recruitment', 'status' => 'new']));

        $complaint = $this->assertSaved($this->save($this->type('internal_office', 'client'), [
            'relation_type' => 'contract', 'contract_id' => $contract->id,
        ]));

        $this->assertSame($client->id, (int) $complaint->related_user_id);
        $this->assertSame($client->name, $complaint->against_name);
        $this->assertSame($contract->id, (int) $complaint->contract_id);
    }

    public function test_editing_imported_complaint_keeps_unregistered_worker_name(): void
    {
        $type = $this->type(null, null);
        $existing = Complaint::withoutEvents(fn () => Complaint::create([
            'subject' => 'Imported', 'status' => 'pending', 'complaint_type_id' => $type->id,
            'complainant_type' => 'عميل', 'complainant_name' => 'Imported client', 'complainant_phone' => '551112233',
            'against_type' => 'عاملـ/ـة', 'against_name' => 'Imported worker',
        ]));

        $this->assertSaved($this->save($type, [
            'complainant_type' => 'client', 'against_type' => 'worker',
            'complainant_name' => 'Imported client', 'complainant_phone' => '0551112233',
            'maid_registration_status' => 'registered',
        ], $existing));

        $existing->refresh();
        $this->assertSame('client', $existing->complainant_type);
        $this->assertSame('worker', $existing->against_type);
        $this->assertSame('Imported worker', $existing->against_name);
    }

    public function test_requests_without_party_form_keep_previous_rules(): void
    {
        $request = Request::create('/', 'POST', [
            'complainant_name' => 'Legacy', 'complainant_type' => 'client', 'subject' => 'Legacy',
            'complaint_type_id' => $this->type('client', 'worker')->id, 'details' => 'x', 'priority' => 'medium', 'relation_type' => 'none',
        ]);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        // الجوال ما زال إلزاميًا في المسار القديم
        $response = Complaint::withoutEvents(fn () => app(ComplaintController::class)->store($request, app(UserAction::class)));
        $this->assertSame(422, $response->status());
        $this->assertArrayHasKey('complainant_phone', $response->getData(true)['data']);
    }

    public function test_party_type_normalization_supports_imported_arabic_values(): void
    {
        $this->assertSame('client', ComplaintType::normalizePartyType('عميل'));
        $this->assertSame('worker', ComplaintType::normalizePartyType('عاملـ/ـة'));
        $this->assertSame('external_office', ComplaintType::normalizePartyType('مكتب خارجي'));
        $this->assertSame('internal_office', ComplaintType::normalizePartyType('المكتب'));
        $this->assertSame('employee', ComplaintType::normalizePartyType('employee'));
        $this->assertNull(ComplaintType::normalizePartyType(''));
    }

    public function test_create_and_edit_pages_render_party_sections(): void
    {
        $admin = $this->globalAdmin();
        $admin->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate(\App\PermKeys::COMPLAINT_CREATE, 'admin'));
        $admin->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate(\App\PermKeys::COMPLAINT_EDIT, 'admin'));
        $this->actingAsAdmin($admin);

        $this->get(route('complaints.create'))->assertOk()
            ->assertSee('name="party_form"', false)
            ->assertSee('بيانات المشتكى عليه', false);

        $existing = Complaint::withoutEvents(fn () => Complaint::create([
            'subject' => 'Imported view', 'status' => 'pending', 'complaint_type_id' => $this->type(null, null)->id,
            'complainant_type' => 'عميل', 'against_type' => 'عاملـ/ـة', 'against_name' => 'Imported worker',
        ]));
        $html = $this->get(route('complaints.edit', $existing->id))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/id="againstType".*?<option value="worker" selected/s', $html);
    }
}
