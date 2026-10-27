<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\RecruitmentContractExternalOfficeDispatchController;
use App\Models\Contract;
use App\Models\ContractExternalOfficeDispatch;
use App\Services\ManagedFileStorage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExternalOfficeDispatchAttachmentsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('superadmin.modules.enabled_map', ['branches' => false], 60);
    }

    private function contract(): Contract
    {
        $id = DB::table('contracts')->insertGetId(['type' => 'recruitment', 'status' => 'new', 'created_at' => now(), 'updated_at' => now()]);
        return Contract::findOrFail($id);
    }

    private function invoke(string $method, ...$arguments)
    {
        $reflection = new \ReflectionMethod(RecruitmentContractExternalOfficeDispatchController::class, $method);
        $reflection->setAccessible(true);
        return $reflection->invoke(app(RecruitmentContractExternalOfficeDispatchController::class), ...$arguments);
    }

    public function test_multiple_attachments_are_saved_appended_and_displayed(): void
    {
        $contract = $this->contract();
        $dispatch = ContractExternalOfficeDispatch::create(['contract_id' => $contract->id, 'attachments' => [
            ['path' => 'existing.pdf', 'name' => 'Original invoice.pdf'],
        ]]);
        $storage = $this->mock(ManagedFileStorage::class);
        $storage->shouldReceive('uploadFile')->twice()->andReturn('new-invoice.pdf', 'receipt.png');
        $storage->shouldReceive('url')->andReturnUsing(fn ($path) => '/storage/' . $path);
        $request = Request::create('/', 'POST', [], [], ['attachments' => [
            UploadedFile::fake()->create('New invoice.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->image('Receipt.png'),
        ]]);
        $this->invoke('saveWithAttachments', $request, function ($attachments) use ($contract) {
            $this->invoke('appendAttachments', $contract, $attachments);
        });
        $attachments = $dispatch->fresh()->attachments;
        $this->assertCount(3, $attachments);
        $this->assertSame('Original invoice.pdf', $attachments[0]['name']);
        $this->assertSame('New invoice.pdf', $attachments[1]['name']);
        $this->assertSame('receipt.png', $attachments[2]['path']);
        $html = view('admin.ingaz.RecruitmentContract.partials.dispatch-attachments', ['dispatch' => $dispatch->fresh()])->render();
        $this->assertStringContainsString('Original invoice.pdf', $html);
        $this->assertStringContainsString('New invoice.pdf', $html);
        $this->assertStringContainsString('Receipt.png', $html);
        $this->invoke('appendAttachments', $contract, []);
        $this->assertCount(3, $dispatch->fresh()->attachments);
    }

    public function test_failed_save_rolls_back_records_and_cleans_uploaded_files(): void
    {
        $contract = $this->contract();
        $storage = $this->mock(ManagedFileStorage::class);
        $storage->shouldReceive('uploadFile')->once()->andReturn('failed-save.pdf');
        $storage->shouldReceive('delete')->once()->with('failed-save.pdf')->andReturn(true);
        $request = Request::create('/', 'POST', [], [], ['attachments' => [UploadedFile::fake()->create('Invoice.pdf', 10, 'application/pdf')]]);
        try {
            $this->invoke('saveWithAttachments', $request, function ($attachments) use ($contract) {
                ContractExternalOfficeDispatch::create(['contract_id' => $contract->id, 'attachments' => $attachments]);
                throw new \RuntimeException('Accounting save failed');
            });
            $this->fail('Expected failed save.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Accounting save failed', $exception->getMessage());
        }
        $this->assertFalse(ContractExternalOfficeDispatch::where('contract_id', $contract->id)->exists());
    }

    public function test_bulk_save_links_the_same_uploaded_files_to_each_dispatch(): void
    {
        $contracts = [$this->contract(), $this->contract()];
        $storage = $this->mock(ManagedFileStorage::class);
        $storage->shouldReceive('uploadFile')->once()->andReturn('bulk-invoice.pdf');
        $request = Request::create('/', 'POST', [], [], ['attachments' => [UploadedFile::fake()->create('Bulk invoice.pdf', 10, 'application/pdf')]]);
        $this->invoke('saveWithAttachments', $request, function ($attachments) use ($contracts) {
            foreach ($contracts as $contract) {
                ContractExternalOfficeDispatch::create(['contract_id' => $contract->id]);
                $this->invoke('appendAttachments', $contract, $attachments);
            }
        });
        foreach ($contracts as $contract) {
            $this->assertSame('bulk-invoice.pdf', $contract->externalOfficeDispatch->attachments[0]['path']);
        }
    }

    public function test_unsupported_uploads_are_rejected_before_any_file_is_stored(): void
    {
        $admin = \App\Models\auth\Admin::factory()->create();
        $permission = \App\Models\Permission::withoutGlobalScopes()->updateOrCreate([
            'name' => \App\NewPermKeys::REC_CONTRACTS_UPDATE, 'guard_name' => 'admin',
        ], ['display_name' => 'Update recruitment', 'is_active' => true]);
        $admin->givePermissionTo($permission);
        $this->actingAs($admin, 'admin');
        $storage = $this->mock(ManagedFileStorage::class);
        $storage->shouldNotReceive('uploadFile');
        $request = Request::create('/', 'POST', [
            'dispatch_date' => now()->toDateString(), 'arrival_station' => 'Riyadh', 'office_contract_cost_usd' => 100,
        ], [], ['attachments' => [UploadedFile::fake()->create('script.php', 1, 'application/x-php')]]);
        try {
            app(RecruitmentContractExternalOfficeDispatchController::class)->store($request, $this->contract());
            $this->fail('Expected upload validation failure.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('attachments.0', $exception->errors());
        }
    }

    public function test_dispatch_page_shows_saved_files_and_multiple_upload_control(): void
    {
        $admin = \App\Models\auth\Admin::factory()->create();
        foreach ([\App\NewPermKeys::REC_CONTRACTS_VIEW, \App\NewPermKeys::REC_CONTRACTS_UPDATE, \App\NewPermKeys::REC_CONTRACTS_EDIT] as $name) {
            $permission = \App\Models\Permission::withoutGlobalScopes()->updateOrCreate([
                'name' => $name, 'guard_name' => 'admin',
            ], ['display_name' => $name, 'is_active' => true]);
            $admin->givePermissionTo($permission);
        }
        $this->actingAs($admin, 'admin');
        $contract = $this->contract();
        ContractExternalOfficeDispatch::create(['contract_id' => $contract->id, 'attachments' => [
            ['path' => 'saved-invoice.pdf', 'name' => 'Saved supplier invoice.pdf'],
        ]]);
        $this->mock(ManagedFileStorage::class)->shouldReceive('url')->andReturn('/storage/saved-invoice.pdf');
        $html = $this->get(route('RecruitmentContract.externalOfficeDispatch.edit', $contract->id))->assertOk()->getContent();
        $this->assertStringContainsString('Saved supplier invoice.pdf', $html);
        $this->assertStringContainsString('name="attachments[]"', $html);
        $this->assertStringContainsString('enctype="multipart/form-data"', $html);
    }
}
