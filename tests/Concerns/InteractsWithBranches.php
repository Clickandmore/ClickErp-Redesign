<?php

namespace Tests\Concerns;

use App\Models\auth\Admin;
use App\Models\Branch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Shared setup for branch tests.
 *
 * SystemModuleManager memoises the enabled-map and the branch limit in the cache,
 * so anything that flips those settings mid-suite has to clear it or the next test
 * inherits the previous test's answer.
 */
trait InteractsWithBranches
{
    protected function setUpBranches(): void
    {
        Cache::flush();
    }

    protected function enableBranchesModule(bool $enabled = true): void
    {
        DB::table('system_module_settings')->updateOrInsert(
            ['key' => 'branches'],
            ['name' => 'branches', 'is_enabled' => $enabled, 'updated_at' => now(), 'created_at' => now()]
        );

        Cache::flush();
    }

    protected function setBranchLimit(?int $limit): void
    {
        DB::table('dev_superadmin_settings')->updateOrInsert(
            ['key' => 'branches_limit'],
            ['value' => $limit === null ? null : (string) $limit, 'updated_at' => now(), 'created_at' => now()]
        );

        Cache::flush();
    }

    protected function makeBranch(array $attributes = []): Branch
    {
        return Branch::factory()->create($attributes);
    }

    /** An admin locked to one branch. */
    protected function branchAdmin(Branch $branch, array $attributes = []): Admin
    {
        return Admin::factory()->forBranch($branch)->create($attributes);
    }

    /** An admin with no branch_id — the "sees everything" case. */
    protected function globalAdmin(array $attributes = []): Admin
    {
        return Admin::factory()->global()->create($attributes);
    }

    protected function actingAsAdmin(Admin $admin): self
    {
        $this->actingAs($admin, 'admin');

        return $this;
    }
}
