<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\EnsureAdminRoutePermission;
use App\Models\auth\Admin;
use App\Models\Permission;
use App\Models\ingaz\Order;
use Illuminate\Support\Facades\DB;
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

    private function gate(string $name, array $data = [], ?int $orderId = null): int
    {
        $request = Request::create('/admin/orders/1', 'POST', $data);
        $request->headers->set('Accept', 'application/json');
        $route = (new Route('POST', 'admin/orders/{id}', fn () => null))->name($name);
        $route->bind($request);
        if ($orderId !== null) {
            $route->setParameter('id', $orderId);
        } else {
            $route->forgetParameter('id');
        }
        $request->setRouteResolver(fn () => $route);
        try {
            return app(EnsureAdminRoutePermission::class)->handle($request, fn () => response('allowed'))->getStatusCode();
        } catch (HttpException $exception) {
            return $exception->getStatusCode();
        }
    }

    public function test_creator_can_reject_return_and_delete_but_other_employees_cannot(): void
    {
        $creator = Admin::factory()->create();
        $responsible = Admin::factory()->create();
        $permission = Permission::firstOrCreate(['name' => PermKeys::ORDERS_DELETE_OWN, 'guard_name' => 'admin']);
        $creator->givePermissionTo($permission);
        $responsible->givePermissionTo($permission);
        $id = DB::table('orders')->insertGetId([
            'status' => 'new',
            'created_by_admin_id' => $creator->id,
            'customer_id' => $responsible->id,
        ]);

        foreach (['orders.refuseReturn', 'orders.destroy', 'orders.restore'] as $route) {
            $this->actingAs($creator, 'admin');
            $this->assertSame(200, $this->gate($route, [], $id));
            $this->actingAs($responsible, 'admin');
            $this->assertSame(403, $this->gate($route, [], $id));
        }

        $this->actingAs($creator, 'admin');
        foreach (['orders.changeMaid', 'orders.changeResponsible', 'orders.changeStatus'] as $route) {
            $this->assertSame(403, $this->gate($route, ['status' => 'under_process'], $id));
        }

        $order = Order::findOrFail($id);
        $this->assertTrue($order->adminCanReject($creator));
        $this->assertTrue($order->adminCanDelete($creator));
        $this->assertFalse($order->adminCanReject($responsible));
        $this->assertFalse($order->adminCanDelete($responsible));
        $this->assertFalse($order->wasCreatedByAdmin(null));
    }

    public function test_creation_records_authenticated_admin_and_responsibility_changes_preserve_creator(): void
    {
        $creator = Admin::factory()->create();
        $responsible = Admin::factory()->create();
        $this->actingAs($creator, 'admin');
        $order = Order::create([
            'status' => 'refused',
            'customer_id' => $responsible->id,
            'created_by_admin_id' => $responsible->id,
        ]);
        $this->assertSame($creator->id, (int) $order->fresh()->created_by_admin_id);
        $order->syncResponsibleAdmin($responsible->id);
        $this->assertTrue($order->fresh()->wasCreatedByAdmin($creator));
        $this->assertFalse($order->fresh()->wasCreatedByAdmin($responsible));
    }

    public function test_deleting_other_employees_orders_requires_global_delete_permission(): void
    {
        $creator = Admin::factory()->create();
        $employee = Admin::factory()->create();
        $id = DB::table('orders')->insertGetId([
            'status' => 'new', 'created_by_admin_id' => $creator->id, 'customer_id' => $employee->id,
        ]);
        $this->actingAs($employee, 'admin');
        $this->deleteJson(route('orders.destroy', $id))->assertForbidden();
        $this->assertNull(DB::table('orders')->where('id', $id)->value('deleted_at'));
        $permission = Permission::firstOrCreate(['name' => PermKeys::ORDERS_DELETE, 'guard_name' => 'admin']);
        $employee->givePermissionTo($permission);
        $this->deleteJson(route('orders.destroy', $id))->assertOk();
        $this->assertNotNull(DB::table('orders')->where('id', $id)->value('deleted_at'));
        $employee->revokePermissionTo($permission);
        $this->postJson(route('orders.restore', $id))->assertForbidden();
    }

    public function test_missing_creator_does_not_grant_ownership_to_responsible_employee(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');
        $id = DB::table('orders')->insertGetId(['status' => 'new', 'customer_id' => $admin->id]);
        $this->assertSame(403, $this->gate('orders.destroy', [], $id));
        $this->assertSame(403, $this->gate('orders.refuseReturn', [], $id));
    }

    public function test_creator_cannot_delete_or_restore_without_own_delete_permission(): void
    {
        $creator = Admin::factory()->create();
        $this->actingAs($creator, 'admin');
        $id = DB::table('orders')->insertGetId(['status' => 'new', 'created_by_admin_id' => $creator->id]);
        $order = Order::findOrFail($id);
        $this->assertFalse($order->adminCanDelete($creator));
        $this->assertSame(403, $this->gate('orders.restore', [], $id));
        $this->deleteJson(route('orders.destroy', $id))->assertForbidden();
        $creator->givePermissionTo(Permission::firstOrCreate(['name' => PermKeys::ORDERS_DELETE_OWN, 'guard_name' => 'admin']));
        $this->assertTrue($order->adminCanDelete($creator));
        $this->assertSame(200, $this->gate('orders.restore', [], $id));
        $this->deleteJson(route('orders.destroy', $id))->assertOk();
        $this->assertNotNull(DB::table('orders')->where('id', $id)->value('deleted_at'));
    }

    public function test_creator_can_execute_actions_and_completed_orders_remain_locked(): void
    {
        $admin = Admin::factory()->create();
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => PermKeys::ORDERS_DELETE_OWN, 'guard_name' => 'admin']));
        $this->actingAs($admin, 'admin');
        foreach (['refused', 'return'] as $status) {
            $id = DB::table('orders')->insertGetId(['status' => 'new', 'created_by_admin_id' => $admin->id]);
            $this->postJson(route('orders.refuseReturn', $id), ['action' => $status])->assertOk();
            $this->assertSame($status, DB::table('orders')->where('id', $id)->value('status'));
        }

        $this->deleteJson(route('orders.destroy', $id))->assertOk();
        $this->assertNotNull(DB::table('orders')->where('id', $id)->value('deleted_at'));

        $id = DB::table('orders')->insertGetId(['status' => 'done', 'created_by_admin_id' => $admin->id]);
        $this->deleteJson(route('orders.destroy', $id))->assertStatus(403);
        $this->postJson(route('orders.refuseReturn', $id), ['action' => 'return'])->assertStatus(403);
        $this->assertSame('done', DB::table('orders')->where('id', $id)->value('status'));
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
