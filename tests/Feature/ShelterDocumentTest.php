<?php

namespace Tests\Feature;

use App\Http\Controllers\admin\FormSettingsController;
use App\Http\Controllers\ingaz\ShelterContractController;
use App\Models\Contract;
use App\Models\ShelterDocument;
use App\Models\ShelterDocumentTemplate;
use App\Services\ShelterDocumentService;
use App\Support\ShelterDocumentFields;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShelterDocumentTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    private function contract(): Contract
    {
        Cache::put('superadmin.modules.enabled_map', ['branches' => false], 60);
        $id = DB::table('contracts')->insertGetId([
            'type' => 'recruitment', 'status' => 'shelter', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('contract_infos')->insert([
            'contract_id' => $id, 'manual_maid_name' => 'عاملـة اختبار',
            'manual_maid_passport_num' => 'P12345', 'maid_date_back' => '2026-09-20',
            'arrival_date' => '2026-09-10',
        ]);
        return Contract::findOrFail($id);
    }

    private function template(string $kind = 'entry'): ShelterDocumentTemplate
    {
        return ShelterDocumentTemplate::create(ShelterDocumentFields::starter($kind));
    }

    private function save(Contract $contract, ShelterDocumentTemplate $template, array $data = []): ShelterDocument
    {
        DB::transaction(fn () => app(ShelterDocumentService::class)->save($contract, Request::create('/', 'POST', [
            'shelter_documents' => [$template->kind => ['template_id' => $template->id, 'data' => $data]],
        ])));
        return ShelterDocument::where('contract_id', $contract->id)->where('kind', $template->kind)->firstOrFail();
    }

    public function test_legacy_submission_does_not_create_or_select_a_template(): void
    {
        $contract = $this->contract();
        $this->template();
        app(ShelterDocumentService::class)->save($contract, Request::create('/', 'POST'));
        $this->assertSame(0, ShelterDocument::where('contract_id', $contract->id)->count());
    }

    public function test_selected_template_fills_known_data_and_saves_manual_fields_independently(): void
    {
        $contract = $this->contract();
        $entry = $this->save($contract, $this->template(), ['remarks' => 'متعلقات مستلمة', 'reason' => ['refusal']]);
        $release = $this->save($contract, $this->template('release'), ['recipient_name' => 'مستلم آخر', 'exit_time' => '14:30']);
        $this->assertSame('عاملـة اختبار', $entry->data['worker_name']);
        $this->assertSame('P12345', $entry->data['passport']);
        $this->assertSame('2026-09-20', $entry->data['entry_date']);
        $this->assertSame('2026-09-10', $entry->data['arrival_date']);
        $this->assertSame(['refusal'], $entry->data['reason']);
        $this->assertSame('مستلم آخر', $release->data['recipient_name']);
        $this->assertSame('14:30', $release->data['exit_time']);
        $this->assertArrayNotHasKey('date', $release->data);
        $this->assertSame('shelter', $contract->fresh()->status);
        $this->assertSame('متعلقات مستلمة', $entry->fresh()->data['remarks']);
    }

    public function test_template_edits_apply_to_current_print_without_storing_a_snapshot(): void
    {
        $contract = $this->contract();
        $template = $this->template();
        $document = $this->save($contract, $template);
        $template->update(['declaration' => 'نص جديد', 'company_name' => 'شركة أخرى', 'active' => false]);
        $updated = $this->save($contract, $template, ['remarks' => 'تحديث الحقول']);
        $this->assertArrayNotHasKey('template_snapshot', $updated->getAttributes());
        $this->assertSame('نص جديد', $updated->template->printValues()['declaration']);
        $this->app->instance('request', Request::create('/', 'GET'));
        $printed = app(ShelterContractController::class)->deliveryShelterContractDownload($contract->id);
        $this->assertStringContainsString('نص جديد', $printed->getContent());
        $this->assertSame('تحديث الحقول', $updated->data['remarks']);
        $this->assertFalse(app(ShelterDocumentService::class)->editor(null)['templates']->contains('id', $template->id));
    }

    public function test_another_contract_cannot_select_an_inactive_template(): void
    {
        $template = $this->template();
        $this->save($this->contract(), $template);
        $template->update(['active' => false]);
        $this->expectException(ValidationException::class);
        $this->save($this->contract(), $template);
    }

    public function test_template_kind_cannot_be_used_for_the_other_document(): void
    {
        $template = $this->template('release');
        $this->expectException(ValidationException::class);
        app(ShelterDocumentService::class)->save($this->contract(), Request::create('/', 'POST', [
            'shelter_documents' => ['entry' => ['template_id' => $template->id]],
        ]));
    }

    public function test_invalid_field_values_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        $this->save($this->contract(), $this->template(), ['reason' => ['unknown'], 'date' => 'not-a-date']);
    }

    public function test_unchecking_choices_clears_them_and_legacy_selection_retains_saved_data(): void
    {
        $contract = $this->contract();
        $template = $this->template();
        $this->save($contract, $template, ['belongings' => ['passport'], 'remarks' => 'محفوظ']);
        $document = $this->save($contract, $template);
        $this->assertSame([], $document->data['belongings']);
        app(ShelterDocumentService::class)->save($contract, Request::create('/', 'POST', [
            'shelter_documents' => ['entry' => ['template_id' => '']],
        ]));
        $this->assertFalse($document->fresh()->enabled);
        $this->assertSame('محفوظ', $document->fresh()->data['remarks']);
    }

    public function test_print_uses_current_template_and_escapes_user_content(): void
    {
        $document = $this->save($this->contract(), $this->template(), ['remarks' => '<script>alert(1)</script>']);
        $html = view('admin.ingaz.ShelterContract.custom-document-print', [
            'kind' => 'entry', 'template' => $document->template->printValues(), 'data' => $document->data,
        ])->render();
        $this->assertStringContainsString(e($document->template->printValues()['company_name']), $html);
        $this->assertStringNotContainsString('[اسم_الشركة]', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('P12345', $html);
    }

    public function test_editor_keeps_saved_selection_and_allows_editing_a_disabled_template(): void
    {
        $contract = $this->contract();
        $template = $this->template();
        $this->save($contract, $template, ['worker_name' => 'اسم مصحح']);
        $template->update(['active' => false]);
        $html = view('admin.ingaz.ShelterContract.partials.custom-documents', [
            'editingContract' => $contract, 'isEditMode' => true, 'selectedContractId' => $contract->id,
        ])->render();
        $this->assertStringContainsString('value="' . $template->id . '" selected', $html);
        $this->assertStringContainsString('اسم مصحح', $html);
        $this->assertStringNotContainsString('@selected', $html);
    }

    public function test_existing_print_link_uses_selected_entry_and_legacy_remains_accessible(): void
    {
        $contract = $this->contract();
        $this->save($contract, $this->template(), ['worker_name' => 'الاسم المحفوظ']);
        $this->app->instance('request', Request::create('/', 'GET'));
        $response = app(ShelterContractController::class)->deliveryShelterContractDownload($contract->id);
        $this->assertStringContainsString('DOMESTIC WORKER RECEIPT FORM', $response->getContent());
        $this->assertStringContainsString('الاسم المحفوظ', $response->getContent());

        $this->app->instance('request', Request::create('/', 'GET', ['document' => 'legacy']));
        $response = app(ShelterContractController::class)->deliveryShelterContractDownload($contract->id);
        $this->assertStringNotContainsString('DOMESTIC WORKER RECEIPT FORM', $response->getContent());
    }

    public function test_contract_without_selection_still_prints_the_legacy_form(): void
    {
        $contract = $this->contract();
        $this->app->instance('request', Request::create('/', 'GET'));
        $response = app(ShelterContractController::class)->deliveryShelterContractDownload($contract->id);
        $this->assertStringNotContainsString('DOMESTIC WORKER RECEIPT FORM', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_template_management_creates_a_template_without_changing_other_templates(): void
    {
        $existing = $this->template();
        app(FormSettingsController::class)->saveShelterTemplate(Request::create('/', 'POST',
            array_merge(ShelterDocumentFields::starter('release'), ['company_name' => 'شركة ثانية'])
        ));
        $this->assertSame(ShelterDocumentFields::starter('entry')['company_name'], $existing->fresh()->company_name);
        $this->assertDatabaseHas('shelter_document_templates', ['company_name' => 'شركة ثانية', 'kind' => 'release']);
    }

    public function test_contract_update_rolls_back_when_selected_template_is_invalid(): void
    {
        $contract = $this->contract();
        $shelterId = DB::table('shelter_places')->value('id');
        if (!$shelterId) $shelterId = DB::table('shelter_places')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        try {
            app(ShelterContractController::class)->update(Request::create('/', 'PUT', [
                'shelter_id' => $shelterId, 'receipt_source' => 'client',
                'maid_date_back' => '2026-09-22', 'duration_from_enter' => '2', 'arrival_date' => '2026-09-10',
                'shelter_documents' => ['entry' => ['template_id' => 999999999]],
            ]), $contract->id);
            $this->fail('Invalid template must reject the contract update.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('shelter_documents.entry.template_id', $e->errors());
        }
        $this->assertSame('2026-09-20', $contract->info->fresh()->maid_date_back);
    }

    public function test_custom_print_refuses_a_contract_in_another_branch(): void
    {
        $contract = $this->contract();
        $this->save($contract, $this->template());
        $this->setUpBranches();
        $this->enableBranchesModule();
        $this->makeBranch(['is_main' => true]);
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        DB::table('contracts')->where('id', $contract->id)->update(['branch_id' => $other->id]);
        $viewer = $this->lockToOwnBranch($this->branchAdmin($own));
        $permission = \App\Models\Permission::firstOrCreate(
            ['name' => \App\PermKeys::SHELTER_CONTRACT_CONTROL, 'guard_name' => 'admin'],
            ['display_name' => 'Shelter control', 'group_name' => 'shelter_contracts', 'section_name' => 'operations_management', 'is_active' => true]
        );
        $viewer->givePermissionTo($permission);
        $this->actingAsAdmin($viewer)->get(route('shelter_contract_form.download', [
            'id' => $contract->id, 'document' => 'entry',
        ]))->assertNotFound();
    }

    public function test_admin_without_settings_control_cannot_add_templates(): void
    {
        $admin = $this->globalAdmin();
        $admin->syncRoles([]);
        $admin->syncPermissions([]);
        $before = ShelterDocumentTemplate::count();
        $this->actingAsAdmin($admin)->postJson(route('form-settings.shelter-templates.save'),
            ShelterDocumentFields::starter('entry'))->assertForbidden();
        $this->assertSame($before, ShelterDocumentTemplate::count());
    }
}
