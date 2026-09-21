<?php

namespace Tests\Feature\Admin;

use App\Models\auth\Admin;
use App\Models\Branch;
use App\Models\Permission;
use App\PermKeys;
use App\Support\InvoiceAuthorization;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InvoiceCreatePermissionTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    public function test_control_permission_alone_does_not_allow_creation(): void
    {
        $actor = Admin::factory()->forBranch(Branch::factory()->main()->create())->create();
        $actor->givePermissionTo(Permission::firstOrCreate(['name' => PermKeys::INVOICES_CONTROL, 'guard_name' => 'admin']));
        $this->actingAs($actor, 'admin');

        $this->assertFalse(InvoiceAuthorization::canCreate());
        $this->get(route('invoices.create'))->assertForbidden();
        $this->postJson(route('invoices.store'), [])->assertForbidden();
    }

    public function test_create_permission_alone_allows_form_and_revoking_it_blocks_creation(): void
    {
        $actor = Admin::factory()->forBranch(Branch::factory()->main()->create())->create();
        $permission = Permission::firstOrCreate(['name' => PermKeys::INVOICES_CREATE, 'guard_name' => 'admin']);
        $actor->givePermissionTo($permission);
        $this->actingAs($actor, 'admin');

        $this->assertTrue(InvoiceAuthorization::canCreate());
        $this->get(route('invoices.create'))->assertOk();
        $actor->revokePermissionTo($permission);
        $this->actingAs($actor->fresh(), 'admin');
        $this->assertFalse(InvoiceAuthorization::canCreate());
        $this->get(route('invoices.create'))->assertForbidden();
        $this->postJson(route('invoices.store'), [])->assertForbidden();
    }
}
