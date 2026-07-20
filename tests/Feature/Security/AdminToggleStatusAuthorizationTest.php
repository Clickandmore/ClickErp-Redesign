<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\admin\auth\AdminController;
use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use Tests\TestCase;

class AdminToggleStatusAuthorizationTest extends TestCase
{
    public function test_toggle_status_route_requires_users_edit_in_permission_matrix(): void
    {
        $this->assertSame(
            [PermKeys::USERS_EDIT],
            AdminRoutePermissionMatrix::resolve('admins.toggle-status')
        );
    }

    public function test_toggle_status_action_is_covered_by_controller_permission_middleware(): void
    {
        $controller = app(AdminController::class);

        $usersEditMiddleware = collect($controller->getMiddleware())
            ->first(fn (array $entry) => $entry['middleware'] === 'permission:users_edit,admin');

        $this->assertNotNull($usersEditMiddleware);
        $this->assertContains('toggleStatus', $usersEditMiddleware['options']['only'] ?? []);
    }
}
