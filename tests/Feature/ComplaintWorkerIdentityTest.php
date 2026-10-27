<?php

namespace Tests\Feature;

use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\ingaz\ComplaintController;
use App\Models\Contract;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\ComplaintType;
use App\Models\ingaz\Maid;
use App\Services\Complaints\ComplaintWorkerResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class ComplaintWorkerIdentityTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule(false);
    }

    private function maid(?int $branchId = null): Maid
    {
        $id = DB::table('maids')->insertGetId([
            'branch_id' => $branchId, 'name' => 'Complaint worker', 'passport_num' => 'COMPLAINT-' . uniqid(),
            'nationality_id' => DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]),
            'religion_id' => DB::table('religions')->insertGetId(['created_at' => now(), 'updated_at' => now()]),
            'cv_file' => '', 'height' => '', 'weight' => '', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return Maid::withoutBranchScope()->findOrFail($id);
    }

    private function save(array $extra = [], ?Complaint $existing = null)
    {
        $type = ComplaintType::withoutEvents(fn () => ComplaintType::create(['name' => 'Worker validation']));
        $request = Request::create('/', $existing ? 'PUT' : 'POST', $extra + [
            'complainant_name' => 'Complaint test', 'complainant_phone' => '0501234567',
            'complainant_type' => 'client', 'subject' => 'Worker identity test',
            'complaint_type_id' => $type->id, 'details' => 'Complaint details',
            'priority' => 'medium', 'relation_type' => 'none', 'status' => 'pending',
        ]);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        return Complaint::withoutEvents(fn () => $existing
            ? app(ComplaintController::class)->update($request, $existing->id)
            : app(ComplaintController::class)->store($request, app(UserAction::class)));
    }

    public function test_random_passport_is_rejected_on_create_and_update(): void
    {
        $response = $this->save(['maid_passport' => 'UNKNOWN-' . uniqid()]);
        $this->assertSame(422, $response->status(), $response->getContent());
        $this->assertArrayHasKey('maid_passport', $response->getData(true)['data']);
        $existing = Complaint::withoutEvents(fn () => Complaint::create(['subject' => 'Existing', 'status' => 'pending']));
        $response = $this->save(['maid_passport' => 'UNKNOWN-' . uniqid()], $existing);
        $this->assertSame(422, $response->status());
        $this->assertNull($existing->fresh()->maid_passport);
    }

    public function test_registered_worker_is_saved_with_canonical_identity(): void
    {
        $maid = $this->maid();
        $response = $this->save(['maid_passport' => ' ' . $maid->passport_num . ' ', 'maid_nationality_id' => null]);
        $this->assertSame(201, $response->getData(true)['code'], $response->getContent());
        $complaint = Complaint::where('maid_id', $maid->id)->firstOrFail();
        $this->assertSame('registered', $complaint->maid_registration_status);
        $this->assertSame($maid->passport_num, $complaint->maid_passport);
        $this->assertSame($maid->name, $complaint->maid_name);
        $this->assertEquals($maid->nationality_id, $complaint->maid_nationality_id);
    }

    public function test_unregistered_worker_requires_explicit_mode_and_name(): void
    {
        $passport = 'MANUAL' . strtoupper(substr(uniqid(), -8));
        $this->assertSame(422, $this->save(['maid_passport' => $passport, 'maid_registration_status' => 'unregistered'])->status());
        $this->assertSame(422, $this->save(['maid_passport' => 'جواز-12345!', 'maid_registration_status' => 'unregistered', 'maid_name' => 'Manual worker'])->status());
        $response = $this->save(['maid_passport' => $passport, 'maid_registration_status' => 'unregistered', 'maid_name' => 'Manual worker']);
        $this->assertSame(201, $response->getData(true)['code'], $response->getContent());
        $complaint = Complaint::where('maid_passport', $passport)->firstOrFail();
        $this->assertNull($complaint->maid_id);
        $this->assertSame('unregistered', $complaint->maid_registration_status);
        $this->assertSame('Manual worker', $complaint->maid_name);
    }

    public function test_forged_identity_and_manual_existing_worker_are_rejected(): void
    {
        $maid = $this->maid();
        $this->assertSame(422, $this->save(['maid_id' => $maid->id, 'maid_passport' => 'WRONG'])->status());
        $this->assertSame(422, $this->save(['maid_registration_status' => 'unregistered', 'maid_passport' => $maid->passport_num, 'maid_name' => 'Manual'])->status());
    }

    public function test_worker_must_match_the_related_contract(): void
    {
        $maid = $this->maid();
        $other = $this->maid();
        $contract = Contract::withoutEvents(fn () => Contract::create(['maid_id' => $maid->id, 'type' => 'recruitment', 'status' => 'new']));
        $response = $this->save(['relation_type' => 'contract', 'contract_id' => $contract->id, 'maid_passport' => $other->passport_num]);
        $this->assertSame(422, $response->status(), $response->getContent());
    }

    public function test_worker_details_remain_optional_for_general_complaints(): void
    {
        $response = $this->save();
        $this->assertSame(201, $response->getData(true)['code'], $response->getContent());
    }

    public function test_quick_link_cannot_change_a_verified_worker_to_a_different_contract_worker(): void
    {
        $maid = $this->maid();
        $other = $this->maid();
        $complaint = Complaint::withoutEvents(fn () => Complaint::create([
            'subject' => 'Verified worker', 'status' => 'pending', 'maid_id' => $maid->id,
            'maid_passport' => $maid->passport_num, 'maid_registration_status' => 'registered',
        ]));
        $contract = Contract::withoutEvents(fn () => Contract::create(['maid_id' => $other->id, 'type' => 'recruitment', 'status' => 'new']));
        $this->expectException(ValidationException::class);
        app(ComplaintController::class)->linkRecord(Request::create('/', 'POST', [
            'relation_type' => 'contract', 'contract_id' => $contract->id,
        ]), $complaint);
    }

    public function test_search_and_resolution_respect_branch_visibility(): void
    {
        $this->enableBranchesModule();
        $own = $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $maid = $this->maid($other->id);
        $this->actingAsAdmin($this->lockToOwnBranch($this->branchAdmin($own)));
        $response = app(ComplaintController::class)->searchMaids(Request::create('/', 'GET', ['q' => $maid->passport_num]));
        $this->assertSame([], $response->getData(true)['results']);
        $data = ['maid_id' => $maid->id, 'maid_passport' => $maid->passport_num];
        $this->expectException(ValidationException::class);
        app(ComplaintWorkerResolver::class)->resolve($data);
    }

    public function test_form_and_search_offer_registered_and_manual_worker_choices(): void
    {
        $permission = \App\Models\Permission::withoutGlobalScopes()->updateOrCreate([
            'name' => \App\PermKeys::COMPLAINT_CREATE, 'guard_name' => 'admin',
        ], ['display_name' => 'Create complaint', 'is_active' => true]);
        $admin = $this->globalAdmin();
        $admin->givePermissionTo($permission);
        $maid = $this->maid();
        $this->actingAsAdmin($admin)->get(route('complaints.create'))->assertOk()
            ->assertSee('العاملة غير مسجلة بالنظام')->assertSee('البحث عن العاملة بالاسم أو رقم الجواز');
        $this->getJson(route('complaints.search-maids', ['q' => $maid->passport_num]))->assertOk()
            ->assertJsonPath('results.0.id', $maid->id)->assertJsonPath('results.0.passport', $maid->passport_num);
    }
}
