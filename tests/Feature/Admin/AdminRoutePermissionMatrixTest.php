<?php

namespace Tests\Feature\Admin;

use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Http\Request;
use Tests\TestCase;

class AdminRoutePermissionMatrixTest extends TestCase
{
    public function test_every_primary_sidebar_route_has_a_permission_mapping(): void
    {
        foreach (AdminRoutePermissionMatrix::primaryRouteNames() as $routeName) {
            $this->assertNotEmpty(
                AdminRoutePermissionMatrix::resolve($routeName, Request::create('/')),
                "Route [{$routeName}] is missing from the admin permission matrix."
            );
        }
    }

    public function test_roles_store_uses_create_permission_for_new_role_requests(): void
    {
        $permissions = AdminRoutePermissionMatrix::resolve(
            'dash.roles.store',
            Request::create('/dash/roles/store', 'POST', ['name' => 'Support'])
        );

        $this->assertSame([PermKeys::ROLES_CREATE], $permissions);
    }

    public function test_dashboard_route_requires_dashboard_view_permission_only(): void
    {
        $permissions = AdminRoutePermissionMatrix::resolve(
            'dashboard.index',
            Request::create('/dashboard')
        );

        $this->assertSame([PermKeys::DASHBOARD_VIEW], $permissions);
    }

    public function test_roles_store_uses_edit_permission_for_edit_requests(): void
    {
        $permissions = AdminRoutePermissionMatrix::resolve(
            'dash.roles.store',
            Request::create('/dash/roles/store', 'POST', ['edit_id' => 7, 'name' => 'Support'])
        );

        $this->assertSame([PermKeys::ROLES_EDIT], $permissions);
    }

    public function test_offices_route_uses_external_agents_permissions(): void
    {
        $permissions = AdminRoutePermissionMatrix::resolve(
            'offices.index',
            Request::create('/admin/offices')
        );

        $this->assertSame([PermKeys::OFFICE_VIEW, PermKeys::OFFICE_CONTROL], $permissions);
    }

    public function test_sponsorship_transfer_workflow_routes_require_control_permission(): void
    {
        $routeNames = [
            'SponsorshipTransfer.approve',
            'SponsorshipTransfer.reject',
            'SponsorshipTransfer.complete',
            'SponsorshipTransfer.confirmTransfer',
            'SponsorshipTransfer.cancelTransfer',
            'SponsorshipTransfer.send-contract',
            'SponsorshipTransfer.sign-contract',
            'SponsorshipTransfer.deliver-maid',
            'SponsorshipTransfer.start-trial',
            'sponsorship-transfer.save-signature',
            'sponsorship-transfer.save-company-signature',
        ];

        foreach ($routeNames as $routeName) {
            $this->assertSame(
                [PermKeys::SPONSOR_TRANSFER_CONTROL],
                AdminRoutePermissionMatrix::resolve($routeName, Request::create('/')),
                "Route [{$routeName}] must require sponsorship-transfer control permission."
            );
        }
    }
}
