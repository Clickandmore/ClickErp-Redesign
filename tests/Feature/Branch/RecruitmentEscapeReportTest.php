<?php

namespace Tests\Feature\Branch;

use App\Models\Contract;
use App\Models\Permission;
use App\PermKeys;
use App\Services\ManagedFileStorage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class RecruitmentEscapeReportTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBranches;

    private $branch;
    private $viewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
        $this->branch = $this->makeBranch();
        $this->viewer = $this->branchAdmin($this->branch);
        foreach ([PermKeys::REC_CONTRACTS_ESCAPE_REPORTS_VIEW, PermKeys::REC_CONTRACTS_ESCAPE_REPORT_CREATE, PermKeys::REC_CONTRACTS_ESCAPE_REPORT_DELETE, \App\NewPermKeys::REC_CONTRACTS_VIEW] as $name) {
            Permission::updateOrCreate(['name' => $name, 'guard_name' => 'admin'], ['display_name' => $name, 'group_name' => 'recruitment_contracts', 'section_name' => 'operations_management', 'is_active' => true]);
            $this->viewer->givePermissionTo($name);
        }
        $this->actingAsAdmin($this->viewer);
        $storage = Mockery::mock(ManagedFileStorage::class);
        $storage->shouldReceive('uploadFile')->andReturn('escape-test/document.pdf');
        $storage->shouldReceive('delete')->andReturn(true);
        $this->app->instance(ManagedFileStorage::class, $storage);
    }

    private function contract(string $status = 'completed', ?int $branch = null): Contract
    {
        $id = DB::table('contracts')->insertGetId(['type' => 'recruitment', 'status' => $status, 'branch_id' => $branch ?? $this->branch->id, 'created_at' => now(), 'updated_at' => now()]);
        return Contract::withoutGlobalScopes()->findOrFail($id);
    }

    private function payload(): array
    {
        return ['escape_date' => now()->toDateString(), 'notes' => 'Test report', 'attachments' => [UploadedFile::fake()->create('document.pdf', 10, 'application/pdf')]];
    }

    public function test_report_can_be_created_viewed_and_deleted_without_changing_contract_status(): void
    {
        $contract = $this->contract();
        $this->get(route('RecruitmentContract.escapeReports.show', $contract->id))->assertOk()->assertSee('escape_attachments');
        $this->post(route('RecruitmentContract.escapeReports.store', $contract->id), $this->payload())->assertRedirect();
        $report = $contract->escapeReports()->firstOrFail();
        $this->assertSame('completed', $contract->fresh()->status);
        $this->assertTrue($contract->fresh()->hasEscapeReport());
        $this->assertSame('escape-test/document.pdf', $report->attachments[0]['path']);
        $this->get(route('RecruitmentContract.escapeReports.show', $contract->id))->assertOk()->assertSee('Test report');
        $this->get(route('RecruitmentContract.escapeReports.index'))->assertOk()->assertSee('إدارة البلاغ');
        $this->delete(route('RecruitmentContract.escapeReports.destroy', [$contract->id, $report->id]))->assertRedirect();
        $this->assertFalse($contract->fresh()->hasEscapeReport());
        $this->assertSame('completed', $contract->fresh()->status);
    }

    public function test_duplicate_and_non_completed_contracts_are_rejected(): void
    {
        $contract = $this->contract();
        $url = route('RecruitmentContract.escapeReports.store', $contract->id);
        $this->post($url, $this->payload())->assertRedirect();
        $this->postJson($url, $this->payload())->assertUnprocessable();
        $this->assertSame(1, $contract->escapeReports()->count());
        $this->postJson(route('RecruitmentContract.escapeReports.store', $this->contract('new')->id), $this->payload())->assertUnprocessable();
    }

    public function test_attachment_and_date_validation(): void
    {
        $url = route('RecruitmentContract.escapeReports.store', $this->contract()->id);
        $this->postJson($url, ['escape_date' => now()->toDateString()])->assertUnprocessable()->assertJsonValidationErrors('attachments');
        $data = $this->payload();
        $data['escape_date'] = now()->addDays(6)->toDateString();
        $this->postJson($url, $data)->assertUnprocessable()->assertJsonValidationErrors('escape_date');
    }

    public function test_other_branch_contracts_and_attachments_are_inaccessible(): void
    {
        $other = $this->makeBranch();
        $contract = $this->contract('completed', $other->id);
        $response = $this->get(route('RecruitmentContract.escapeReports.show', $contract->id));
        $this->assertContains($response->status(), [403, 404]);
        $response = $this->postJson(route('RecruitmentContract.escapeReports.store', $contract->id), $this->payload());
        $this->assertContains($response->status(), [403, 404]);
    }

    public function test_create_requires_its_own_permission(): void
    {
        $this->viewer->revokePermissionTo(PermKeys::REC_CONTRACTS_ESCAPE_REPORT_CREATE);
        $this->postJson(route('RecruitmentContract.escapeReports.store', $this->contract()->id), $this->payload())->assertForbidden();
    }

    public function test_listing_combines_both_sources_with_badges_links_and_branch_isolation(): void
    {
        $contract = $this->contract();
        $this->post(route('RecruitmentContract.escapeReports.store', $contract->id), $this->payload())->assertRedirect();
        $shelter = $this->contract('shelter');
        $other = $this->contract('shelter', $this->makeBranch()->id);
        foreach ([$shelter, $other] as $item) {
            \App\Models\ContractInfo::create([
                'contract_id' => $item->id, 'outside_shelter_reason' => 'escape_report',
                'outside_shelter_at' => now()->subDay(), 'outside_shelter_by' => $this->viewer->id,
            ]);
        }
        $ordinary = $this->contract('shelter');
        \App\Models\ContractInfo::create(['contract_id' => $ordinary->id, 'outside_shelter_reason' => 'other']);

        $response = $this->get(route('RecruitmentContract.escapeReports.index'));
        $response->assertOk()->assertSee('الاستقدام')->assertSee('الإيواء')
            ->assertSee(route('ShelterContract.show', $shelter->id), false)
            ->assertSee(route('RecruitmentContract.escapeReports.show', $contract->id), false);
        $reports = $response->viewData('reports');
        $this->assertSame(2, $reports->total());
        $this->assertSame([$contract->id, $shelter->id], $reports->getCollection()->pluck('contract_id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_reports_from_both_sources_on_same_contract_are_preserved_and_paginated_together(): void
    {
        $contract = $this->contract();
        $this->post(route('RecruitmentContract.escapeReports.store', $contract->id), $this->payload())->assertRedirect();
        \App\Models\ContractInfo::create(['contract_id' => $contract->id, 'outside_shelter_reason' => 'escape_report']);
        for ($i = 0; $i < 25; $i++) {
            $shelter = $this->contract('shelter');
            \App\Models\ContractInfo::create(['contract_id' => $shelter->id, 'outside_shelter_reason' => 'escape_report', 'outside_shelter_at' => now()->subDay()]);
        }
        $first = $this->get(route('RecruitmentContract.escapeReports.index'))->assertOk()->viewData('reports');
        $second = $this->get(route('RecruitmentContract.escapeReports.index', ['page' => 2]))->assertOk()->viewData('reports');
        $this->assertSame(27, $first->total());
        $this->assertCount(25, $first->items());
        $this->assertCount(2, $second->items());
        $all = $first->getCollection()->concat($second->getCollection());
        $this->assertCount(27, $all->unique(fn ($report) => $report->source . ':' . $report->id));
        $this->assertSame(2, $all->where('contract_id', $contract->id)->count());
    }

    public function test_compensation_formula_uses_contract_value_duration_and_escape_date(): void
    {
        $contract = $this->contract();
        \App\Models\ContractInfo::create([
            'contract_id' => $contract->id, 'contract_value' => 7300,
            'contract_duration' => 2, 'ingaz_from' => '2025-01-01',
        ]);
        $data = $this->payload();
        $data['escape_date'] = '2026-01-01';
        $this->post(route('RecruitmentContract.escapeReports.store', $contract->id), $data)->assertRedirect();
        $notes = $contract->escapeReports()->firstOrFail()->notes;
        $this->assertStringContainsString('730 يوم', $notes);
        $this->assertStringContainsString('365 يوم', $notes);
        $this->assertStringContainsString('3,650.00 ريال', $notes);
    }

    public function test_failed_upload_removes_previously_uploaded_files_and_does_not_create_a_report(): void
    {
        $storage = Mockery::mock(ManagedFileStorage::class);
        $storage->shouldReceive('uploadFile')->once()->ordered()->andReturn('escape-test/first.pdf');
        $storage->shouldReceive('uploadFile')->once()->ordered()->andThrow(new \RuntimeException('Upload failed'));
        $storage->shouldReceive('delete')->once()->with('escape-test/first.pdf')->andReturn(true);
        $this->app->instance(ManagedFileStorage::class, $storage);
        $contract = $this->contract();
        $data = $this->payload();
        $data['attachments'][] = UploadedFile::fake()->create('second.pdf', 10, 'application/pdf');
        $this->postJson(route('RecruitmentContract.escapeReports.store', $contract->id), $data)->assertStatus(500);
        $this->assertFalse($contract->escapeReports()->exists());
    }

    public function test_delete_and_attachment_access_require_separate_permissions(): void
    {
        $contract = $this->contract();
        $this->post(route('RecruitmentContract.escapeReports.store', $contract->id), $this->payload())->assertRedirect();
        $report = $contract->escapeReports()->firstOrFail();
        $this->viewer->revokePermissionTo(PermKeys::REC_CONTRACTS_ESCAPE_REPORT_DELETE);
        $this->deleteJson(route('RecruitmentContract.escapeReports.destroy', [$contract->id, $report->id]))->assertForbidden();
        $this->viewer->revokePermissionTo(PermKeys::REC_CONTRACTS_ESCAPE_REPORTS_VIEW);
        $this->get(route('RecruitmentContract.escapeReports.attachment', [$contract->id, $report->id, 0]))->assertForbidden();
    }
}
