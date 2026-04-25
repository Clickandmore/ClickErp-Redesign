<?php

namespace Tests\Feature\Admin;

use App\Support\AdminRoutePermissionMatrix;
use Tests\TestCase;

class AdminProtectedRoutesMiddlewareTest extends TestCase
{
    public function test_primary_sidebar_routes_are_behind_admin_route_permission_middleware(): void
    {
        foreach (AdminRoutePermissionMatrix::primaryRouteNames() as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");
            $this->assertContains(
                'admin.route.permission',
                $route->gatherMiddleware(),
                "Route [{$routeName}] is missing the admin.route.permission middleware."
            );
        }
    }

    public function test_dash_routes_are_also_behind_admin_authentication(): void
    {
        foreach (['dash.settings', 'dash.roles.index', 'dash.perms.setPerms', 'dash.activity.log'] as $routeName) {
            $route = app('router')->getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is not registered.");

            $middlewares = $route->gatherMiddleware();

            $this->assertContains('admin', $middlewares, "Route [{$routeName}] is missing admin authentication.");
            $this->assertContains('admin.route.permission', $middlewares, "Route [{$routeName}] is missing route-permission enforcement.");
        }
    }
}
