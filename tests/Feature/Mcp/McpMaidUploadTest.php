<?php

namespace Tests\Feature\Mcp;

use App\Http\Controllers\McpMaidUploadController;
use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\McpAccessToken;
use App\Models\McpAdminAccess;
use App\Models\McpAuditLog;
use App\Models\office\Office;
use App\Models\Permission;
use App\PermKeys;
use App\Services\ManagedFileStorage;
use App\Support\McpSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * رابط رفع صورة العاملة وفيديوها: يعطيه المساعد للموظف لأنه لا يستطيع إرسال الملفات عبر MCP.
 * الرابط موقّع ومؤقت ومرتبط بالموظف واتصاله والعاملة، وتُعاد الفحوص عند الفتح والرفع.
 */
class McpMaidUploadTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    private Admin $admin;

    private McpAccessToken $tokenRecord;

    private string $token;

    private Maid $maid;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('dev_superadmin_settings')->where('key', 'mcp_server')->delete();
        $this->enableBranchesModule(false);
        McpSettings::persist(['enabled' => true, 'rate_limit_per_minute' => 1000, 'write_enabled' => true, 'human_confirmation' => false]);

        $this->admin = Admin::factory()->create();
        McpAdminAccess::query()->create(['admin_id' => $this->admin->id, 'access_level' => 'write']);
        $this->grant(PermKeys::MAIDS_EDIT);
        [$this->tokenRecord, $this->token] = McpAccessToken::issue($this->admin, 'test', 1);

        $nationality = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $religion = DB::table('religions')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $this->maid = Maid::create([
            'name' => 'Upload test', 'passport_num' => 'UPL-' . uniqid(),
            'nationality_id' => $nationality, 'religion_id' => $religion,
            'age' => 30, 'height' => '', 'weight' => '', 'cv_file' => '', 'image' => '',
            'office_id' => Office::create(['nationality_id' => $nationality, 'status' => 'active'])->id,
            'status' => 'available', 'available' => true, 'is_show' => true, 'maid_accommodation_type' => 1,
        ]);
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

    private function tool(string $name, array $arguments = [])
    {
        return $this->withToken($this->token)->postJson('/mcp', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => $name, 'arguments' => $arguments],
        ])->assertOk();
    }

    private function link(): string
    {
        return $this->tool('maid_media_upload_link', ['maid_id' => $this->maid->id])
            ->json('result.structuredContent.upload_url');
    }

    private function fakeStorage(): void
    {
        $this->mock(ManagedFileStorage::class, function ($mock) {
            $mock->shouldReceive('uploadMaidFile')->andReturnUsing(fn (UploadedFile $file) => 'images/Maid/' . $file->getClientOriginalName());
            $mock->shouldReceive('delete')->andReturn(true);
        });
    }

    /** @test */
    public function the_link_opens_the_upload_page_and_saves_the_photo_and_video(): void
    {
        $this->fakeStorage();
        $url = $this->link();
        $this->assertNotEmpty($url);

        $this->get($url)->assertOk()->assertSee('رفع ملفات العاملة')->assertSee('Upload test');

        $this->post($url, [
            'image' => UploadedFile::fake()->image('face.jpg'),
            'video' => UploadedFile::fake()->create('intro.mp4', 500, 'video/mp4'),
        ])->assertRedirect()->assertSessionHas('success');

        $this->maid->refresh();
        $this->assertSame('images/Maid/face.jpg', $this->maid->image);
        $this->assertSame('images/Maid/intro.mp4', $this->maid->video);

        $audit = McpAuditLog::query()->where('admin_id', $this->admin->id)->where('method', 'upload')->firstOrFail();
        $this->assertSame((int) $this->tokenRecord->id, (int) $audit->token_id);
    }

    /** @test */
    public function an_empty_upload_changes_nothing_and_says_so(): void
    {
        $url = $this->link();

        $back = $this->post($url, [])->assertRedirect()->assertSessionHasErrors('media');
        // الرجوع لنفس الرابط الموقّع يبقى صالحاً.
        $this->get($back->headers->get('Location'))->assertOk();
    }

    /** @test */
    public function the_progress_bar_upload_gets_json_back(): void
    {
        $this->fakeStorage();
        $url = $this->link();

        $this->post($url, ['image' => UploadedFile::fake()->image('face.jpg')], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('message', 'تم رفع الملفات وحفظها في سيرة العاملة.');
        $this->assertSame('images/Maid/face.jpg', $this->maid->refresh()->image);

        $this->post($url, [], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonStructure(['errors' => ['media']]);
        $this->post($url, ['video' => UploadedFile::fake()->create('x.exe', 10)], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonStructure(['errors' => ['video']]);
    }

    /** @test */
    public function a_tampered_or_expired_link_is_refused(): void
    {
        $url = $this->link();

        $this->get(str_replace('/maids/' . $this->maid->id . '/', '/maids/' . ($this->maid->id + 1) . '/', $url))->assertForbidden();
        $this->get($url . '&admin=1')->assertForbidden();

        $this->travel(McpMaidUploadController::LINK_MINUTES + 1)->minutes();
        $this->get($url)->assertForbidden();
    }

    /** @test */
    public function revoking_the_connection_or_the_permission_kills_the_link(): void
    {
        $url = $this->link();
        $this->get($url)->assertOk();

        McpAdminAccess::query()->where('admin_id', $this->admin->id)->update(['access_level' => 'read']);
        $this->get($url)->assertForbidden();

        McpAdminAccess::query()->where('admin_id', $this->admin->id)->update(['access_level' => 'write']);
        $this->get($url)->assertOk();

        McpAccessToken::revokeAllFor($this->admin->id);
        $this->get($url)->assertForbidden();
    }

    /** @test */
    public function a_new_cv_comes_back_with_its_upload_link(): void
    {
        $this->grant(PermKeys::MAIDS_CREATE);
        $id = fn (string $table) => DB::table($table)->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $arguments = [
            'name' => 'Ana Cruz', 'passport_num' => 'P' . random_int(1000000, 9999999), 'gender' => 'female',
            'nationality_id' => $id('nationalities'), 'religion_id' => $id('religions'), 'type_id' => $id('job_types'),
            'job_ids' => [$id('job_titles')], 'office_id' => $id('recruitment_offices'),
        ];

        $token = $this->tool('create_maid_cv', $arguments)->json('result.structuredContent.confirmation_token');
        $created = $this->tool('create_maid_cv', $arguments + ['confirmation_token' => $token])->json('result.structuredContent');

        $this->assertStringContainsString('/mcp/maids/' . $created['maid']['id'] . '/upload', $created['media_upload']['upload_url']);
        $this->get($created['media_upload']['upload_url'])->assertOk()->assertSee('Ana Cruz');
    }

    /** @test */
    public function the_link_tool_needs_the_maid_edit_permission(): void
    {
        $this->admin->revokePermissionTo(PermKeys::MAIDS_EDIT);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $tools = collect($this->withToken($this->token)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->json('result.tools'))->pluck('name')->all();
        $this->assertNotContains('maid_media_upload_link', $tools);
        $this->tool('maid_media_upload_link', ['maid_id' => $this->maid->id])->assertJsonPath('result.isError', true);
    }
}
