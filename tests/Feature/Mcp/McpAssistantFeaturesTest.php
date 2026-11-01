<?php

namespace Tests\Feature\Mcp;

use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\McpAccessToken;
use App\Models\McpAdminAccess;
use App\Models\McpAuditLog;
use App\Models\Permission;
use App\NewPermKeys;
use App\PermKeys;
use App\Support\McpSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/** الصفحات والإخفاء والروابط وneeds_attention والقوالب وموافقة الموظف داخل ClickERP. */
class McpAssistantFeaturesTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    private Admin $admin;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('dev_superadmin_settings')->where('key', 'mcp_server')->delete();
        Cache::flush();
        $this->enableBranchesModule(false);
        McpSettings::persist(['enabled' => true, 'rate_limit_per_minute' => 1000, 'write_enabled' => true, 'access_mode' => 'selected']);

        $this->admin = Admin::factory()->create();
        McpAdminAccess::query()->create(['admin_id' => $this->admin->id, 'access_level' => 'write']);
        $this->token = McpAccessToken::issue($this->admin, 'test', 1)[1];
        RateLimiter::clear('mcp-write:' . $this->admin->id);
    }

    private function grant(string ...$permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::withoutGlobalScopes()->updateOrCreate(
                ['name' => $permission, 'guard_name' => 'admin'],
                ['display_name' => $permission, 'group_name' => 'mcp_test', 'section_name' => 'mcp_test', 'is_active' => true]
            );
            $this->admin->givePermissionTo($permission);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function rpc(string $method, array $params = [])
    {
        return $this->withToken($this->token)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params])->assertOk();
    }

    private function tool(string $name, array $arguments = [])
    {
        return $this->rpc('tools/call', ['name' => $name, 'arguments' => $arguments]);
    }

    private function contract(array $extra = []): int
    {
        return DB::table('contracts')->insertGetId($extra + [
            'arabia_order_number' => random_int(900000000, 999999999),
            'status' => 'msaned',
            'type' => 'recruitment',
            'contract_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function lists_page_through_every_result_and_link_to_the_screen(): void
    {
        $this->grant(NewPermKeys::REC_CONTRACTS_VIEW);
        $marker = 'V' . uniqid();
        foreach (range(1, 3) as $i) {
            $this->contract(['visa_number' => $marker]);
        }

        $first = $this->tool('list_recruitment_contracts', ['query' => $marker, 'limit' => 2])->json('result.structuredContent');
        $this->assertSame(3, $first['total']);
        $this->assertSame(2, $first['next_offset']);
        $this->assertStringContainsString('/RecruitmentContract/', (string) $first['contracts'][0]['url']);

        $second = $this->tool('list_recruitment_contracts', ['query' => $marker, 'limit' => 2, 'offset' => 2])->json('result.structuredContent');
        $this->assertSame(1, $second['returned']);
        $this->assertNull($second['next_offset']);
        $this->assertNotEquals($first['contracts'][0]['id'], $second['contracts'][0]['id']);
    }

    /** @test */
    public function free_text_hides_long_numbers_but_keeps_dates_and_short_numbers(): void
    {
        $this->grant(NewPermKeys::REC_CONTRACTS_VIEW);
        $id = $this->contract();
        DB::table('contract_notes')->insert([
            'contract_id' => $id,
            'note' => 'جوال العميل 0551234567 وجواز EP1234567 وبالعربية ٠٥٥١٢٣٤٥٦٧ بتاريخ 2026-11-01 عقد 123',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $tool = new class extends \App\Mcp\Tools\WhoAmITool {
            public function run(string $text): ?string
            {
                return $this->maskText($text);
            }
        };

        $masked = $tool->run('جوال 0551234567 وجواز EP1234567 وبالعربية ٠٥٥١٢٣٤٥٦٧ بتاريخ 2026-11-01 عقد 123');
        $this->assertStringNotContainsString('0551234567', $masked);
        $this->assertStringNotContainsString('EP1234567', $masked);
        $this->assertStringNotContainsString('٠٥٥١٢٣٤٥٦٧', $masked);
        $this->assertStringContainsString('2026-11-01', $masked);
        $this->assertStringContainsString('عقد 123', $masked);
        $this->assertStringContainsString('4567', $masked);
    }

    /** @test */
    public function needs_attention_shows_only_permitted_sections(): void
    {
        $this->assertNotContains('needs_attention', collect($this->rpc('tools/list')->json('result.tools'))->pluck('name')->all());

        $this->grant(NewPermKeys::REC_CONTRACTS_VIEW);
        $id = $this->contract(['updated_at' => now()->subDays(40)]);

        $result = $this->tool('needs_attention', ['section' => 'recruitment_stalled', 'limit' => 50, 'stalled_days' => 30])->json('result.structuredContent');
        $this->assertGreaterThanOrEqual(1, $result['sections']['recruitment_stalled']['count']);

        $all = $this->tool('needs_attention')->json('result.structuredContent');
        $this->assertArrayNotHasKey('invoices_unpaid', $all['sections']);
        $this->assertArrayNotHasKey('complaints_overdue', $all['sections']);

        $this->assertTrue($this->tool('needs_attention', ['section' => 'invoices_unpaid'])->json('result.isError'));
        $this->assertNotNull($id);
    }

    /** @test */
    public function every_new_read_tool_answers_for_a_permitted_employee(): void
    {
        $this->grant(PermKeys::INVOICES_VIEW);

        $result = $this->tool('receivables_report')->json('result');
        $this->assertFalse($result['isError'], json_encode($result, JSON_UNESCAPED_UNICODE));
        $this->assertArrayHasKey('aging', $result['structuredContent']);
    }

    /** @test */
    public function prompts_follow_the_tools_the_employee_has(): void
    {
        $this->assertSame([], collect($this->rpc('prompts/list')->json('result.prompts'))->where('name', 'morning_briefing')->values()->all());

        $this->grant(NewPermKeys::REC_CONTRACTS_VIEW);
        $this->assertContains('morning_briefing', collect($this->rpc('prompts/list')->json('result.prompts'))->pluck('name')->all());
        $this->assertStringContainsString('needs_attention', $this->rpc('prompts/get', ['name' => 'morning_briefing'])->json('result.messages.0.content.text'));
        $this->assertArrayHasKey('error', $this->rpc('prompts/get', ['name' => 'not_a_prompt'])->json());
    }

    /** @test */
    public function an_empty_search_is_counted_in_the_log(): void
    {
        $this->grant(NewPermKeys::REC_CONTRACTS_VIEW);
        $this->tool('list_recruitment_contracts', ['query' => 'لا-يوجد-' . uniqid()]);

        $this->assertSame(0, McpAuditLog::query()->where('admin_id', $this->admin->id)->latest('id')->value('result_count'));
    }

    /** @test */
    public function the_model_cannot_approve_a_write_with_its_own_token(): void
    {
        $this->grant(NewPermKeys::REC_CONTRACTS_VIEW, NewPermKeys::REC_CONTRACTS_UPDATE);
        $id = $this->contract();

        $preview = $this->tool('add_contract_note', ['contract_id' => $id, 'note' => 'ملاحظة المساعد'])->json('result.structuredContent');
        $this->assertSame('awaiting_approval', $preview['status']);
        $this->assertNotEmpty($preview['confirm_url']);

        $attempt = $this->tool('add_contract_note', ['confirmation_token' => $preview['confirmation_token']])->json('result');
        $this->assertTrue($attempt['isError']);
        $this->assertSame(0, DB::table('contract_notes')->where('contract_id', $id)->count());
    }

    /** @test */
    public function only_the_logged_in_owner_can_approve_on_the_page(): void
    {
        $this->grant(NewPermKeys::REC_CONTRACTS_VIEW, NewPermKeys::REC_CONTRACTS_UPDATE);
        $id = $this->contract();
        $preview = $this->tool('add_contract_note', ['contract_id' => $id, 'note' => 'ملاحظة المساعد'])->json('result.structuredContent');
        $url = $preview['confirm_url'];
        $token = $preview['confirmation_token'];

        // طلب /mcp يضع الموظف في الحارس داخل نفس التطبيق أثناء الاختبار؛ الطلب الحقيقي للصفحة بلا هذا.
        $this->app['auth']->forgetGuards();

        // بلا جلسة: تحويل لتسجيل الدخول، ولا تنفيذ بدون جلسة.
        $this->get($url)->assertRedirect(route('admin.form.login'));
        $this->post($url)->assertForbidden();
        $this->assertSame(0, DB::table('contract_notes')->where('contract_id', $id)->count());

        // موظف آخر لا يرى طلب غيره.
        $this->actingAs(Admin::factory()->create(), 'admin')->get($url)->assertNotFound();

        $this->actingAs($this->admin, 'admin')->get($url)->assertOk()->assertSee('ملاحظة المساعد');
        $this->actingAs($this->admin, 'admin')->post($url)->assertOk()->assertSee('تم التنفيذ');
        $this->assertSame(1, DB::table('contract_notes')->where('contract_id', $id)->where('admin_id', $this->admin->id)->count());

        // إعادة الفتح لا تكرر التنفيذ، والنموذج يعرف النتيجة بالرمز.
        $this->actingAs($this->admin, 'admin')->post($url)->assertNotFound();
        $this->assertSame(1, DB::table('contract_notes')->where('contract_id', $id)->count());
        $this->assertSame('created', $this->tool('add_contract_note', ['confirmation_token' => $token])->json('result.structuredContent.status'));
    }

    /** @test */
    public function complaint_status_tool_uses_the_screen_logic(): void
    {
        $this->grant(PermKeys::COMPLAINT_VIEW, PermKeys::COMPLAINT_EDIT);
        McpSettings::persist(['human_confirmation' => false]);

        $id = DB::table('complaints')->insertGetId(['status' => 'pending', 'subject' => 'شكوى', 'message' => 'نص', 'created_at' => now(), 'updated_at' => now()]);

        $preview = $this->tool('update_complaint_status', ['complaint_id' => $id, 'status' => 'resolved'])->json('result.structuredContent');
        $this->assertSame('pending', DB::table('complaints')->where('id', $id)->value('status'));

        $this->tool('update_complaint_status', ['confirmation_token' => $preview['confirmation_token']]);
        $row = DB::table('complaints')->where('id', $id)->first();
        $this->assertSame('resolved', $row->status);
        $this->assertEquals($this->admin->id, $row->resolved_by);

        $again = $this->tool('update_complaint_status', ['complaint_id' => $id, 'status' => 'pending'])->json('result');
        $this->assertTrue($again['isError']);
    }

    /** @test */
    public function the_complaint_screen_still_changes_status_through_the_shared_service(): void
    {
        $this->grant(PermKeys::COMPLAINT_VIEW, PermKeys::COMPLAINT_EDIT);
        $id = DB::table('complaints')->insertGetId(['status' => 'pending', 'subject' => 'شكوى', 'message' => 'نص', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->admin, 'admin')
            ->postJson(route('complaints.update-status'), ['complaint_id' => $id, 'status' => 'resolved'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $row = DB::table('complaints')->where('id', $id)->first();
        $this->assertSame('resolved', $row->status);
        $this->assertEquals($this->admin->id, $row->resolved_by);
        $this->assertNotNull($row->resolved_at);
    }

    /** @test */
    public function detail_and_report_tools_answer_for_permitted_employees(): void
    {
        $this->grant(PermKeys::RENT_CONTRACT_VIEW, PermKeys::MAIDS_VIEW, PermKeys::CLIENTS_VIEW, PermKeys::CLIENTS_SHOW, PermKeys::COMPLAINT_VIEW, NewPermKeys::REC_CONTRACTS_VIEW, PermKeys::INVOICES_VIEW, PermKeys::TRAVEL_VIEW);
        $this->grant('finance_profit_loss_view');

        $clientId = DB::table('users')->insertGetId(['name' => 'عميل', 'phone' => '5' . random_int(10000000, 99999999), 'created_at' => now(), 'updated_at' => now()]);
        $complaintId = DB::table('complaints')->insertGetId(['status' => 'pending', 'user_id' => $clientId, 'subject' => 'جوال 0551234567', 'message' => 'نص', 'created_at' => now(), 'updated_at' => now()]);
        $rentalId = DB::table('rental_contracts')->insertGetId(['user_id' => $clientId, 'status' => 'active', 'contract_number' => 'R' . uniqid(), 'created_at' => now(), 'updated_at' => now()]);

        $customer = $this->tool('get_customer', ['id' => $clientId])->json('result');
        $this->assertFalse($customer['isError'], json_encode($customer, JSON_UNESCAPED_UNICODE));
        $this->assertSame(1, $customer['structuredContent']['complaints']['open']);
        $this->assertSame(1, $customer['structuredContent']['rental_contracts']['total']);
        $this->assertStringNotContainsString('0551234567', $customer['structuredContent']['complaints']['latest'][0]['subject']);

        $rental = $this->tool('get_rental_contract', ['id' => $rentalId])->json('result');
        $this->assertFalse($rental['isError'], json_encode($rental, JSON_UNESCAPED_UNICODE));
        $this->assertStringContainsString('/RentContract/', (string) $rental['structuredContent']['url']);

        $complaint = $this->tool('get_complaint', ['id' => $complaintId])->json('result');
        $this->assertFalse($complaint['isError'], json_encode($complaint, JSON_UNESCAPED_UNICODE));

        $nationality = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        DB::table('nationalities_translations')->insert(['nationality_id' => $nationality, 'locale' => 'ar', 'title' => 'جنسية-اختبار']);
        $maidId = DB::table('maids')->insertGetId(['name' => 'عاملة اختبار', 'nationality_id' => $nationality, 'age' => 30, 'salary' => 900, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()]);

        $found = $this->tool('search_maids', ['nationality' => 'جنسية-اختبار', 'max_age' => 35, 'max_salary' => 1000])->json('result.structuredContent');
        $this->assertSame(1, $found['total']);
        $this->assertSame(0, $this->tool('search_maids', ['nationality' => 'جنسية-اختبار', 'max_age' => 25])->json('result.structuredContent.total'));

        $maid = $this->tool('get_maid', ['id' => $maidId])->json('result');
        $this->assertFalse($maid['isError'], json_encode($maid, JSON_UNESCAPED_UNICODE));

        $all = $this->tool('needs_attention', ['limit' => 2])->json('result');
        $this->assertFalse($all['isError'], json_encode($all, JSON_UNESCAPED_UNICODE));
        $this->assertArrayHasKey('invoices_unpaid', $all['structuredContent']['sections']);

        $report = $this->tool('recruitment_contracts_report', ['period' => 'last_week', 'compare_to' => 'previous_period'])->json('result');
        $this->assertFalse($report['isError'], json_encode($report, JSON_UNESCAPED_UNICODE));
        $this->assertSame('previous_period', $report['structuredContent']['comparison']['mode']);

        $rentalReport = $this->tool('rental_contracts_report', ['compare_to' => 'same_period_last_year'])->json('result');
        $this->assertFalse($rentalReport['isError'], json_encode($rentalReport, JSON_UNESCAPED_UNICODE));
        $this->assertArrayHasKey('change', $rentalReport['structuredContent']['comparison']);

        $deportation = $this->tool('deportation_report', ['period' => 'this_quarter'])->json('result');
        $this->assertFalse($deportation['isError'], json_encode($deportation, JSON_UNESCAPED_UNICODE));

        $profit = $this->tool('profit_loss_summary', ['from' => now()->startOfYear()->toDateString()])->json('result');
        $this->assertFalse($profit['isError'], json_encode($profit, JSON_UNESCAPED_UNICODE));
        $this->assertArrayHasKey('net', $profit['structuredContent']);
    }

    /** @test */
    public function the_superadmin_overview_renders_usage_panels(): void
    {
        $this->grant(NewPermKeys::REC_CONTRACTS_VIEW);
        $this->tool('list_recruitment_contracts', ['query' => 'بحث-بلا-نتائج-' . uniqid()]);

        $this->withSession(['dev_superadmin_authenticated' => true])
            ->get(route('dev-superadmin.mcp.index', ['tab' => 'overview']))
            ->assertOk()
            ->assertSee('بحث بلا نتائج')
            ->assertSee('بحث-بلا-نتائج-')
            ->assertSee('فرص المتابعة');

        $this->withSession(['dev_superadmin_authenticated' => true])
            ->get(route('dev-superadmin.mcp.index', ['tab' => 'settings']))
            ->assertOk()
            ->assertSee('موافقة الموظف داخل ClickERP');
    }

    /** @test */
    public function the_guide_page_shows_to_every_employee_while_its_switch_is_on(): void
    {
        $this->get(route('mcp.guide'))->assertRedirect(route('admin.form.login'));

        // موظف غير مفعّل في المساعد: يرى الصفحة والرابط في القائمة وتنبيهاً، لكن المساعد يبقى مغلقاً له.
        $other = Admin::factory()->create();
        $this->actingAs($other, 'admin')->get(route('mcp.guide'))->assertOk()
            ->assertSee(route('mcp.guide') . '"', false)
            ->assertSee('لم يُفعَّل المساعد لحسابك');
        $this->app['auth']->forgetGuards();
        $this->withToken(McpAccessToken::issue($other, 'test', 1)[1])
            ->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertForbidden();
        $this->flushHeaders();

        // غير المفعّل لا يرى أمثلة الإضافة حتى لو كان يملك صلاحية شاشتها.
        $other->givePermissionTo(Permission::withoutGlobalScopes()->updateOrCreate(
            ['name' => PermKeys::ORDERS_CREATE, 'guard_name' => 'admin'],
            ['display_name' => 'x', 'group_name' => 'mcp_test', 'section_name' => 'mcp_test', 'is_active' => true]
        ));
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($other->fresh(), 'admin')->get(route('mcp.guide'))->assertOk()->assertDontSee('أنشئ طلب استقدام');

        // الخادم مغلق: الصفحة تظهر بتنبيه وبلا رابط الخادم.
        McpSettings::persist(['enabled' => false]);
        $this->actingAs($this->admin, 'admin')->get(route('mcp.guide'))->assertOk()
            ->assertSee('الخدمة غير مفعّلة حالياً')
            ->assertDontSee('<code dir="ltr">', false);
        McpSettings::persist(['enabled' => true]);

        $this->grant(NewPermKeys::REC_CONTRACTS_VIEW);
        $this->actingAs($this->admin, 'admin')->get(route('mcp.guide'))->assertOk()->assertSee('/mcp')->assertSee('الملخص الصباحي');
    }
}
