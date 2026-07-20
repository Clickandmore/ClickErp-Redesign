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
}
