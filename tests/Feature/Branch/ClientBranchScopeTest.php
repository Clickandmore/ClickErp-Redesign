<?php

namespace Tests\Feature\Branch;

use App\Models\ingaz\User;
use App\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class ClientBranchScopeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
    }

    private function grant($admin, string $permission): void
    {
        Permission::withoutGlobalScopes()->updateOrCreate(
            ['name' => $permission, 'guard_name' => 'admin'],
            [
                'display_name' => $permission,
                'group_name' => 'branches',
                'section_name' => 'users_management',
                'is_active' => true,
            ]
        );

        $admin->givePermissionTo($permission);
    }

    private function makeClient(string $name, int $branchId): User
    {
        return User::query()->create([
            'name' => $name,
            'phone_code' => '+966',
            'phone' => '5' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'register_from' => 'dashboard',
            'is_blocked' => 0,
            'branch_id' => $branchId,
        ]);
    }

    /** @test */
    public function client_page_only_returns_clients_from_the_logged_in_branch(): void
    {
        $this->makeBranch(['is_main' => true]);
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $viewer = $this->branchAdmin($own);
        $this->grant($viewer, \App\PermKeys::CLIENTS_VIEW);
        $ownClient = $this->makeClient('Own branch client', $own->id);
        $otherClient = $this->makeClient('Other branch client', $other->id);
        $unassignedClient = $this->makeClient('Unassigned client', $own->id);
        $unassignedClient->updateQuietly(['branch_id' => null]);

        $response = $this->actingAsAdmin($viewer)->get(
            route('clients.index', ['branch_id' => $other->id]),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertOk()
            ->assertSee($ownClient->name, false)
            ->assertDontSee($otherClient->name, false)
            ->assertDontSee($unassignedClient->name, false);
    }

    /** @test */
    public function view_all_permission_enables_filtering_clients_by_another_branch(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $viewer = $this->branchAdmin($own);
        $this->grant($viewer, \App\PermKeys::CLIENTS_VIEW);
        $this->grant($viewer, \App\PermKeys::BRANCHES_VIEW_ALL);
        $ownClient = $this->makeClient('Own branch client', $own->id);
        $otherClient = $this->makeClient('Other branch client', $other->id);

        $response = $this->actingAsAdmin($viewer)->get(
            route('clients.index', ['branch_id' => $other->id]),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertOk()
            ->assertDontSee($ownClient->name, false)
            ->assertSee($otherClient->name, false);
    }

    /** @test */
    public function duplicate_phone_filter_uses_the_clean_phone_without_the_migration_marker(): void
    {
        $branch = $this->makeBranch(['is_main' => true]);
        $viewer = $this->branchAdmin($branch);
        $this->grant($viewer, \App\PermKeys::CLIENTS_VIEW);

        $phone = '5' . random_int(10000000, 99999999);
        $first = $this->makeClient('Duplicate phone one', $branch->id);
        $first->updateQuietly(['phone' => $phone]);
        $second = $this->makeClient('Duplicate phone two', $branch->id);
        $second->updateQuietly(['phone' => $phone . '-dup-']);
        $unique = $this->makeClient('Unique phone client', $branch->id);

        $response = $this->actingAsAdmin($viewer)->get(
            route('clients.index', ['phone_status' => 'duplicate']),
            ['X-Requested-With' => 'XMLHttpRequest']
        );

        $response->assertOk()
            ->assertSee($first->name, false)
            ->assertSee($second->name, false)
            ->assertDontSee($unique->name, false);

        $this->assertStringContainsString(
            __('ingaz.duplicate_phone'),
            (string) data_get($response->json(), 'data.0.client_data')
        );
    }

    /** @test */
    public function duplicate_phone_report_can_be_downloaded_without_merging_clients(): void
    {
        $branch = $this->makeBranch(['is_main' => true]);
        $viewer = $this->branchAdmin($branch);
        $this->grant($viewer, \App\PermKeys::CLIENTS_VIEW);
        $this->grant($viewer, \App\PermKeys::CLIENTS_EXPORT_EXCEL);

        $phone = '5' . random_int(10000000, 99999999);
        $first = $this->makeClient('Report duplicate one', $branch->id);
        $first->updateQuietly(['phone' => $phone]);
        $second = $this->makeClient('Report duplicate two', $branch->id);
        $second->updateQuietly(['phone' => $phone . '-dup-2']);

        $response = $this->actingAsAdmin($viewer)->get(route('clients.reports.duplicate-phones'));

        $response->assertOk();
        $this->assertStringContainsString(
            'duplicate-client-phones-',
            (string) $response->headers->get('content-disposition')
        );
        $this->assertSame($phone . '-dup-2', $second->fresh()->phone);
    }
}
