<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\admin\auth\AdminController;
use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
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

    public function test_staff_without_users_edit_is_rejected_with_403(): void
    {
        $staff = new class {
            public function hasAnyPermission(): bool
            {
                return false;
            }

            public function canAny(array $permissions): bool
            {
                return false;
            }
        };

        $guard = Mockery::mock();
        $guard->shouldReceive('user')->once()->andReturn($staff);
        Auth::shouldReceive('guard')->once()->with('admin')->andReturn($guard);

        try {
            app(PermissionMiddleware::class)->handle(
                Request::create('/dashboard/admins/1/toggle-status', 'POST'),
                fn () => response()->json(['unexpected' => true]),
                PermKeys::USERS_EDIT,
                'admin'
            );

            $this->fail('Unauthorized staff reached the toggle-status action.');
        } catch (UnauthorizedException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
            $this->assertSame([PermKeys::USERS_EDIT], $exception->getRequiredPermissions());
        }
    }
}
