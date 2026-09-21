<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\EnsureAdminRoutePermission;
use App\Models\auth\Admin;
use App\Models\Permission;
use App\PermKeys;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class OrderActionPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    private function gate(string $name, array $data = []): int
    {
        $request = Request::create('/admin/orders/1', 'POST', $data);
        $request->headers->set('Accept', 'application/json');
        $route = (new Route('POST', 'admin/orders/1', fn () => null))->name($name);
        $request->setRouteResolver(fn () => $route);
        try {
            return app(EnsureAdminRoutePermission::class)->handle($request, fn () => response('allowed'))->getStatusCode();
        } catch (HttpException $exception) {
            return $exception->getStatusCode();
        }
    }

    public function test_each_action_requires_its_own_permission_and_is_grantable(): void
    {
        (require database_path('migrations/2026_09_21_120000_expose_order_action_permissions.php'))->up();
        $admin = Admin::factory()->create();
        $edit = Permission::firstOrCreate(['name' => PermKeys::ORDERS_EDIT, 'guard_name' => 'admin']);
        $admin->givePermissionTo($edit);
        $this->actingAs($admin, 'admin');
        $actions = [
            'orders.edit' => PermKeys::ORDERS_CHANGE_STATUS,
            'orders.update' => PermKeys::ORDERS_CHANGE_STATUS,
            'orders.changeStatus' => PermKeys::ORDERS_CHANGE_STATUS,
            'orders.refuseReturn' => PermKeys::ORDERS_REJECT,
            'orders.changeMaid' => PermKeys::ORDERS_CHANGE_MAID,
            'orders.changeMaidById' => PermKeys::ORDERS_CHANGE_MAID,
            'orders.changeResponsible' => PermKeys::ORDERS_CHANGE_RESPONSIBLE,
            'orders.changeResponsibleById' => PermKeys::ORDERS_CHANGE_RESPONSIBLE,
            'orders.completeOrder' => PermKeys::ORDERS_ADD_CONTRACT,
            'orders.destroy' => PermKeys::ORDERS_DELETE,
            'showSingleOrder' => PermKeys::ORDERS_VIEW,
        ];
        foreach ($actions as $route => $permission) {
            $this->assertSame(403, $this->gate($route), $route);
            $admin->givePermissionTo($permission);
            $this->assertSame(200, $this->gate($route), $route);
            $admin->revokePermissionTo($permission);
            $this->assertContains($permission, PermissionCatalog::grantablePermissions('admin')->all());
        }
    }

    public function test_status_editor_cannot_bypass_refusal_permission(): void
    {
        (require database_path('migrations/2026_09_21_120000_expose_order_action_permissions.php'))->up();
        $admin = Admin::factory()->create();
        $admin->givePermissionTo(PermKeys::ORDERS_CHANGE_STATUS);
        $this->actingAs($admin, 'admin');
        foreach (['orders.update', 'orders.changeStatus'] as $route) {
            $this->assertSame(200, $this->gate($route, ['status' => 'under_process']));
            foreach (['refused', 'return'] as $status) {
                $this->assertSame(403, $this->gate($route, ['status' => $status]));
                $admin->givePermissionTo(PermKeys::ORDERS_REJECT);
                $this->assertSame(200, $this->gate($route, ['status' => $status]));
                $admin->revokePermissionTo(PermKeys::ORDERS_REJECT);
            }
        }
    }

    public function test_linking_an_existing_order_also_requires_add_contract_permission(): void
    {
        (require database_path('migrations/2026_09_21_120000_expose_order_action_permissions.php'))->up();
        $admin = Admin::factory()->create();
        $permission = Permission::firstOrCreate(['name' => PermKeys::REC_CONTRACTS_CREATE, 'guard_name' => 'admin']);
        $admin->givePermissionTo($permission);
        $this->actingAs($admin, 'admin');

        foreach (['RecruitmentContract.create' => 'orderId', 'RecruitmentContract.store' => 'order_id'] as $route => $field) {
            $this->assertSame(200, $this->gate($route));
            $this->assertSame(403, $this->gate($route, [$field => 1]));
            $admin->givePermissionTo(PermKeys::ORDERS_ADD_CONTRACT);
            $this->assertSame(200, $this->gate($route, [$field => 1]));
            $admin->revokePermissionTo(PermKeys::ORDERS_ADD_CONTRACT);
        }
    }
}
