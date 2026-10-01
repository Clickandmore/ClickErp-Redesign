<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\ShelterContractController;
use App\Http\Middleware\ShelterDocumentMovement;
use App\Models\Contract;
use App\Models\ShelterDocument;
use App\Models\ShelterDocumentTemplate;
use App\Services\ShelterDocumentMovementService;
use App\Services\ShelterDocumentService;
use App\Support\ShelterDocumentFields;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShelterDocumentMovementTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    private function authorizedAdmin(): void
    {
        $admin = $this->globalAdmin();
        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => \App\PermKeys::SHELTER_CONTRACT_CONTROL, 'guard_name' => 'admin'],
            ['display_name' => 'Shelter control', 'group_name' => 'shelter_contracts', 'section_name' => 'operations_management', 'is_active' => true]
        );
        $admin->givePermissionTo($permission);
        $this->actingAsAdmin($admin);
    }

    private function contract(bool $configured = true): Contract
    {
        Cache::put('superadmin.modules.enabled_map', ['branches' => false], 60);
        $id = DB::table('contracts')->insertGetId([
            'type' => 'recruitment', 'status' => 'shelter', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('contract_infos')->insert([
            'contract_id' => $id, 'manual_maid_name' => 'عاملة اختبار', 'maid_date_back' => now()->toDateString(),
            'is_outside_shelter' => false,
        ]);
        $contract = Contract::findOrFail($id);
        if ($configured) {
            $input = [];
            foreach (['entry', 'release'] as $kind) {
                $template = ShelterDocumentTemplate::create(ShelterDocumentFields::starter($kind));
                $input[$kind] = ['template_id' => $template->id];
            }
            app(ShelterDocumentService::class)->save($contract, Request::create('/', 'POST', ['shelter_documents' => $input]));
        }
        return $contract;
    }

    private function release(Contract $contract): array
    {
        return [
            'shelter_release_version' => app(ShelterDocumentMovementService::class)->version($contract->fresh()),
            'shelter_release' => [
                'worker_name' => 'عاملة اختبار', 'date' => now()->toDateString(), 'exit_time' => '12:00',
                'reason' => ['other'], 'other_reason' => 'إجازة', 'recipient_name' => 'مستلم اختبار',
                'recipient_id' => '1234567890', 'recipient_phone' => '0500000000',
                'recipient_capacity' => 'مفوض', 'employee_name' => 'موظف اختبار',
            ],
        ];
    }

    private function runAction(Contract $contract, string $name, array $data, ?\Closure $next = null)
    {
        $request = Request::create('/movement/' . $contract->id, 'POST', $data);
        $route = new Route('POST', '/movement/{id}', fn () => null);
        $route->name($name)->bind($request);
        $request->setRouteResolver(fn () => $route);
        $this->app->instance('request', $request);
        $controller = app(ShelterContractController::class);
        return app(ShelterDocumentMovement::class)->handle($request, $next ?? function () use ($controller, $contract, $name) {
            return $name === 'ShelterContract.vacation'
                ? $controller->leaveForVacation($contract->id)
                : $controller->returnFromVacation($contract->id);
        });
    }

    public function test_selecting_release_template_does_not_issue_a_document(): void
    {
        $contract = $this->contract();
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('shelter_document_movements'));
        $this->assertNull(ShelterDocument::where('contract_id', $contract->id)->where('kind', 'release')->first()->release_completed_at);
    }

    public function test_exit_pauses_for_release_data_without_changing_presence(): void
    {
        $contract = $this->contract();
        $response = $this->runAction($contract, 'ShelterContract.vacation', []);
        $this->assertSame(409, $response->getStatusCode());
        $this->assertStringContainsString('release-form', $response->getData(true)['shelter_document_url']);
        $this->assertFalse($contract->info->fresh()->is_outside_shelter);
        $this->assertNull(ShelterDocument::where('contract_id', $contract->id)->where('kind', 'release')->first()->release_completed_at);
    }

    public function test_exit_then_return_then_exit_updates_current_data_without_copies(): void
    {
        $contract = $this->contract();
        $this->assertSame(200, $this->runAction($contract, 'ShelterContract.vacation', $this->release($contract))->getStatusCode());
        $first = ShelterDocument::where('contract_id', $contract->id)->where('kind', 'release')->firstOrFail();
        $this->assertNotNull($first->release_completed_at);
        $this->assertTrue($contract->info->fresh()->is_outside_shelter);
        $this->assertSame(200, $this->runAction($contract, 'ShelterContract.returnFromVacation', [])->getStatusCode());
        $this->assertFalse($contract->info->fresh()->is_outside_shelter);
        $this->assertNull($first->fresh()->release_completed_at);
        $payload = $this->release($contract);
        $payload['shelter_release']['recipient_name'] = 'مستلم جديد';
        $this->runAction($contract, 'ShelterContract.vacation', $payload);
        $this->assertSame(2, ShelterDocument::where('contract_id', $contract->id)->count());
        $this->assertSame('مستلم جديد', $first->fresh()->data['recipient_name']);
    }

    public function test_retry_does_not_issue_a_second_release(): void
    {
        $contract = $this->contract();
        $payload = $this->release($contract);
        $this->runAction($contract, 'ShelterContract.vacation', $payload);
        $this->assertSame(422, $this->runAction($contract, 'ShelterContract.vacation', $payload)->getStatusCode());
        $this->assertSame(1, ShelterDocument::where('contract_id', $contract->id)->whereNotNull('release_completed_at')->count());
    }

    public function test_stale_form_cannot_exit_a_new_admission(): void
    {
        $contract = $this->contract();
        $payload = $this->release($contract);
        $this->runAction($contract, 'ShelterContract.vacation', $payload);
        $this->runAction($contract, 'ShelterContract.returnFromVacation', []);
        $this->assertSame(409, $this->runAction($contract, 'ShelterContract.vacation', $payload)->getStatusCode());
        $this->assertFalse($contract->info->fresh()->is_outside_shelter);
        $this->assertSame(2, ShelterDocument::where('contract_id', $contract->id)->count());
    }

    public function test_failed_exit_rolls_back_changes_and_does_not_issue_document(): void
    {
        $contract = $this->contract();
        $response = $this->runAction($contract, 'ShelterContract.vacation', $this->release($contract), function () use ($contract) {
            $contract->info()->update(['is_outside_shelter' => true]);
            return response()->json(['success' => false], 422);
        });
        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($contract->info->fresh()->is_outside_shelter);
        $this->assertNull(ShelterDocument::where('contract_id', $contract->id)->where('kind', 'release')->first()->release_completed_at);
    }

    public function test_missing_recipient_is_rejected_before_exit(): void
    {
        $contract = $this->contract();
        $payload = $this->release($contract);
        unset($payload['shelter_release']['recipient_name']);
        try {
            $this->runAction($contract, 'ShelterContract.vacation', $payload);
            $this->fail('Expected validation failure.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('recipient_name', $e->errors());
        }
        $this->assertFalse($contract->info->fresh()->is_outside_shelter);
    }

    public function test_existing_company_without_selected_release_template_keeps_old_flow(): void
    {
        $contract = $this->contract(false);
        $this->assertSame(200, $this->runAction($contract, 'ShelterContract.vacation', [])->getStatusCode());
        $this->assertTrue($contract->info->fresh()->is_outside_shelter);
        $this->assertSame(0, ShelterDocument::where('contract_id', $contract->id)->count());
    }

    public function test_actual_admission_issues_entry_only_after_success(): void
    {
        $contract = $this->contract();
        DB::table('contracts')->where('id', $contract->id)->update(['status' => 'completed']);
        $response = $this->runAction($contract->fresh(), 'ShelterContract.store', [], function () use ($contract) {
            DB::table('contracts')->where('id', $contract->id)->update(['status' => 'shelter']);
            return response()->json(['success' => true]);
        });
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(2, ShelterDocument::where('contract_id', $contract->id)->count());
        $this->assertNull(ShelterDocument::where('contract_id', $contract->id)->where('kind', 'release')->first()->release_completed_at);
    }

    public function test_http_exit_opens_form_and_confirming_it_creates_a_printable_record(): void
    {
        $contract = $this->contract();
        $this->authorizedAdmin();
        $response = $this->postJson(route('ShelterContract.vacation', $contract->id));
        $response->assertStatus(409);
        $form = $this->get($response->json('shelter_document_url'));
        $form->assertOk()->assertSee('shelter_release_version', false)->assertSee('shelter_release[recipient_name]', false);
        $this->postJson(route('ShelterContract.vacation', $contract->id), $this->release($contract))->assertOk();
        $this->get(route('shelter_contract_form.download', [
            'id' => $contract->id, 'document' => 'release',
        ]))->assertOk()->assertSee('مستلم اختبار');
        $this->assertTrue($contract->info->fresh()->is_outside_shelter);
    }

    public function test_http_admission_saves_entry_and_only_configures_release(): void
    {
        $contract = $this->contract(false);
        DB::table('contracts')->where('id', $contract->id)->update(['status' => 'completed']);
        DB::table('contract_infos')->where('contract_id', $contract->id)->update(['arrival_date' => now()->toDateString()]);
        $this->authorizedAdmin();
        $shelterId = DB::table('shelter_places')->value('id');
        if (!$shelterId) $shelterId = DB::table('shelter_places')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $templates = [];
        foreach (['entry', 'release'] as $kind) {
            $template = ShelterDocumentTemplate::create(ShelterDocumentFields::starter($kind));
            $templates[$kind] = ['template_id' => $template->id];
        }
        $response = $this->postJson(route('ShelterContract.store'), [
            'contract_id' => $contract->id, 'shelter_id' => $shelterId, 'receipt_source' => 'client',
            'maid_date_back' => now()->toDateString(), 'duration_from_enter' => '1',
            'arrival_date' => now()->toDateString(), 'shelter_documents' => $templates,
        ]);
        $this->assertLessThan(300, $response->status(), $response->getContent());
        $this->assertSame(2, ShelterDocument::where('contract_id', $contract->id)->count());
        $this->get(route('shelter_contract_form.download', ['id' => $contract->id, 'document' => 'release']))->assertNotFound();
    }
}
