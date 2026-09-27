<?php

namespace Tests\Feature;

use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\ingaz\ComplaintController;
use App\Http\Controllers\ingaz\ShelterContractController;
use App\Models\ingaz\Complaint;
use App\Models\ingaz\ComplaintType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShelterComplaintLinkTest extends TestCase
{
    use DatabaseTransactions;

    public function test_shelter_options_include_closed_and_unlinked_complaints(): void
    {
        Cache::put('superadmin.modules.enabled_map', ['branches' => false], 60);
        $complaint = Complaint::withoutEvents(fn () => Complaint::create([
            'subject' => 'شكوى غير مرتبطة', 'message' => 'تفاصيل',
            'status' => 'closed', 'priority' => 'medium', 'contract_id' => null,
        ]));
        $view = app(ShelterContractController::class)->create();
        $this->assertContains($complaint->id, $view->getData()['shelterComplaintOptions']->pluck('id')->all());
    }

    public function test_edit_form_includes_closed_and_unlinked_complaint_options(): void
    {
        Cache::put('superadmin.modules.enabled_map', ['branches' => false], 60);
        $contractId = DB::table('contracts')->insertGetId([
            'type' => 'recruitment', 'status' => 'shelter', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $complaint = Complaint::withoutEvents(fn () => Complaint::create([
            'subject' => 'شكوى غير مرتبطة', 'message' => 'تفاصيل',
            'status' => 'closed', 'priority' => 'medium', 'contract_id' => null,
        ]));

        $view = app(ShelterContractController::class)->edit($contractId);

        $this->assertSame('admin.ingaz.ShelterContract.create', $view->name());
        $this->assertTrue($view->getData()['isEditMode']);
        $this->assertContains($complaint->id, $view->getData()['shelterComplaintOptions']->pluck('id')->all());
    }

    public function test_creating_complaint_can_redirect_to_shelter_with_explicit_link(): void
    {
        Cache::put('superadmin.modules.enabled_map', ['branches' => false], 60);
        $contractId = DB::table('contracts')->insertGetId([
            'type' => 'recruitment', 'status' => 'arrival', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $type = ComplaintType::withoutEvents(fn () => ComplaintType::create(['name' => 'اختبار الإيواء']));
        $request = Request::create('/', 'POST', [
            'complainant_name' => 'اختبار الإيواء', 'complainant_phone' => '0501234567',
            'complainant_type' => 'client', 'subject' => 'ربط الإيواء',
            'complaint_type_id' => $type->id, 'details' => 'تفاصيل الشكوى',
            'priority' => 'medium', 'relation_type' => 'contract',
            'contract_id' => $contractId, 'after_save' => 'shelter',
        ]);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $response = Complaint::withoutEvents(fn () => app(ComplaintController::class)->store($request, app(UserAction::class)));
        $this->assertSame(201, $response->getData(true)['code'], $response->getContent());
        $complaint = Complaint::where('contract_id', $contractId)->firstOrFail();
        $this->assertSame('contract', $complaint->relation_type);
        $this->assertSame(route('ShelterContract.create', [
            'contract_id' => $contractId, 'shelter_complaint_id' => $complaint->id,
        ]), $response->getData(true)['data']['url']);
    }
    public function test_linking_an_existing_complaint_updates_its_relation_type(): void
    {
        Cache::put('superadmin.modules.enabled_map', ['branches' => false], 60);
        $contractId = DB::table('contracts')->insertGetId([
            'type' => 'recruitment', 'status' => 'shelter', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $complaint = Complaint::withoutEvents(fn () => Complaint::create([
            'subject' => 'شكوى يدوية', 'message' => 'تفاصيل', 'status' => 'closed',
            'priority' => 'medium', 'relation_type' => 'manual',
        ]));
        $method = new \ReflectionMethod(ShelterContractController::class, 'syncShelterComplaint');
        $method->setAccessible(true);
        $method->invoke(app(ShelterContractController::class), \App\Models\Contract::findOrFail($contractId),
            Request::create('/', 'POST', ['complaint_action' => 'link', 'shelter_complaint_id' => $complaint->id]));
        $this->assertEquals($contractId, $complaint->fresh()->contract_id);
        $this->assertSame('contract', $complaint->fresh()->relation_type);
    }

}
