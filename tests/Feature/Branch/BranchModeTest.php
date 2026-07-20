<?php

namespace Tests\Feature\Branch;

use App\Models\auth\Admin;
use App\Models\FinancialRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

/**
 * The two operating modes of the system, side by side.
 *
 * The whole branches feature hangs off one switch — SystemModuleManager::isEnabled('branches'),
 * driven by the `branches` row in system_module_settings. Every test here runs the SAME
 * scenario twice, once with the switch on and once off, so the two modes are pinned against
 * each other rather than each being described in isolation.
 *
 * ON  — the branch is visible in the UI, new records are stamped with it, lists are filtered
 *       to it, and reaching into another branch is refused.
 * OFF — the system behaves as a single-branch business: no stamping, no filtering, no branch
 *       UI, and the branch screens are closed.
 *
 * Financial requests are the worked example because they carry branch_id end to end:
 * a create form, a validated store, a filtered index and a guarded show.
 */
class BranchModeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
    }

    /**
     * An admin holding full permissions. A null branch means head office — no branch_id,
     * which is the "sees every branch" case.
     */
    private function staffOf($branch): Admin
    {
        $factory = Admin::factory();
        $factory = $branch ? $factory->forBranch($branch) : $factory->global();

        $admin = $factory->create();
        $admin->assignRole('Administrator');

        return $admin;
    }

    private function financialRequestPayload(?int $branchId): array
    {
        return [
            'type' => 'other_financial',
            'priority' => 'normal',
            'amount' => 100,
            'request_date' => now()->toDateString(),
            'description' => 'طلب اختبار',
            'branch_id' => $branchId,
            'payload' => [
                'paid_to' => 'مستفيد اختباري',
                'accounting_name' => 'مصروف اختباري',
                'debit_credit' => 'debit',
                'payment_method' => 'cash',
            ],
        ];
    }

    private function makeRequestFor($branch, string $requestNo): FinancialRequest
    {
        $item = FinancialRequest::query()->create([
            'request_no' => $requestNo,
            'type' => 'other_financial',
            'priority' => FinancialRequest::PRIORITY_NORMAL,
            'status' => FinancialRequest::STATUS_FOR_APPROVAL,
            'request_date' => now()->toDateString(),
        ]);

        $item->updateQuietly(['branch_id' => $branch?->id]);

        return $item->fresh();
    }

    // ---------------------------------------------------------------- the switch itself

    /** @test */
    public function the_module_is_off_by_default(): void
    {
        // config/superadmin_modules.php marks branches as default_enabled => false, so a
        // fresh install runs without branches until somebody turns them on.
        $this->assertFalse(
            config('superadmin_modules.branches.default_enabled'),
            'A new installation must start in single-branch mode.'
        );
    }

    /** @test */
    public function the_switch_can_be_turned_on_and_off_at_runtime(): void
    {
        $this->enableBranchesModule(true);
        $this->assertTrue(app(\App\Services\BranchAccessService::class)->enabled());

        $this->enableBranchesModule(false);
        $this->assertFalse(
            app(\App\Services\BranchAccessService::class)->enabled(),
            'Flipping the setting must take effect immediately, not after the cache expires.'
        );
    }

    // ---------------------------------------------------------------- branch screens

    /** @test */
    public function on_the_branch_screens_are_reachable(): void
    {
        $this->enableBranchesModule(true);
        $this->actingAsAdmin($this->staffOf(null));

        $this->get(route('branches.index'))->assertOk();
    }

    /** @test */
    public function off_the_branch_screens_are_closed(): void
    {
        $this->enableBranchesModule(false);
        $this->actingAsAdmin($this->staffOf(null));

        $this->get(route('branches.index'))
            ->assertRedirect(route('admin.no-permissions'));
    }

    /** @test */
    public function off_the_branch_endpoints_refuse_json_callers(): void
    {
        $branch = $this->makeBranch();
        $this->enableBranchesModule(false);
        $this->actingAsAdmin($this->staffOf(null));

        $this->getJson(route('branches.admins', $branch))
            ->assertStatus(403)
            ->assertJson(['module' => 'branches']);
    }

    // ---------------------------------------------------------------- the branch is visible

    /** @test */
    public function on_the_admins_branch_is_shown_in_the_interface(): void
    {
        $branch = $this->makeBranch(['name_ar' => 'فرع الظهران المميز']);
        $this->enableBranchesModule(true);
        $this->actingAsAdmin($this->staffOf($branch));

        $this->get(route('financial-requests.index'))
            ->assertOk()
            ->assertSee('فرع الظهران المميز', false);
    }

    /** @test */
    public function off_the_branch_is_not_shown_in_the_interface(): void
    {
        $branch = $this->makeBranch(['name_ar' => 'فرع الظهران المميز']);
        $this->enableBranchesModule(false);
        $this->actingAsAdmin($this->staffOf($branch));

        $this->get(route('financial-requests.index'))
            ->assertOk()
            ->assertDontSee('فرع الظهران المميز', false);
    }

    // ---------------------------------------------------------------- operations are stamped

    /** @test */
    public function on_a_new_operation_is_recorded_against_the_admins_branch(): void
    {
        $branch = $this->makeBranch();
        $this->enableBranchesModule(true);
        $this->actingAsAdmin($this->staffOf($branch));

        $this->post(route('financial-requests.store'), $this->financialRequestPayload($branch->id))
            ->assertSessionHasNoErrors();

        $this->assertSame(
            $branch->id,
            (int) FinancialRequest::query()->latest('id')->firstOrFail()->branch_id
        );
    }

    /** @test */
    public function on_an_operation_cannot_be_filed_against_another_branch(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $this->enableBranchesModule(true);
        $this->actingAsAdmin($this->staffOf($own));

        $this->post(route('financial-requests.store'), $this->financialRequestPayload($other->id))
            ->assertStatus(403);
    }

    /** @test */
    public function off_an_operation_may_be_filed_against_any_branch(): void
    {
        // The mirror of on_an_operation_cannot_be_filed_against_another_branch: with the
        // module off, the cross-branch guard must not fire at all.
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $this->enableBranchesModule(false);
        $this->actingAsAdmin($this->staffOf($own));

        $this->post(route('financial-requests.store'), $this->financialRequestPayload($other->id))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('financial-requests.index'));

        $this->assertSame(
            $other->id,
            (int) FinancialRequest::query()->latest('id')->firstOrFail()->branch_id,
            'The submitted branch is stored verbatim — nothing overrides or rejects it.'
        );
    }

    /** @test */
    public function off_a_record_created_without_a_branch_stays_without_one(): void
    {
        $branch = $this->makeBranch();
        $this->enableBranchesModule(false);
        $this->actingAsAdmin($this->staffOf($branch));

        $record = FinancialRequest::query()->create([
            'request_no' => 'FR-MODE-OFF-' . uniqid(),
            'type' => 'other_financial',
            'priority' => FinancialRequest::PRIORITY_NORMAL,
            'status' => FinancialRequest::STATUS_FOR_APPROVAL,
            'request_date' => now()->toDateString(),
        ]);

        $this->assertNull(
            $record->fresh()->branch_id,
            'With branches off nothing may be stamped, even for an admin who has a branch.'
        );
    }

    /** @test */
    public function on_a_record_created_without_a_branch_is_stamped_automatically(): void
    {
        $branch = $this->makeBranch();
        $this->enableBranchesModule(true);
        $this->actingAsAdmin($this->staffOf($branch));

        $record = FinancialRequest::query()->create([
            'request_no' => 'FR-MODE-ON-' . uniqid(),
            'type' => 'other_financial',
            'priority' => FinancialRequest::PRIORITY_NORMAL,
            'status' => FinancialRequest::STATUS_FOR_APPROVAL,
            'request_date' => now()->toDateString(),
        ]);

        $this->assertSame(
            $branch->id,
            (int) $record->fresh()->branch_id,
            'With branches on the acting admin\'s branch is filled in automatically.'
        );
    }

    // ---------------------------------------------------------------- lists are scoped

    /** @test */
    public function on_the_list_hides_other_branches_records(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();

        $mine = $this->makeRequestFor($own, 'FR-MINE-' . uniqid());
        $theirs = $this->makeRequestFor($other, 'FR-THEIRS-' . uniqid());

        $this->enableBranchesModule(true);
        $this->actingAsAdmin($this->staffOf($own));

        $this->get(route('financial-requests.index'))
            ->assertOk()
            ->assertSee($mine->request_no)
            ->assertDontSee($theirs->request_no);
    }

    /** @test */
    public function off_the_list_shows_every_record_regardless_of_branch(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();

        $mine = $this->makeRequestFor($own, 'FR-MINE-' . uniqid());
        $theirs = $this->makeRequestFor($other, 'FR-THEIRS-' . uniqid());

        $this->enableBranchesModule(false);
        $this->actingAsAdmin($this->staffOf($own));

        $this->get(route('financial-requests.index'))
            ->assertOk()
            ->assertSee($mine->request_no)
            ->assertSee($theirs->request_no, false);
    }

    /** @test */
    public function on_records_without_a_branch_are_hidden_from_branch_users(): void
    {
        $this->makeBranch(['is_main' => true]);
        $own = $this->makeBranch();
        $legacy = $this->makeRequestFor(null, 'FR-LEGACY-' . uniqid());

        $this->enableBranchesModule(true);
        $this->actingAsAdmin($this->staffOf($own));

        $this->get(route('financial-requests.index'))
            ->assertOk()
            ->assertDontSee($legacy->request_no, false);
    }

    // ---------------------------------------------------------------- records are guarded

    /** @test */
    public function on_another_branches_record_cannot_be_opened(): void
    {
        $own = $this->makeBranch();
        $foreign = $this->makeRequestFor($this->makeBranch(), 'FR-FOREIGN-' . uniqid());

        $this->enableBranchesModule(true);
        $this->actingAsAdmin($this->staffOf($own));

        $this->get(route('financial-requests.show', $foreign))->assertStatus(403);
    }

    /** @test */
    public function off_another_branches_record_can_be_opened(): void
    {
        $own = $this->makeBranch();
        $foreign = $this->makeRequestFor($this->makeBranch(), 'FR-FOREIGN-' . uniqid());

        $this->enableBranchesModule(false);
        $this->actingAsAdmin($this->staffOf($own));

        $this->get(route('financial-requests.show', $foreign))
            ->assertOk()
            ->assertSee($foreign->request_no, false);
    }

    // ---------------------------------------------------------------- head office

    /** @test */
    public function on_an_admin_without_a_branch_still_sees_everything(): void
    {
        $a = $this->makeRequestFor($this->makeBranch(), 'FR-A-' . uniqid());
        $b = $this->makeRequestFor($this->makeBranch(), 'FR-B-' . uniqid());

        $this->enableBranchesModule(true);
        $this->actingAsAdmin($this->staffOf(null));

        $this->get(route('financial-requests.index'))
            ->assertOk()
            ->assertSee($a->request_no, false)
            ->assertSee($b->request_no, false);
    }

    /** @test */
    public function on_a_head_office_admin_can_narrow_the_list_to_one_branch(): void
    {
        $target = $this->makeBranch();
        $mine = $this->makeRequestFor($target, 'FR-TARGET-' . uniqid());
        $theirs = $this->makeRequestFor($this->makeBranch(), 'FR-OTHER-' . uniqid());

        $this->enableBranchesModule(true);
        $this->actingAsAdmin($this->staffOf(null));

        $this->get(route('financial-requests.index', ['branch_id' => $target->id]))
            ->assertOk()
            ->assertSee($mine->request_no, false)
            ->assertDontSee($theirs->request_no, false);
    }

    /** @test */
    public function off_the_branch_filter_in_the_url_is_ignored(): void
    {
        $target = $this->makeBranch();
        $mine = $this->makeRequestFor($target, 'FR-TARGET-' . uniqid());
        $theirs = $this->makeRequestFor($this->makeBranch(), 'FR-OTHER-' . uniqid());

        $this->enableBranchesModule(false);
        $this->actingAsAdmin($this->staffOf(null));

        $this->get(route('financial-requests.index', ['branch_id' => $target->id]))
            ->assertOk()
            ->assertSee($mine->request_no, false)
            ->assertSee($theirs->request_no, false);
    }
}
