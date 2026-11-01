<?php

namespace Tests\Feature\Mcp;

use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Models\McpAccessToken;
use App\Models\McpAdminAccess;
use App\Models\Permission;
use App\PermKeys;
use App\Support\McpSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use Tests\TestCase;

/**
 * أدوات الإضافة: لا تظهر إلا بمفتاح الكتابة ومستوى «قراءة وإضافة»، والمعاينة لا تحفظ شيئاً،
 * والتأكيد يحفظ مرة واحدة فقط بالمدخلات التي رآها المستخدم.
 */
class McpWriteToolsTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    private Admin $admin;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('dev_superadmin_settings')->where('key', 'mcp_server')->delete();
        // الإضافة في وضع الفروع مغطاة باختبارات شاشة العملاء؛ هنا يكفي الوضع العادي.
        $this->enableBranchesModule(false);
        McpSettings::persist(['enabled' => true, 'rate_limit_per_minute' => 1000, 'write_enabled' => true, 'human_confirmation' => false]);

        $this->admin = Admin::factory()->create();
        McpAdminAccess::query()->create(['admin_id' => $this->admin->id, 'access_level' => 'write']);
        $this->grant(PermKeys::CLIENTS_CREATE, PermKeys::MAIDS_CREATE);
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

    private function tool(string $name, array $arguments = [], ?string $token = null)
    {
        return $this->withToken($token ?? $this->token)->postJson('/mcp', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => $name, 'arguments' => $arguments],
        ])->assertOk();
    }

    private function tools(?string $token = null): array
    {
        return collect($this->withToken($token ?? $this->token)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->json('result.tools'))->pluck('name')->all();
    }

    private function clientsParentAccount(): void
    {
        Account::query()->create([
            'name' => 'عملاء اختبار المساعد', 'status' => 'debtor', 'type' => AccountTypesEnum::Clients->value,
            'code' => ((int) DB::table('fn_accounts')->max('code')) + 1, 'level' => 1, 'operation_type' => 1,
        ]);
    }

    private function customerArguments(): array
    {
        return ['name' => 'عميل المساعد', 'phone' => '05' . random_int(10000000, 99999999), 'client_type' => 'person', 'national_num' => (string) random_int(1000000000, 1999999999)];
    }

    /** @test */
    public function write_tools_need_the_global_switch_and_the_write_level(): void
    {
        $this->assertContains('create_customer', $this->tools());
        $this->assertContains('create_maid_cv', $this->tools());

        McpAdminAccess::query()->where('admin_id', $this->admin->id)->update(['access_level' => 'read']);
        $this->assertNotContains('create_customer', $this->tools());
        $this->tool('create_customer', $this->customerArguments())->assertJsonPath('result.isError', true);

        McpAdminAccess::query()->where('admin_id', $this->admin->id)->update(['access_level' => 'write']);
        McpSettings::persist(['write_enabled' => false]);
        $this->assertNotContains('create_customer', $this->tools());
        $this->assertNotContains('create_maid_cv', $this->tools());
    }

    /** @test */
    public function a_customer_is_saved_only_after_confirmation_and_only_once(): void
    {
        $this->clientsParentAccount();
        $arguments = $this->customerArguments();
        $phone = substr($arguments['phone'], 1);

        $preview = $this->tool('create_customer', $arguments)->json('result.structuredContent');
        $this->assertSame('preview', $preview['status']);
        $this->assertFalse($preview['saved']);
        $this->assertSame('عميل المساعد', $preview['preview']['name']);
        $this->assertDatabaseMissing('users', ['phone' => $phone]);

        // البيانات المرسلة مع التأكيد تُتجاهل: يُحفظ ما رآه المستخدم في المعاينة.
        $created = $this->tool('create_customer', ['name' => 'اسم آخر', 'confirmation_token' => $preview['confirmation_token']] + $arguments)
            ->json('result.structuredContent');
        $this->assertSame('created', $created['status']);

        $customer = User::query()->where('phone', $phone)->firstOrFail();
        $this->assertSame('عميل المساعد', $customer->name);
        $this->assertSame('mcp', $customer->register_from);
        $this->assertSame($this->admin->id, (int) $customer->created_by);
        $this->assertNotNull($customer->account_id);
        $this->assertSame($customer->id, $created['customer']['id']);

        // إعادة نفس التأكيد (انقطاع اتصال مثلاً) ترجع النتيجة نفسها ولا تنشئ عميلاً ثانياً.
        $again = $this->tool('create_customer', ['confirmation_token' => $preview['confirmation_token']] + $arguments)->json('result.structuredContent');
        $this->assertSame($customer->id, $again['customer']['id']);
        $this->assertSame(1, User::query()->where('phone', $phone)->count());
    }

    /** @test */
    public function duplicates_and_bad_data_come_back_as_readable_errors(): void
    {
        $this->clientsParentAccount();
        $arguments = $this->customerArguments();
        $token = $this->tool('create_customer', $arguments)->json('result.structuredContent.confirmation_token');
        $this->tool('create_customer', ['confirmation_token' => $token] + $arguments);

        $duplicate = $this->tool('create_customer', $arguments);
        $duplicate->assertJsonPath('result.isError', true);
        $this->assertStringContainsString('phone', $duplicate->json('result.content.0.text'));

        $bad = $this->tool('create_customer', ['name' => 'Ali 123', 'phone' => '123', 'client_type' => 'person']);
        $bad->assertJsonPath('result.isError', true);
        $this->assertStringContainsString('البيانات غير صالحة', $bad->json('result.content.0.text'));
    }

    /** @test */
    public function a_confirmation_token_belongs_to_one_admin_and_one_tool(): void
    {
        $this->clientsParentAccount();
        $arguments = $this->customerArguments();
        $token = $this->tool('create_customer', $arguments)->json('result.structuredContent.confirmation_token');

        $other = Admin::factory()->create();
        McpAdminAccess::query()->create(['admin_id' => $other->id, 'access_level' => 'write']);
        $other->givePermissionTo(PermKeys::CLIENTS_CREATE);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $otherToken = McpAccessToken::issue($other, 'test', 1)[1];

        $this->tool('create_customer', ['confirmation_token' => $token] + $arguments, $otherToken)->assertJsonPath('result.isError', true);
        $this->tool('create_maid_cv', ['confirmation_token' => $token])->assertJsonPath('result.isError', true);
        $this->assertDatabaseMissing('users', ['phone' => substr($arguments['phone'], 1)]);
    }

    /** @test */
    public function a_maid_cv_goes_through_the_rapid_cv_rules_and_is_saved_on_confirmation(): void
    {
        $nationality = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $religion = DB::table('religions')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $jobType = DB::table('job_types')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $jobTitle = DB::table('job_titles')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $office = DB::table('recruitment_offices')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $passport = 'P' . random_int(1000000, 9999999);

        $arguments = [
            'name' => 'Maria Santos', 'passport_num' => strtolower($passport), 'gender' => 'female',
            'nationality_id' => $nationality, 'religion_id' => $religion, 'type_id' => $jobType,
            'job_ids' => [$jobTitle], 'office_id' => $office,
        ];

        $options = $this->tool('form_options', ['form' => 'maid'])->json('result.structuredContent');
        $this->assertArrayHasKey('nationalities', $options);

        $preview = $this->tool('create_maid_cv', $arguments)->json('result.structuredContent');
        $this->assertSame('preview', $preview['status']);
        $this->assertSame($passport, $preview['preview']['passport_num']);
        $this->assertDatabaseMissing('maids', ['passport_num' => $passport]);

        $created = $this->tool('create_maid_cv', $arguments + ['confirmation_token' => $preview['confirmation_token']])->json('result.structuredContent');
        $maid = Maid::query()->where('passport_num', $passport)->firstOrFail();
        $this->assertSame($maid->id, $created['maid']['id']);
        $this->assertSame('Maria Santos', $maid->name);
        $this->assertSame('available', $maid->status);
        $this->assertSame($this->admin->id, (int) $maid->added_by);
        $this->assertSame([$jobTitle], $maid->jobTitles()->pluck('job_titles.id')->map(fn ($id) => (int) $id)->all());
        // بدون صلاحية تعديل العاملة لا يُعطى رابط رفع الصور؛ يُكمل من الشاشة.
        $this->assertArrayNotHasKey('media_upload', $created);
        $this->assertArrayHasKey('next_step', $created);

        $this->tool('create_maid_cv', $arguments)->assertJsonPath('result.isError', true);
    }

    /** @test */
    public function the_hourly_write_limit_from_superadmin_is_enforced(): void
    {
        $this->clientsParentAccount();
        McpSettings::persist(['write_limit_per_hour' => 1]);

        $first = $this->customerArguments();
        $token = $this->tool('create_customer', $first)->json('result.structuredContent.confirmation_token');
        $this->tool('create_customer', ['confirmation_token' => $token] + $first)->assertJsonPath('result.structuredContent.status', 'created');

        $second = $this->customerArguments();
        $token = $this->tool('create_customer', $second)->json('result.structuredContent.confirmation_token');
        $limited = $this->tool('create_customer', ['confirmation_token' => $token] + $second);
        $limited->assertJsonPath('result.isError', true);
        $this->assertStringContainsString('حد الإضافة', $limited->json('result.content.0.text'));
    }
}
