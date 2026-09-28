<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\RecruitmentOffices;
use App\Services\BranchDataSharingService;
use App\Support\BranchSharingSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

/**
 * HTTP behaviour of the branch admin screens: validation, the branch-count limit,
 * and code auto-generation.
 */
class BranchCrudTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
        $this->setBranchLimit(null);
        BranchSharingSettings::flush();
        $this->actingAsAdmin($this->globalAdmin()->assignRole('Administrator'));
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name_ar' => 'فرع الرياض',
            'name_en' => 'Riyadh Branch',
            'code' => 'BR-TEST-1',
            'phone' => '0500000000',
            'email' => 'riyadh@example.com',
            'city' => 'الرياض',
            'is_active' => 1,
            'sort_order' => 0,
        ], $overrides);
    }

    /** @test */
    public function it_lists_branches(): void
    {
        $branch = $this->makeBranch(['name_ar' => 'فرع جدة الفريد']);

        $this->get(route('branches.index'))
            ->assertOk()
            ->assertSee('فرع جدة الفريد', false);
    }

    /** @test */
    public function the_list_can_be_searched_by_name(): void
    {
        $match = $this->makeBranch(['name_ar' => 'فرع الدمام المميز']);
        $other = $this->makeBranch(['name_ar' => 'فرع مكة']);

        $this->get(route('branches.index', ['search' => 'الدمام المميز']))
            ->assertOk()
            ->assertSee('فرع الدمام المميز', false)
            ->assertDontSee('فرع مكة', false);
    }

    /** @test */
    public function it_creates_a_branch(): void
    {
        $this->post(route('branches.store'), $this->validPayload())
            ->assertRedirect(route('branches.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('branches', [
            'name_ar' => 'فرع الرياض',
            'code' => 'BR-TEST-1',
        ]);
    }

    /** @test */
    public function it_generates_a_code_when_none_is_supplied(): void
    {
        $this->post(route('branches.store'), $this->validPayload(['code' => null]))
            ->assertRedirect(route('branches.index'));

        $branch = Branch::query()->where('name_ar', 'فرع الرياض')->firstOrFail();

        $this->assertMatchesRegularExpression('/^BR-\d{4}$/', $branch->code);
    }

    /** @test */
    public function the_arabic_name_is_required(): void
    {
        $this->post(route('branches.store'), $this->validPayload(['name_ar' => '']))
            ->assertSessionHasErrors('name_ar');
    }

    /** @test */
    public function the_code_must_be_unique(): void
    {
        $this->makeBranch(['code' => 'BR-DUP']);

        $this->post(route('branches.store'), $this->validPayload(['code' => 'BR-DUP']))
            ->assertSessionHasErrors('code');
    }

    /** @test */
    public function a_branch_may_keep_its_own_code_when_updated(): void
    {
        $branch = $this->makeBranch(['code' => 'BR-KEEP']);

        $this->put(route('branches.update', $branch), $this->validPayload([
            'code' => 'BR-KEEP',
            'name_ar' => 'اسم محدث',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('اسم محدث', $branch->fresh()->name_ar);
    }

    /** @test */
    public function coordinates_outside_the_valid_range_are_rejected(): void
    {
        $this->post(route('branches.store'), $this->validPayload(['latitude' => 120]))
            ->assertSessionHasErrors('latitude');

        $this->post(route('branches.store'), $this->validPayload(['longitude' => 200]))
            ->assertSessionHasErrors('longitude');
    }

    /** @test */
    public function an_invalid_email_is_rejected(): void
    {
        $this->post(route('branches.store'), $this->validPayload(['email' => 'not-an-email']))
            ->assertSessionHasErrors('email');
    }

    /** @test */
    public function working_hours_are_normalised_across_all_seven_days(): void
    {
        $this->post(route('branches.store'), $this->validPayload([
            'working_hours' => [
                'sunday' => ['from' => '09:00', 'to' => '17:00'],
                'friday' => ['is_closed' => 1, 'from' => '09:00', 'to' => '17:00'],
            ],
        ]))->assertSessionHasNoErrors();

        $hours = Branch::query()->where('name_ar', 'فرع الرياض')->firstOrFail()->working_hours;

        $this->assertCount(7, $hours, 'Every day of the week gets an entry.');
        $this->assertSame('09:00', $hours['sunday']['from']);
        $this->assertTrue($hours['friday']['is_closed']);
        $this->assertNull($hours['friday']['from'], 'A closed day must not keep stale opening times.');
        $this->assertFalse($hours['monday']['is_closed'], 'Unlisted days default to open.');
    }

    /** @test */
    public function a_malformed_working_hour_is_rejected(): void
    {
        $this->post(route('branches.store'), $this->validPayload([
            'working_hours' => ['sunday' => ['from' => '9am', 'to' => '17:00']],
        ]))->assertSessionHasErrors('working_hours.sunday.from');
    }

    /** @test */
    public function it_deletes_a_branch(): void
    {
        $branch = $this->makeBranch();

        $this->delete(route('branches.destroy', $branch))
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
    }

    /** @test */
    public function deleting_over_json_returns_a_json_body(): void
    {
        $branch = $this->makeBranch();

        $this->deleteJson(route('branches.destroy', $branch))
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function a_branch_cannot_be_created_once_the_limit_is_reached(): void
    {
        $this->setBranchLimit(Branch::query()->count());

        $this->post(route('branches.store'), $this->validPayload())
            ->assertRedirect(route('branches.index'))
            ->assertSessionHasErrors('branches_limit');

        $this->assertDatabaseMissing('branches', ['name_ar' => 'فرع الرياض']);
    }

    /** @test */
    public function the_create_form_is_blocked_once_the_limit_is_reached(): void
    {
        $this->setBranchLimit(Branch::query()->count());

        $this->get(route('branches.create'))
            ->assertRedirect(route('branches.index'))
            ->assertSessionHasErrors('branches_limit');
    }

    /** @test */
    public function a_branch_can_still_be_created_below_the_limit(): void
    {
        $this->setBranchLimit(Branch::query()->count() + 1);

        $this->post(route('branches.store'), $this->validPayload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('branches', ['name_ar' => 'فرع الرياض']);
    }

    /** @test */
    public function guests_are_redirected_away_from_the_branch_screens(): void
    {
        auth('admin')->logout();

        $this->get(route('branches.index'))->assertRedirect();
    }

    /** @test */
    public function it_updates_branch_sharing_settings(): void
    {
        BranchSharingSettings::persist([
            'share_maids' => true,
        ]);

        $this->post(route('branches.settings.update'), [
            'share_maids' => 0,
            'shared_cost_center_root' => 1,
            'accounts_per_branch' => 1,
        ])
            ->assertRedirect(route('branches.settings'))
            ->assertSessionHasNoErrors();

        BranchSharingSettings::flush();

        $this->assertFalse(BranchSharingSettings::shareMaids());
        $this->assertTrue(BranchSharingSettings::sharedCostCenterRoot());
        $this->assertTrue(BranchSharingSettings::accountsPerBranch());
    }

    /** @test */
    public function settings_page_distinguishes_company_policy_from_branch_permissions(): void
    {
        $this->makeBranch(['name_ar' => 'الفرع الرئيسي', 'is_main' => true]);

        $this->get(route('branches.settings'))
            ->assertOk()
            ->assertSee('نطاق هذه الشاشة: الشركة بالكامل (جميع الفروع).', false)
            ->assertSee('سياسات الشركة العامة', false)
            ->assertSee('name="share_maids"', false)
            ->assertSee('مشاركة بيانات كل فرع', false)
            ->assertSee('صلاحية view_all', false)
            ->assertSee('name="sharing_policies[', false)
            ->assertDontSee('targetBranch', false)
            ->assertDontSee('data_get($sharingRuleMatrix', false);
    }

    /** @test */
    public function it_saves_source_branch_sharing_policies(): void
    {
        $source = $this->makeBranch(['name_ar' => 'فرع الدمام']);

        $this->post(route('branches.settings.update'), [
            'share_maids' => 0,
            'shared_cost_center_root' => 1,
            'accounts_per_branch' => 0,
            'sharing_policies' => [
                $source->id => [
                    BranchDataSharingService::CLIENTS => 1,
                ],
            ],
        ])->assertRedirect(route('branches.settings'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('branch_data_sharing_policies', [
            'entity_type' => BranchDataSharingService::CLIENTS,
            'branch_id' => $source->id,
            'access_level' => 'view',
        ]);

        $this->get(route('branches.settings'))
            ->assertOk()
            ->assertSee('مشاركة بيانات كل فرع', false)
            ->assertSee('فرع الدمام', false);
    }

    /** @test */
    public function permissions_page_states_the_selected_branch_and_role_before_saving(): void
    {
        $branch = $this->makeBranch(['name_ar' => 'فرع الصلاحيات الواضح']);
        $roleId = DB::table('roles')
            ->where('guard_name', 'admin')
            ->where('name', 'Administrator')
            ->value('id');

        $this->get(route('dash.perms.setPerms', ['id' => $roleId, 'branch_id' => $branch->id]))
            ->assertOk()
            ->assertSee('الفرع الذي ستُحفظ له الصلاحيات', false)
            ->assertSee('نطاق الحفظ الحالي:', false)
            ->assertSee('فرع الصلاحيات الواضح', false)
            ->assertSee('دور <strong>Administrator</strong>', false);
    }

    /** @test */
    public function external_offices_are_isolated_without_a_source_branch_policy(): void
    {
        $own = $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $admin = $this->lockToOwnBranch($this->branchAdmin($own)->assignRole('Administrator'));

        $ownOfficeId = DB::table('recruitment_offices')->insertGetId([
            'branch_id' => $own->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherOfficeId = DB::table('recruitment_offices')->insertGetId([
            'branch_id' => $other->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAsAdmin($admin);

        $this->assertTrue(RecruitmentOffices::query()->whereKey($ownOfficeId)->exists());
        $this->assertFalse(RecruitmentOffices::query()->whereKey($otherOfficeId)->exists());
    }

    /** @test */
    public function a_source_policy_shares_external_offices_with_all_other_branches(): void
    {
        $target = $this->makeBranch(['is_main' => true]);
        $source = $this->makeBranch();
        $unrelated = $this->makeBranch();
        $admin = $this->lockToOwnBranch($this->branchAdmin($target)->assignRole('Administrator'));

        $sharedOfficeId = DB::table('recruitment_offices')->insertGetId([
            'branch_id' => $source->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $hiddenOfficeId = DB::table('recruitment_offices')->insertGetId([
            'branch_id' => $unrelated->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(BranchDataSharingService::class)->sync([
            $source->id => [
                BranchDataSharingService::EXTERNAL_OFFICES => true,
            ],
        ]);

        $this->actingAsAdmin($admin);

        $this->assertTrue(RecruitmentOffices::query()->whereKey($sharedOfficeId)->exists());
        $this->assertFalse(RecruitmentOffices::query()->whereKey($hiddenOfficeId)->exists());
    }

    public function test_main_branch_office_sharing_includes_legacy_offices_in_nationality_lookup(): void
    {
        DB::table('branches')->update(['is_main' => false]);
        $main = $this->makeBranch(['is_main' => true]);
        $target = $this->makeBranch();
        app(\App\Services\BranchAccessService::class)->forgetMainBranchId();
        $admin = $this->lockToOwnBranch($this->branchAdmin($target)->assignRole('Administrator'));
        $nationalityId = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $officeId = DB::table('recruitment_offices')->insertGetId([
            'branch_id' => null,
            'nationality_id' => $nationalityId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $legacyOffice = RecruitmentOffices::withoutBranchScope()->findOrFail($officeId);
        $this->actingAsAdmin($admin);

        foreach ([[], [$target->id => ['external_offices' => true]], [$main->id => ['external_offices' => true]], []] as $policies) {
            app(BranchDataSharingService::class)->sync($policies);
            $expected = isset($policies[$main->id]);
            $this->assertSame($expected, RecruitmentOffices::query()->whereKey($officeId)->exists());
            $this->assertSame($expected, \App\Models\office\Office::query()->whereKey($officeId)->exists());
            $this->assertSame($expected, $legacyOffice->belongsToActiveBranch());
            $response = $this->getJson(route('getOfficesByNationality', ['nationality_id' => $nationalityId]))->assertOk();
            $this->assertSame($expected, collect($response->json())->pluck('id')->contains($officeId));
        }
    }
}
