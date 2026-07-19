<?php

namespace Tests\Feature\Admin;

use App\Models\auth\Admin;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Covers the developer permission-keys screen (/dash/developers/perms), which lists
 * every admin permission in a single client-filtered table.
 */
class DeveloperPermissionKeysPageTest extends TestCase
{
    use DatabaseTransactions;

    private function developerAdmin(): Admin
    {
        $role = Role::firstOrCreate(['name' => 'perm-keys-tester', 'guard_name' => 'admin']);
        $permission = Permission::firstOrCreate([
            'name' => PermKeys::DEVELOPERS_PERMS,
            'guard_name' => 'admin',
        ]);
        $role->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = Admin::factory()->create();
        $admin->assignRole($role);

        return $admin;
    }

    /** @test */
    public function the_permission_keys_page_renders_the_filter_toolbar_and_rows(): void
    {
        $response = $this->actingAs($this->developerAdmin(), 'admin')
            ->get(route('dash.developers.perms.index'));

        $response->assertOk();

        // Filter controls.
        $response->assertSee('perm-filter-search', false);
        $response->assertSee('perm-filter-section', false);
        $response->assertSee('perm-filter-group', false);
        $response->assertSee('perm-filter-status', false);
        $response->assertSee('perm-filter-reset', false);

        // The table body must actually be populated, not just the shell.
        $this->assertGreaterThan(
            100,
            substr_count($response->getContent(), 'class="perm-row'),
            'The permission table rendered no rows.'
        );
    }

    /** @test */
    public function every_rendered_row_carries_the_dataset_the_filter_reads(): void
    {
        $content = $this->actingAs($this->developerAdmin(), 'admin')
            ->get(route('dash.developers.perms.index'))
            ->getContent();

        $rowCount = substr_count($content, 'class="perm-row');

        foreach (['data-name=', 'data-translation=', 'data-group=', 'data-section=', 'data-status='] as $attribute) {
            $this->assertSame(
                $rowCount,
                substr_count($content, $attribute),
                "Not every row carries [{$attribute}]; the client-side filter would silently skip rows."
            );
        }
    }

    /** @test */
    public function the_edit_form_is_prefilled_when_a_permission_id_is_supplied(): void
    {
        $permission = Permission::firstOrCreate([
            'name' => 'perm_keys_page_probe',
            'guard_name' => 'admin',
        ]);

        $this->actingAs($this->developerAdmin(), 'admin')
            ->get(route('dash.developers.perms.index', ['id' => $permission->id]))
            ->assertOk()
            ->assertSee('value="perm_keys_page_probe"', false)
            ->assertSee('name="edit_id"', false);
    }
}
