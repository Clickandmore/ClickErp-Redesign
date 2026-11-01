<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Tools\CreateMaidCvBatchTool;
use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Models\McpAccessToken;
use App\Models\McpAdminAccess;
use App\Models\McpAuditLog;
use App\Models\Permission;
use App\PermKeys;
use App\Support\McpSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use Tests\TestCase;

/**
 * مسار الإضافة كما يعيشه الموظف بالوضع الافتراضي: معاينة في المحادثة، ثم موافقته بنفسه من صفحة ClickERP.
 * يشمل العميل والسيرة الواحدة والسير المجمعة.
 */
class McpBatchAndApprovalFlowTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    private Admin $admin;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('dev_superadmin_settings')->where('key', 'mcp_server')->delete();
        $this->enableBranchesModule(false);
        McpSettings::persist(['enabled' => true, 'rate_limit_per_minute' => 1000, 'write_enabled' => true, 'human_confirmation' => true]);

        $this->admin = Admin::factory()->create();
        McpAdminAccess::query()->create(['admin_id' => $this->admin->id, 'access_level' => 'write']);

        foreach ([PermKeys::CLIENTS_CREATE, PermKeys::MAIDS_CREATE] as $permission) {
            Permission::withoutGlobalScopes()->updateOrCreate(
                ['name' => $permission, 'guard_name' => 'admin'],
                ['display_name' => $permission, 'group_name' => 'mcp_test', 'section_name' => 'mcp_test', 'is_active' => true]
            );
            $this->admin->givePermissionTo($permission);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->token = McpAccessToken::issue($this->admin, 'test', 1)[1];
        RateLimiter::clear('mcp-write:' . $this->admin->id);
    }

    private function tool(string $name, array $arguments = [])
    {
        return $this->withToken($this->token)->postJson('/mcp', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => $name, 'arguments' => $arguments],
        ])->assertOk();
    }

    /** يوافق الموظف من حسابه، ثم يرجع الحارس لوضعه حتى يكمل الاختبار طلبات MCP. */
    private function approve(array $preview)
    {
        $this->assertSame('awaiting_approval', $preview['status'], json_encode($preview, JSON_UNESCAPED_UNICODE));
        $url = $preview['confirm_url'];

        $this->actingAs($this->admin, 'admin')->get($url)->assertOk()->assertSee('موافقة وتنفيذ');

        $response = $this->actingAs($this->admin, 'admin')->post($url)->assertOk();
        $this->app['auth']->forgetGuards();

        return $response;
    }

    private function maidLookups(): array
    {
        $insert = fn (string $table) => DB::table($table)->insertGetId(['created_at' => now(), 'updated_at' => now()]);

        return [
            'gender' => 'female',
            'nationality_id' => $insert('nationalities'),
            'religion_id' => $insert('religions'),
            'type_id' => $insert('job_types'),
            'job_ids' => [$insert('job_titles')],
            'office_id' => $insert('recruitment_offices'),
        ];
    }

    private function passport(): string
    {
        return 'P' . random_int(1000000, 9999999);
    }

    /** @test */
    public function a_customer_is_added_after_the_employee_approves_in_clickerp(): void
    {
        Account::query()->create([
            'name' => 'عملاء اختبار', 'status' => 'debtor', 'type' => AccountTypesEnum::Clients->value,
            'code' => ((int) DB::table('fn_accounts')->max('code')) + 1, 'level' => 1, 'operation_type' => 1,
        ]);
        $phone = '05' . random_int(10000000, 99999999);
        $arguments = ['name' => 'سعد القحطاني', 'phone' => $phone, 'client_type' => 'person', 'national_num' => (string) random_int(1000000000, 1999999999)];

        $preview = $this->tool('create_customer', $arguments)->json('result.structuredContent');
        $this->assertDatabaseMissing('users', ['phone' => substr($phone, 1)]);

        // المساعد لا يستطيع الموافقة عن الموظف حتى لو أرسل الرمز.
        $this->tool('create_customer', ['confirmation_token' => $preview['confirmation_token']])->assertJsonPath('result.isError', true);
        $this->assertDatabaseMissing('users', ['phone' => substr($phone, 1)]);

        $page = $this->approve($preview);
        $customer = User::query()->where('phone', substr($phone, 1))->firstOrFail();
        $page->assertSee('تم التنفيذ')->assertSee(route('clients.show', $customer->id), false);

        $this->assertSame('mcp', $customer->register_from);
        $this->assertSame($this->admin->id, (int) $customer->created_by);
        $this->assertNotNull($customer->account_id);

        // بعد الموافقة يعرف المساعد النتيجة بالرمز نفسه، بلا سجل جديد.
        $this->tool('create_customer', ['confirmation_token' => $preview['confirmation_token']])
            ->assertJsonPath('result.structuredContent.customer.id', $customer->id);
    }

    /** @test */
    public function a_single_cv_is_added_after_approval_and_the_page_reads_in_arabic(): void
    {
        $passport = $this->passport();
        $preview = $this->tool('create_maid_cv', ['name' => 'Maria Santos', 'passport_num' => $passport] + $this->maidLookups())->json('result.structuredContent');

        $this->actingAs($this->admin, 'admin')->get($preview['confirm_url'])->assertOk()->assertSee('رقم الجواز')->assertDontSee('passport_num');
        $this->app['auth']->forgetGuards();

        $page = $this->approve($preview);
        $maid = Maid::query()->where('passport_num', $passport)->firstOrFail();
        $page->assertSee(route('maids.show', $maid->id), false);
        $this->assertSame($this->admin->id, (int) $maid->added_by);

        // الرابط لا يُبنى من صفحة الموافقة؛ النتيجة تدل المساعد على أداة الرابط.
        $result = $this->tool('create_maid_cv', ['confirmation_token' => $preview['confirmation_token']])->json('result.structuredContent');
        $this->assertStringContainsString('maid_media_upload_link', $result['next_step']);
    }

    /** @test */
    public function a_batch_saves_valid_rows_only_and_reports_the_rest(): void
    {
        $lookups = $this->maidLookups();
        $existing = $this->passport();
        $first = $this->passport();
        $second = $this->passport();
        $this->tool('create_maid_cv', ['passport_num' => $existing] + $lookups);
        $seed = $this->tool('create_maid_cv', ['passport_num' => $existing] + $lookups)->json('result.structuredContent');
        $this->approve($seed);

        $rows = [
            ['name' => 'Maria Santos', 'passport_num' => strtolower($first)] + $lookups,
            ['name' => 'Ana Cruz', 'passport_num' => $second] + $lookups,
            ['name' => 'Same File', 'passport_num' => $first] + $lookups,
            ['name' => 'Old One', 'passport_num' => $existing] + $lookups,
            ['name' => 'Bad Name 123', 'passport_num' => $this->passport()] + $lookups,
            ['name' => 'Wrong Office', 'passport_num' => $this->passport(), 'office_id' => 0] + $lookups,
        ];

        $preview = $this->tool('create_maid_cvs_batch', ['rows' => $rows])->json('result.structuredContent');
        $this->assertSame(6, $preview['preview']['total_rows']);
        $this->assertSame(2, $preview['preview']['will_save']);
        $this->assertSame(4, $preview['preview']['will_skip']);
        $this->assertStringContainsString('مكرر في الصف 1', $preview['preview']['rejected_rows'][0]);
        $this->assertDatabaseMissing('maids', ['passport_num' => $first]);

        // الموافقة إلزامية حتى لو كان الرمز عند المساعد.
        $this->tool('create_maid_cvs_batch', ['confirmation_token' => $preview['confirmation_token']])->assertJsonPath('result.isError', true);
        $this->assertDatabaseMissing('maids', ['passport_num' => $first]);

        $this->approve($preview)->assertSee('السير المحفوظة: 2')->assertSee('Same File');

        foreach ([$first, $second] as $passport) {
            $maid = Maid::query()->where('passport_num', $passport)->firstOrFail();
            $this->assertSame($this->admin->id, (int) $maid->added_by);
            $this->assertSame('available', $maid->status);
            $this->assertSame($lookups['job_ids'], $maid->jobTitles()->pluck('job_titles.id')->map(fn ($id) => (int) $id)->all());
        }

        $this->assertSame(1, Maid::query()->where('passport_num', $existing)->count());

        // إعادة إرسال الملف بعد انقطاع لا تكرر شيئاً.
        $this->tool('create_maid_cvs_batch', ['rows' => array_slice($rows, 0, 2)])->assertJsonPath('result.isError', true);
        $this->assertSame(1, Maid::query()->where('passport_num', $first)->count());
    }

    /** @test */
    public function a_batch_needs_approval_even_when_the_setting_is_off_and_counts_as_one_write(): void
    {
        McpSettings::persist(['human_confirmation' => false, 'write_limit_per_hour' => 1]);
        $lookups = $this->maidLookups();
        $rows = [['passport_num' => $this->passport()] + $lookups, ['passport_num' => $this->passport()] + $lookups];

        $this->approve($this->tool('create_maid_cvs_batch', ['rows' => $rows])->json('result.structuredContent'))->assertSee('السير المحفوظة: 2');

        $this->assertSame(2, Maid::query()->whereIn('passport_num', array_column($rows, 'passport_num'))->count());
    }

    /** @test */
    public function cv_files_of_a_batch_are_generated_only_after_the_response(): void
    {
        $generator = \Mockery::mock(\App\Services\ingaz\MaidCvPdfGenerator::class);
        $this->app->instance(\App\Services\ingaz\MaidCvPdfGenerator::class, $generator);
        $lookups = $this->maidLookups();
        $rows = [['passport_num' => $this->passport()] + $lookups, ['passport_num' => $this->passport()] + $lookups];

        $generated = 0;
        $generator->shouldReceive('generateIfEnabled')->andReturnUsing(function () use (&$generated) {
            $generated++;
        });

        // بلا طلب HTTP: مسار الاختبار نفسه يستدعي terminate بعد كل طلب فلا يكشف التأجيل.
        $token = $this->tool('create_maid_cvs_batch', ['rows' => $rows])->json('result.structuredContent.confirmation_token');
        $this->actingAs($this->admin, 'admin');
        app(CreateMaidCvBatchTool::class)->execute($token, $this->admin);
        $this->assertSame(0, $generated);

        $this->app->terminate();
        $this->assertSame(2, $generated);
    }

    /** @test */
    public function a_batch_over_the_limit_or_without_permission_is_refused(): void
    {
        $lookups = $this->maidLookups();
        $rows = array_fill(0, CreateMaidCvBatchTool::MAX_ROWS + 1, ['passport_num' => 'X'] + $lookups);
        $this->tool('create_maid_cvs_batch', ['rows' => $rows])->assertJsonPath('result.isError', true);

        $this->admin->revokePermissionTo(PermKeys::MAIDS_CREATE);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->tool('create_maid_cvs_batch', ['rows' => [['passport_num' => $this->passport()] + $lookups]])->assertJsonPath('result.isError', true);
    }

    /** @test */
    public function passport_numbers_inside_a_batch_are_masked_in_the_audit_log(): void
    {
        $passport = $this->passport();
        $this->tool('create_maid_cvs_batch', ['rows' => [['passport_num' => $passport] + $this->maidLookups()]]);

        $log = McpAuditLog::query()->where('admin_id', $this->admin->id)->where('tool', 'create_maid_cvs_batch')->latest('id')->firstOrFail();
        $this->assertStringNotContainsString($passport, json_encode($log->arguments));
    }
}
