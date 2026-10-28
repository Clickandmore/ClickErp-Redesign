<?php

namespace Tests\Feature\Finance;

use App\Models\auth\Admin;
use App\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DailyCashReferenceLinesTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
        DB::table('fn_daily_cash_reference_lines')->delete();
    }

    private function grant(string $name): void
    {
        $this->admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']));
    }

    private function account(string $code, string $name): int
    {
        return DB::table('fn_accounts')->insertGetId([
            'name' => $name, 'code' => $code, 'level' => 1, 'type' => 1, 'status' => 'debtor', 'operation_type' => 1,
            'parent_id' => null, 'created_by_id' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_page_requires_permission(): void
    {
        $this->get(route('finance.daily-cash-reference-lines.index'))->assertForbidden();
        $this->grant('finance_daily_cash_reference_lines_view');
        $this->get(route('finance.daily-cash-reference-lines.index'))->assertOk()->assertSee('ربط البنود بحساباتها');
        $this->post(route('finance.daily-cash-reference-lines.store'), ['link' => 1])->assertForbidden();
    }

    public function test_link_add_edit_reorder_and_delete_lines(): void
    {
        foreach (['view', 'create', 'edit', 'delete'] as $ability) $this->grant("finance_daily_cash_reference_lines_$ability");

        $this->post(route('finance.daily-cash-reference-lines.store'), ['link' => 1])->assertRedirect();
        $count = DB::table('fn_daily_cash_reference_lines')->count();
        $this->assertSame(136, $count);
        $this->post(route('finance.daily-cash-reference-lines.store'), ['link' => 1])->assertSessionHasErrors('link');

        $id = $this->account('987654321', 'حساب اختبار');
        $this->post(route('finance.daily-cash-reference-lines.store'), ['group_key' => 'expenses', 'account_id' => $id])->assertRedirect();
        $line = DB::table('fn_daily_cash_reference_lines')->where('account_id', $id)->first();
        $this->assertSame('حساب اختبار', $line->label);
        $this->assertSame('expenses', $line->group_key);
        $this->post(route('finance.daily-cash-reference-lines.store'), ['group_key' => 'other', 'account_id' => $id])->assertSessionHasErrors('group_key');

        $this->put(route('finance.daily-cash-reference-lines.update', $line->id), ['direction' => 'up'])->assertRedirect();
        $this->assertSame((int) $line->sort_order - 1, (int) DB::table('fn_daily_cash_reference_lines')->where('id', $line->id)->value('sort_order'));

        $this->put(route('finance.daily-cash-reference-lines.update', $line->id), ['account_id' => $id, 'label' => 'تسمية جديدة'])->assertRedirect();
        $this->assertSame('تسمية جديدة', DB::table('fn_daily_cash_reference_lines')->where('id', $line->id)->value('label'));

        $this->get(route('finance.daily-cash-reference-lines.index'))->assertOk()->assertSee('تسمية جديدة');

        $this->delete(route('finance.daily-cash-reference-lines.destroy', $line->id))->assertRedirect();
        $this->assertSame($count, DB::table('fn_daily_cash_reference_lines')->count());
    }
}
