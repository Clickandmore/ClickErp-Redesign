<?php

namespace Tests\Feature\Security;

use App\Http\Controllers\ingaz\ShelterContractController;
use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Mockery;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Tests\TestCase;

class ShelterExportPassportAuthorizationTest extends TestCase
{
    public function test_routes_require_an_authenticated_admin_and_route_permission_check(): void
    {
        foreach (['ShelterContract.export', 'ShelterContract.searchByPassport', 'search.contracts.by.passport'] as $name) {
            $this->step("Find route {$name}.");
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route);
            $middleware = $route->gatherMiddleware();
            $this->result('Middleware: ' . implode(', ', $middleware));
            $this->assertContains('auth:admin', $middleware);
            $this->assertContains('admin', $middleware);
            $this->assertContains('admin.route.permission', $middleware);
            $this->result("PASS: {$name} requires an authenticated, active admin.");
        }
    }

    public function test_export_has_a_central_permission_mapping(): void
    {
        $this->step('Resolve central permissions for ShelterContract.export.');
        $permissions = AdminRoutePermissionMatrix::resolve('ShelterContract.export');
        $this->result('Required permissions: ' . implode(', ', $permissions));

        $this->assertSame([
            PermKeys::SHELTER_CONTRACT_VIEW,
            PermKeys::SHELTER_CONTRACT_CONTROL,
        ], $permissions);
        $this->result('PASS: export requires shelter view or shelter control permission.');
    }

    public function test_controller_applies_permission_middleware_to_export_and_passport_search(): void
    {
        $this->step('Inspect ShelterContractController middleware configuration.');
        $middleware = collect(app(ShelterContractController::class)->getMiddleware());

        $exportEntry = $middleware->first(fn (array $entry) => in_array('export', $entry['options']['only'] ?? [], true));
        $searchEntry = $middleware->first(fn (array $entry) => in_array('searchByPassport', $entry['options']['only'] ?? [], true));

        $this->assertNotNull($exportEntry);
        $this->assertNotNull($searchEntry);
        $this->result('Export controller middleware: ' . $exportEntry['middleware']);
        $this->result('Passport-search controller middleware: ' . $searchEntry['middleware']);
        $this->result('PASS: both controller methods have permission middleware.');
    }

    public function test_staff_without_shelter_permission_cannot_reach_export(): void
    {
        $this->step('Prepare staff with no shelter permissions.');
        $this->step('Attempt to pass the export permission middleware.');

        $permissions = implode('|', [
            PermKeys::SHELTER_CONTRACT_VIEW,
            PermKeys::SHELTER_CONTRACT_CONTROL,
        ]);

        $this->assertPermissionDenied('/admin/ShelterContract/export/excel', $permissions);
        $this->result('PASS: export request was rejected with HTTP 403.');
    }

    public function test_staff_without_create_or_edit_permission_cannot_search_by_passport(): void
    {
        $this->step('Prepare staff with no shelter create/edit permissions.');
        $this->step('Attempt to pass the passport-search permission middleware.');

        $permissions = implode('|', [
            PermKeys::SHELTER_CONTRACT_ADDITION_CREATE,
            PermKeys::SHELTER_CONTRACT_CREATE,
            PermKeys::SHELTER_CONTRACT_CONTROL,
            PermKeys::SHELTER_CONTRACT_EDIT,
        ]);

        $this->assertPermissionDenied('/admin/ShelterContract/search-by-passport', $permissions);
        $this->result('PASS: passport-search request was rejected with HTTP 403.');
    }

    private function assertPermissionDenied(string $uri, string $permissions): void
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
                Request::create($uri, 'GET'),
                fn () => response()->json(['unexpected' => true]),
                $permissions,
                'admin'
            );

            $this->fail('Staff without permission reached the protected action.');
        } catch (UnauthorizedException $exception) {
            $this->result('Actual HTTP status: ' . $exception->getStatusCode());
            $this->result('Middleware required: ' . implode(', ', $exception->getRequiredPermissions()));
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    private function step(string $message): void
    {
        fwrite(STDOUT, "\n  STEP: {$message}\n");
    }

    private function result(string $message): void
    {
        fwrite(STDOUT, "  RESULT: {$message}\n");
    }
}
