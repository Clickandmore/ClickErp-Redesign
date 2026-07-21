<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\EnsurePackageRoutePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ConstraintDestroyCurrentAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::put('superadmin.modules.enabled_map', ['branches' => false], 60);
    }

    public function test_destroy_route_uses_admin_and_finance_permission_middleware(): void
    {
        $this->step('1', 'Find route finance.constraints.destroy.');
        $route = Route::getRoutes()->getByName('finance.constraints.destroy');

        $this->assertNotNull($route);
        $this->step('2', 'Read all middleware attached to the route.');
        $this->result('Middleware: ' . implode(', ', $route->gatherMiddleware()));
        $this->assertContains('admin', $route->gatherMiddleware());
        $this->assertContains('package.route.permission:finance', $route->gatherMiddleware());
        $this->result('PASS: admin authentication and finance permission middleware are attached.');
    }

    public function test_request_without_an_authenticated_admin_is_rejected(): void
    {
        $this->step('1', 'Prepare DELETE /finance/constraints/123 without an authenticated admin.');
        $this->mockAdminGuard(null);
        $this->step('2', 'Send the request through package.route.permission:finance.');

        try {
            $this->middleware()->handle(
                $this->destroyRequest(),
                fn () => response()->json(['reached' => true]),
                'finance'
            );

            $this->fail('Unauthenticated request reached the protected action.');
        } catch (HttpException $exception) {
            $this->result('Actual HTTP status: ' . $exception->getStatusCode());
            $this->assertSame(403, $exception->getStatusCode());
            $this->result('PASS: unauthenticated request was rejected.');
        }
    }

    public function test_admin_without_finance_permissions_is_rejected(): void
    {
        $this->step('1', 'Prepare an authenticated admin with no finance permissions.');
        $admin = new class {
            public int $admin_type = 2;

            public function hasAnyPermission(array $permissions): bool
            {
                return false;
            }

            public function can(string $permission): bool
            {
                return false;
            }
        };
        $this->mockAdminGuard($admin);
        $this->step('2', 'Send DELETE /finance/constraints/123 through the finance permission middleware.');

        $response = $this->middleware()->handle(
            $this->destroyRequest(),
            fn () => response()->json(['reached' => true]),
            'finance'
        );

        $this->result('Actual HTTP status: ' . $response->getStatusCode());
        $this->assertSame(403, $response->getStatusCode());
        $this->result('PASS: admin without finance permissions was rejected.');
    }

    public function test_admin_with_only_finance_module_is_currently_allowed(): void
    {
        $this->step('1', 'Prepare an authenticated admin with finance_module only.');
        $admin = new class {
            public int $admin_type = 2;

            public function hasAnyPermission(array $permissions): bool
            {
                return in_array('finance_module', $permissions, true);
            }

            public function can(string $permission): bool
            {
                return $permission === 'finance_module';
            }
        };
        $this->mockAdminGuard($admin);
        $this->step('2', 'Send DELETE /finance/constraints/123 through the finance permission middleware.');

        $response = $this->middleware()->handle(
            $this->destroyRequest(),
            fn () => response()->json(['reached' => true]),
            'finance'
        );

        $this->result('Actual HTTP status: ' . $response->getStatusCode());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['reached']);
        $this->result('PASS (current behavior): finance_module alone reached the delete action.');
        $this->result('SECURITY FINDING: a dedicated constraint-delete permission is not required.');
    }

    private function destroyRequest(): Request
    {
        $request = Request::create('/finance/constraints/123', 'DELETE');
        $request->headers->set('Accept', 'application/json');
        $route = Mockery::mock();
        $route->shouldReceive('getName')->andReturn('finance.constraints.destroy');
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    private function mockAdminGuard($admin): void
    {
        $guard = Mockery::mock();
        $guard->shouldReceive('user')->andReturn($admin);
        Auth::shouldReceive('guard')->with('admin')->andReturn($guard);
    }

    private function middleware(): EnsurePackageRoutePermission
    {
        return app(EnsurePackageRoutePermission::class);
    }

    private function step(string $number, string $message): void
    {
        fwrite(STDOUT, "\n  STEP {$number}: {$message}\n");
    }

    private function result(string $message): void
    {
        fwrite(STDOUT, "  RESULT: {$message}\n");
    }
}
