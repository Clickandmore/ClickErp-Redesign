<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Models\ingaz\Order;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OrderStaffNotesTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(array $permissions): Admin
    {
        $actor = Admin::factory()->create();
        foreach ($permissions as $name) {
            $actor->givePermissionTo(Permission::findOrCreate($name, 'admin'));
        }
        $this->actingAs($actor, 'admin');

        return $actor;
    }

    private function order(): Order
    {
        $id = DB::table('orders')->insertGetId([
            'order_code' => 'NOTE-' . uniqid(),
            'status' => 'new',
            'job_type_id' => 1, // شاشة الطلبات تعرض طلبات الاستقدام فقط
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Order::withoutGlobalScopes()->findOrFail($id);
    }

    public function test_employee_adds_note_from_orders_table_via_ajax(): void
    {
        $actor = $this->actor([PermKeys::ORDERS_VIEW, PermKeys::ORDERS_EDIT]);
        $order = $this->order();

        $this->postJson(route('orders.notes.store', $order->id), ['note' => '  تم التواصل مع العميل  '])
            ->assertCreated()
            ->assertJsonPath('note.note', 'تم التواصل مع العميل');

        $this->assertDatabaseHas('order_notes', [
            'order_id' => $order->id,
            'admin_id' => $actor->id,
            'note' => 'تم التواصل مع العميل',
        ]);
    }

    public function test_details_page_form_still_redirects_to_notes_section(): void
    {
        $this->actor([PermKeys::ORDERS_VIEW, PermKeys::ORDERS_EDIT]);
        $order = $this->order();

        $this->post(route('orders.notes.store', $order->id), ['note' => 'ملاحظة من صفحة التفاصيل'])
            ->assertRedirect(route('showSingleOrder', ['orderId' => $order->id]) . '#order-notes');
    }

    public function test_note_requires_edit_permission_and_text(): void
    {
        $this->actor([PermKeys::ORDERS_VIEW, PermKeys::ORDERS_EDIT]);
        $order = $this->order();
        $this->postJson(route('orders.notes.store', $order->id), ['note' => ''])
            ->assertStatus(422)->assertJsonValidationErrors('note');

        $this->actor([PermKeys::ORDERS_VIEW]);
        $this->postJson(route('orders.notes.store', $order->id), ['note' => 'غير مسموح'])->assertForbidden();
        $this->assertDatabaseMissing('order_notes', ['order_id' => $order->id]);
    }

    public function test_orders_table_shows_latest_note_escaped_and_searches_notes(): void
    {
        $actor = $this->actor([PermKeys::ORDERS_VIEW, PermKeys::ORDERS_EDIT]);
        $order = $this->order();
        $order->notes()->create(['admin_id' => $actor->id, 'note' => 'كلمة-بحث-فريدة <b>عريض</b>']);

        $columns = collect(['id', 'order_source', 'staff_notes'])->map(fn ($data) => [
            'data' => $data, 'name' => '', 'searchable' => 'true', 'orderable' => 'false',
            'search' => ['value' => '', 'regex' => 'false'],
        ])->all();
        $response = $this->getJson(route('orders.index', [
            'draw' => 1, 'start' => 0, 'length' => 10, 'columns' => $columns,
            'search' => ['value' => 'كلمة-بحث-فريدة', 'regex' => 'false'],
        ]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();

        $this->assertSame(1, (int) $response->json('recordsFiltered'));
        $cell = $response->json('data.0.staff_notes');
        $this->assertStringContainsString('كلمة-بحث-فريدة &lt;b&gt;', $cell);
        $this->assertStringContainsString('order-add-note-btn', $cell);
    }

    public function test_latest_note_is_the_newest_one(): void
    {
        $order = $this->order();
        $admin = Admin::factory()->create();
        $order->notes()->create(['admin_id' => $admin->id, 'note' => 'الأقدم', 'created_at' => now()->subHour()]);
        $order->notes()->create(['admin_id' => $admin->id, 'note' => 'الأحدث']);

        $loaded = Order::withoutGlobalScopes()->with('latestNote.admin')->withCount('notes')->findOrFail($order->id);

        $this->assertSame('الأحدث', $loaded->latestNote->note);
        $this->assertSame(2, (int) $loaded->notes_count);
    }
}
