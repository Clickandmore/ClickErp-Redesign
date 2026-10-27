<?php

namespace Tests\Feature\Branch;

use App\Models\Contract;
use App\Models\Permission;
use App\Models\SponsorshipTransfer;
use App\Models\TransferServiceContract;
use App\PermKeys;
use App\Services\CrossBranchTransferService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class CrossBranchTransferTest extends TestCase
{
    use DatabaseTransactions, InteractsWithBranches;

    private $source;
    private $destination;
    private $admin;
    private int $contractId;
    private int $clientId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
        $this->source = $this->makeBranch(['is_main' => true]);
        $this->destination = $this->makeBranch();
        $this->admin = $this->lockToOwnBranch($this->branchAdmin($this->destination));
        $this->grant(PermKeys::SPONSOR_TRANSFER_CREATE);
        $this->grant(PermKeys::SPONSOR_TRANSFER_VIEW);
        $this->grant(PermKeys::SPONSOR_TRANSFER_EDIT);
        $this->grant(PermKeys::SHELTER_CONTRACT_VIEW);
        $this->clientId = $this->client($this->destination->id);
        $oldClient = $this->client($this->source->id);
        $maid = DB::table('maids')->insertGetId([
            'branch_id' => $this->source->id, 'name' => 'Cross branch worker',
            'passport_num' => 'CROSS-' . uniqid(),
            'nationality_id' => DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]),
            'religion_id' => DB::table('religions')->insertGetId(['created_at' => now(), 'updated_at' => now()]),
            'cv_file' => '', 'height' => '', 'weight' => '', 'status' => 'available',
            'available' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->contractId = DB::table('contracts')->insertGetId([
            'branch_id' => $this->source->id, 'maid_id' => $maid, 'user_id' => $oldClient,
            'status' => 'shelter', 'type' => 'recruitment', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('contract_infos')->insert([
            'contract_id' => $this->contractId, 'branch_id' => $this->source->id,
            'wants_to_work' => 'yes', 'is_outside_shelter' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $nationalityId = DB::table('maids')->where('id', $maid)->value('nationality_id');
        DB::table('nationalities_translations')->insert([
            'nationality_id' => $nationalityId, 'locale' => 'ar', 'title' => 'Test nationality', 'sub_title' => 'Test nationality',
        ]);
        app(CrossBranchTransferService::class)->sync([$this->source->id => [$this->destination->id => true]]);
        $this->actingAsAdmin($this->admin);
    }

    private function grant(string $name): void
    {
        Permission::withoutGlobalScopes()->updateOrCreate(['name' => $name, 'guard_name' => 'admin'], [
            'display_name' => $name, 'group_name' => 'sponsor_transfer',
            'section_name' => 'additional_services', 'is_active' => true,
        ]);
        $this->admin->givePermissionTo($name);
    }

    private function client(int $branch): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Cross branch client', 'branch_id' => $branch, 'phone_code' => '+966',
            'phone' => '5' . random_int(10000000, 99999999), 'password' => bcrypt('password'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function payload(): array
    {
        return [
            'contract_id' => $this->contractId, 'new_sponsor_mode' => 'existing',
            'new_sponsor_id' => $this->clientId, 'marketer_id' => $this->admin->id,
            'trial_days' => 7, 'trial_start_date' => now()->toDateString(),
            'contract_value' => 10000, 'paid_amount' => 0,
            'payment_type' => 'pay_later', 'payment_status' => 'later',
        ];
    }

    public function test_policy_alone_does_not_allow_foreign_workers(): void
    {
        $this->assertFalse(app(CrossBranchTransferService::class)->candidates()->whereKey($this->contractId)->exists());
        $this->postJson(route('SponsorshipTransfer.store'), $this->payload())->assertStatus(422);
        $this->get(route('SponsorshipTransfer.create', ['branch_id' => $this->source->id]))
            ->assertOk()->assertViewHas('contracts', fn ($contracts) => !$contracts->contains('id', $this->contractId));
    }

    private function useCompanySponsor(): \App\Models\CompanyProfile
    {
        $company = \App\Models\CompanyProfile::create([
            'setting_id' => random_int(1000000, 2000000000), 'name' => 'Transfer source company', 'account_id' => 987654,
        ]);
        DB::table('contracts')->where('id', $this->contractId)->update([
            'user_id' => null, 'company_profile_id' => $company->id, 'company_finance_account_id' => 987655,
        ]);
        $this->grant(PermKeys::SPONSOR_TRANSFER_CROSS_BRANCH);
        return $company;
    }

    public function test_company_sponsored_worker_can_be_created_and_edited_without_a_customer_id(): void
    {
        $company = $this->useCompanySponsor();
        $usersBefore = DB::table('users')->count();
        $response = $this->postJson(route('SponsorshipTransfer.store'), $this->payload())->assertOk()->assertJsonPath('success', true);
        $transfer = SponsorshipTransfer::findOrFail($response->json('transfer_id'));
        $this->assertNull($transfer->old_sponsor_id);
        $this->assertEquals($company->id, $transfer->old_company_profile_id);
        $this->assertEquals(987655, $transfer->old_company_finance_account_id);
        $this->assertSame($company->name, $transfer->oldSponsor->name);
        $this->assertSame('standard', $transfer->workflow_source);
        $this->assertNull($transfer->contract->user_id);
        $this->assertSame($usersBefore, DB::table('users')->count());
        $this->get(route('SponsorshipTransfer.show', $transfer->id))->assertOk()->assertSee($company->name);
        $this->putJson(route('SponsorshipTransfer.update', $transfer->id), $this->payload() + ['notes' => 'Company transfer edit'])
            ->assertOk()->assertJsonPath('success', true);
        $this->assertEquals($company->id, $transfer->fresh()->old_company_profile_id);
        $this->assertEquals(987655, $transfer->fresh()->old_company_finance_account_id);
        $document = TransferServiceContract::create([
            'sponsorship_transfer_id' => $transfer->id, 'contract_id' => $this->contractId,
            'old_sponsor_id' => null, 'new_sponsor_id' => $this->clientId,
            'maid_id' => $transfer->contract->maid_id, 'contract_number' => 'COMPANY-' . uniqid(),
            'contract_value' => 10000, 'paid_amount' => 0, 'remaining_amount' => 10000,
            'trial_days' => 7, 'trial_start_date' => now(), 'status' => 'draft',
        ]);
        $this->assertSame($company->name, $document->oldSponsor->name);
        $this->assertEquals(987655, $document->oldSponsor->account_id);
        $this->grant(PermKeys::SPONSOR_TRANSFER_CONTROL);
        $this->mock(\App\Services\ingaz\SponsorshipTransferCompletionService::class)
            ->shouldReceive('record')->once()->andReturn(null);
        $transfer->update(['status' => 'trial_started']);
        $this->postJson(route('SponsorshipTransfer.confirmTransfer', $transfer->id))->assertOk()->assertJsonPath('success', true);
        $transfer->refresh();
        $this->assertEquals($this->clientId, $transfer->contract->user_id);
        $this->assertNull($transfer->contract->company_profile_id);
        $this->assertSame($company->name, $transfer->oldSponsor->name);
        $this->assertEquals(987655, $transfer->old_company_finance_account_id);
    }

    public function test_missing_or_conflicting_current_sponsors_are_rejected(): void
    {
        $company = $this->useCompanySponsor();
        DB::table('contracts')->where('id', $this->contractId)->update(['user_id' => $this->clientId]);
        $this->postJson(route('SponsorshipTransfer.store'), $this->payload())->assertStatus(422)->assertJsonValidationErrors('contract_id');
        DB::table('contracts')->where('id', $this->contractId)->update(['user_id' => null, 'company_profile_id' => null]);
        $this->postJson(route('SponsorshipTransfer.store'), $this->payload())->assertStatus(422)->assertJsonValidationErrors('contract_id');
        DB::table('contracts')->where('id', $this->contractId)->update(['company_profile_id' => $company->id + 1000000]);
        $this->postJson(route('SponsorshipTransfer.store'), $this->payload())->assertStatus(422)->assertJsonValidationErrors('contract_id');
    }

    public function test_reserving_company_worker_for_replacement_preserves_current_sponsor_identity(): void
    {
        $company = $this->useCompanySponsor();
        $contract = app(CrossBranchTransferService::class)->reserve($this->contractId);
        $this->assertNull($contract->user_id);
        $this->assertEquals($company->id, $contract->company_profile_id);
        $this->assertSame($company->name, $contract->user->name);
    }

    public function test_source_branch_labels_only_appear_when_branches_are_enabled(): void
    {
        $this->enableBranchesModule(false);
        $this->get(route('SponsorshipTransfer.create'))->assertOk()
            ->assertSee('Cross branch worker')->assertDontSee('فرع المصدر:')->assertDontSee('يتبع فرعاً آخر');

        $this->enableBranchesModule();
        \App\Support\BranchQueryContext::reset();
        $this->grant(PermKeys::SPONSOR_TRANSFER_CROSS_BRANCH);
        $this->get(route('SponsorshipTransfer.create'))->assertOk()
            ->assertSee('Cross branch worker')->assertSee('فرع المصدر:');
    }

    public function test_permission_requires_directional_policy_and_eligible_worker(): void
    {
        $this->grant(PermKeys::SPONSOR_TRANSFER_CROSS_BRANCH);
        $service = app(CrossBranchTransferService::class);
        $contract = $service->candidates()->findOrFail($this->contractId);
        $this->assertSame('Cross branch worker', $contract->maid->name);
        $this->assertSame('yes', $contract->info->wants_to_work);
        $this->assertNotNull($contract->user);
        $this->get(route('SponsorshipTransfer.create'))->assertOk()->assertSee('Cross branch worker')
            ->assertViewHas('contracts', fn ($contracts) => $contracts->contains('id', $this->contractId));
        $this->assertFalse(Contract::whereKey($this->contractId)->exists());
        $service->sync([$this->destination->id => [$this->source->id => true]]);
        $this->assertFalse($service->candidates()->whereKey($this->contractId)->exists());
        $service->sync([$this->source->id => [$this->destination->id => true]]);
        DB::table('contract_infos')->where('contract_id', $this->contractId)->update(['wants_to_work' => 'no']);
        $this->assertFalse($service->candidates()->whereKey($this->contractId)->exists());
    }

    public function test_foreign_worker_can_be_created_and_viewed_without_moving_source(): void
    {
        $this->grant(PermKeys::SPONSOR_TRANSFER_CROSS_BRANCH);
        $response = $this->postJson(route('SponsorshipTransfer.store'), $this->payload())
            ->assertOk()->assertJsonPath('success', true);
        $transfer = SponsorshipTransfer::findOrFail($response->json('transfer_id'));
        $this->assertSame($this->destination->id, $transfer->branch_id);
        $this->assertSame($this->source->id, $transfer->contract->branch_id);
        $this->assertSame('Cross branch worker', $transfer->contract->maid->name);
        $this->assertNotNull($transfer->oldSponsor);
        $this->get(route('SponsorshipTransfer.show', $transfer->id))->assertOk()->assertSee('Cross branch worker');
        $this->assertContains($this->get(route('ShelterContract.show', $this->contractId))->status(), [403, 404]);
        $this->postJson(route('SponsorshipTransfer.store'), $this->payload())->assertStatus(422);
        $this->assertFalse(app(CrossBranchTransferService::class)->candidates()->whereKey($this->contractId)->exists());
        $transfer->update(['status' => 'failed']);
        $this->assertTrue(app(CrossBranchTransferService::class)->candidates()->whereKey($this->contractId)->exists());
    }

    public function test_related_contract_inherits_executing_branch_and_source_survives_revocation(): void
    {
        $this->grant(PermKeys::SPONSOR_TRANSFER_CROSS_BRANCH);
        $response = $this->postJson(route('SponsorshipTransfer.store'), $this->payload())->assertOk();
        $transfer = SponsorshipTransfer::findOrFail($response->json('transfer_id'));
        $contract = TransferServiceContract::create([
            'sponsorship_transfer_id' => $transfer->id, 'contract_id' => $this->contractId,
            'old_sponsor_id' => $transfer->old_sponsor_id, 'new_sponsor_id' => $this->clientId,
            'maid_id' => $transfer->contract->maid_id, 'contract_number' => 'CROSS-' . uniqid(),
            'contract_value' => 10000, 'paid_amount' => 0, 'remaining_amount' => 10000,
            'trial_days' => 7, 'trial_start_date' => now(), 'status' => 'draft',
        ]);
        $this->assertSame($this->destination->id, $contract->branch_id);
        app(CrossBranchTransferService::class)->sync([]);
        $this->assertNotNull($transfer->fresh()->contract->info);
        $this->assertNotNull($contract->fresh()->maid);
        $this->get(route('SponsorshipTransfer.edit', $transfer->id))->assertOk()
            ->assertViewHas('contracts', fn ($contracts) => $contracts->contains('id', $this->contractId));
        $this->putJson(route('SponsorshipTransfer.update', $transfer->id), $this->payload() + ['notes' => 'Updated after revocation'])
            ->assertOk()->assertJsonPath('success', true);
    }

    public function test_existing_reservation_in_another_branch_is_enforced(): void
    {
        $this->grant(PermKeys::SPONSOR_TRANSFER_CROSS_BRANCH);
        $source = app(CrossBranchTransferService::class)->candidates()->findOrFail($this->contractId);
        $reservation = SponsorshipTransfer::create([
            'branch_id' => $this->source->id, 'contract_id' => $this->contractId,
            'old_sponsor_id' => $source->user_id, 'new_sponsor_id' => $this->clientId,
            'trial_days' => 7, 'trial_start_date' => now(), 'contract_value' => 10000,
            'paid_amount' => 0, 'remaining_amount' => 10000, 'status' => 'waiting_contract_send',
        ]);
        $this->assertNull(SponsorshipTransfer::find($reservation->id));
        $this->assertFalse(app(CrossBranchTransferService::class)->candidates()->whereKey($this->contractId)->exists());
        $this->postJson(route('SponsorshipTransfer.store'), $this->payload())->assertStatus(422);
    }

    public function test_transfer_release_resolves_only_an_accessible_transfer(): void
    {
        $this->grant(PermKeys::SPONSOR_TRANSFER_CROSS_BRANCH);
        $response = $this->postJson(route('SponsorshipTransfer.store'), $this->payload())->assertOk();
        $id = $response->json('transfer_id');
        $service = app(\App\Services\ShelterDocumentMovementService::class);
        $source = $service->resolve('transfer', $id);
        $this->assertSame($this->contractId, $source->id);
        $this->assertTrue($service->inside($source));
        app(CrossBranchTransferService::class)->sync([]);
        $this->assertSame($this->contractId, $service->resolve('transfer', $id)->id);
        $this->actingAsAdmin($this->branchAdmin($this->source));
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $service->resolve('transfer', $id);
    }

    public function test_branch_settings_save_directional_transfer_policy(): void
    {
        $this->grant(PermKeys::BRANCHES_CONTROL);
        $this->get(route('branches.settings'))->assertOk()->assertSee('إتاحة عاملات الإيواء لنقل الخدمات بين الفروع');
        $this->post(route('branches.settings.update'), [
            'transfer_sharing' => [$this->source->id => [$this->destination->id => '1']],
        ])->assertRedirect(route('branches.settings'));
        $this->assertTrue(app(CrossBranchTransferService::class)->policies()[$this->source->id][$this->destination->id]);
        $this->post(route('branches.settings.update'), [
            'transfer_sharing' => [$this->source->id => [$this->destination->id => '0']],
        ])->assertRedirect();
        $this->assertSame([], app(CrossBranchTransferService::class)->policies());
    }
}
