<?php

namespace Tests\Feature\Security;

use App\Models\auth\Admin;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

/**
 * EnsureAdminRoutePermission fails open: when AdminRoutePermissionMatrix::resolve()
 * returns [], the request is passed straight through. Any admin route whose name is
 * absent from the matrix — and whose controller does not authorize() on its own — is
 * therefore reachable by any authenticated admin, regardless of role.
 *
 * These probes document three such holes (BUG-003, BUG-004, BUG-005).
 */
class UngatedAdminRoutesTest extends TestCase
{
    use DatabaseTransactions;

    private function lowPrivilegeAdmin(): Admin
    {
        // No roles, no permissions at all.
        return Admin::factory()->create();
    }

    /**
     * BUG-003 — routes/ingaz.php registers the office CRUD a second time under the
     * name `recruitment-offices.*`. The matrix only knows the `offices.*` names, so
     * this alias resolves to no permissions at all.
     *
     * @test
     */
    public function a_permissionless_admin_cannot_list_recruitment_offices(): void
    {
        $attacker = $this->lowPrivilegeAdmin();

        $this->actingAs($attacker, 'admin')
            ->get(route('recruitment-offices.index'))
            ->assertForbidden();
    }

    /** @test */
    public function the_recruitment_offices_alias_is_covered_by_the_permission_matrix(): void
    {
        $this->assertNotEmpty(
            AdminRoutePermissionMatrix::resolve('recruitment-offices.index', Request::create('/')),
            'Route [recruitment-offices.index] is missing from the admin permission matrix.'
        );
    }

    /**
     * BUG-004 — ChangePasswordController::update takes the target admin id straight
     * from the URL and writes the new hash to it. There is no authorize() call and no
     * check that {id} is the authenticated admin, so any admin can overwrite any
     * other admin's password. `current_password` is validated only `sometimes`, so
     * omitting it skips the old-password check entirely.
     *
     * @test
     */
    public function an_admin_cannot_change_another_admins_password(): void
    {
        $attacker = $this->lowPrivilegeAdmin();
        $victim = Admin::factory()->superAdmin()->create([
            'password' => Hash::make('victim-original-password'),
        ]);

        $this->actingAs($attacker, 'admin')
            ->putJson(route('change-password.update', $victim->id), [
                'new_password' => 'hijacked-password',
                'new_confirm_password' => 'hijacked-password',
            ])
            ->assertForbidden();

        $this->assertTrue(
            Hash::check('victim-original-password', $victim->fresh()->password),
            "The victim's password was overwritten by another admin."
        );
    }

    /**
     * BUG-005 — export endpoints leak the same data the index pages do, so every one
     * of them needs a permission check.
     *
     * Two guards exist and a route only needs one of them:
     *   - `admin.route.permission` reads AdminRoutePermissionMatrix, and fails OPEN
     *     when the route name has no entry there.
     *   - `package.route.permission:{module}` derives `{module}_{resource}_{ability}`
     *     from the route name, so it always demands at least the module permission.
     *
     * A route carrying neither is reachable by any authenticated admin.
     *
     * @test
     */
    public function every_export_route_requires_a_permission(): void
    {
        $ungated = [];

        foreach (RouteFacade::getRoutes() as $route) {
            $name = $route->getName();

            if (! $name || ! preg_match('/(^|[.\-_])(export|print)([.\-_]|$)/i', $name)) {
                continue;
            }

            $middleware = $route->gatherMiddleware();

            // Guarded by a package module middleware — permissions are derived, never empty.
            foreach ($middleware as $entry) {
                if (is_string($entry) && str_starts_with($entry, 'package.route.permission')) {
                    continue 2;
                }
            }

            // Not an admin-panel route at all (site/API surface has its own auth).
            if (! in_array('admin', $middleware, true)) {
                continue;
            }

            if (AdminRoutePermissionMatrix::resolve($name, Request::create('/' . $route->uri())) === []) {
                $ungated[] = $name;
            }
        }

        sort($ungated);

        $this->assertSame([], $ungated, "Export routes with no permission mapping:\n - " . implode("\n - ", $ungated));
    }
}
