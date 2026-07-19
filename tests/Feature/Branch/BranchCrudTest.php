<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
}
