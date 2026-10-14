<?php

namespace Tests\Feature\SystemUpdates;

use App\Models\auth\Admin;
use App\Models\SystemUpdate;
use App\Models\SystemUpdateClient;
use App\Services\SystemUpdates\SystemUpdateInbox;
use App\Services\SystemUpdates\SystemUpdateSyncService;
use App\Support\SystemUpdatesSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * تحديثات النظام: النشر من Super Admin، الشريط وتأكيد الاطلاع للموظف،
 * والتزامن بين النسخ عبر /api/system-updates/feed.
 */
class SystemUpdatesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        SystemUpdatesSettings::flush();
        SystemUpdatesSettings::persist(['banner_enabled' => true, 'banner_days' => 30, 'sync_interval_minutes' => 1]);
    }

    protected function tearDown(): void
    {
        SystemUpdatesSettings::flush();

        parent::tearDown();
    }

    private function superAdmin(): self
    {
        return $this->withSession(['dev_superadmin_authenticated' => true]);
    }

    private function publishedUpdate(array $attributes = []): SystemUpdate
    {
        return SystemUpdate::create(array_merge([
            'title' => 'تحديث اختبار ' . Str::random(6),
            'category' => 'feature',
            'status' => SystemUpdate::STATUS_PUBLISHED,
            'is_shared' => true,
            'published_at' => now()->subMinute(),
            'banner_until' => now()->addDays(10),
        ], $attributes));
    }

    /** @test */
    public function super_admin_publishes_an_update_with_media(): void
    {
        Storage::fake('public');

        $this->superAdmin()
            ->post(route('dev-superadmin.system-updates.store'), [
                'title' => 'إشعار دائن من الفاتورة',
                'summary' => 'أنشئ إشعار الدائن مباشرة',
                'body' => "- الخطوة الأولى\n- **الخطوة الثانية**",
                'category' => 'feature',
                'status' => 'published',
                'link_url' => '/dashboard/invoices',
                'is_shared' => '1',
                'media' => [UploadedFile::fake()->image('step.png', 400, 300)],
                'embed_urls' => 'https://youtu.be/dQw4w9WgXcQ',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $update = SystemUpdate::query()->where('title', 'إشعار دائن من الفاتورة')->firstOrFail();

        $this->assertTrue($update->isPublished());
        $this->assertNotNull($update->published_at);
        $this->assertTrue($update->banner_until->greaterThan(now()->addDays(29)));
        $this->assertSame(['image', 'embed'], $update->media->pluck('type')->all());
        Storage::disk('public')->assertExists($update->media->first()->path);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $update->media->last()->embedUrl());
        $this->assertStringContainsString('<strong>الخطوة الثانية</strong>', $update->renderedBody());
    }

    /** @test */
    public function a_pdf_guide_can_be_attached_and_keeps_its_original_name(): void
    {
        Storage::fake('public');

        $this->superAdmin()
            ->post(route('dev-superadmin.system-updates.store'), [
                'title' => 'دليل الفواتير',
                'category' => 'feature',
                'status' => 'published',
                'media' => [UploadedFile::fake()->create('دليل الفواتير.pdf', 300, 'application/pdf')],
            ])
            ->assertSessionHasNoErrors();

        $media = SystemUpdate::query()->where('title', 'دليل الفواتير')->firstOrFail()->media->first();

        $this->assertSame('pdf', $media->type);
        $this->assertSame('دليل الفواتير.pdf', $media->caption);
        Storage::disk('public')->assertExists($media->path);
    }

    /** @test */
    public function local_media_and_feed_use_the_current_host_instead_of_a_copied_disk_url(): void
    {
        config(['filesystems.disks.public.url' => 'http:/clicadrec.com/storage']);
        $this->app['url']->forceRootUrl('https://clickerp.com.sa');
        $this->app['url']->forceScheme('https');
        try {
            $update = $this->publishedUpdate();
            $media = $update->media()->create(['type' => 'pdf', 'path' => 'system-updates/test/guide.pdf']);
            $expected = 'https://clickerp.com.sa/storage/system-updates/test/guide.pdf';
            $this->assertSame($expected, $media->publicUrl());
            $payload = app(\App\Services\SystemUpdates\SystemUpdateFeed::class)->payload(null);
            $item = collect($payload['updates'])->firstWhere('uuid', $update->uuid);
            $this->assertSame($expected, $item['media'][0]['url']);
        } finally {
            $this->app['url']->forceRootUrl(null);
            $this->app['url']->forceScheme(null);
        }
    }

    /** @test */
    public function remote_media_keeps_the_source_url_without_adding_the_client_host(): void
    {
        $url = 'https://clickerp.com.sa/storage/system-updates/test/guide.pdf';
        $media = new \App\Models\SystemUpdateMedia(['type' => 'pdf', 'url' => $url]);
        $this->assertSame($url, $media->publicUrl());
    }

    /** @test */
    public function saving_source_settings_pulls_updates_immediately(): void
    {
        Http::fake(['*' => Http::response([
            'feed_version' => 1,
            'server_time' => now()->toIso8601String(),
            'updates' => [['uuid' => (string) Str::uuid(), 'active' => true, 'title' => 'وصل فورًا']],
        ])]);

        $this->superAdmin()
            ->put(route('dev-superadmin.system-updates.settings'), [
                'banner_enabled' => '1',
                'banner_days' => 30,
                'sync_enabled' => '1',
                'source_url' => 'https://hub.example.test',
                'source_key' => 'su_secret',
                'sync_interval_minutes' => 1,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertTrue(SystemUpdate::query()->where('title', 'وصل فورًا')->exists());
        $this->assertSame('*/1 * * * *', SystemUpdatesSettings::syncCronExpression());
    }

    /** @test */
    public function employees_opening_the_dashboard_trigger_a_sync_after_the_response_when_due(): void
    {
        SystemUpdatesSettings::persist([
            'sync_enabled' => true,
            'source_url' => 'https://hub.example.test',
            'source_key' => 'su_secret',
            'banner_days' => 30,
            'sync_interval_minutes' => 5,
        ]);
        SystemUpdatesSettings::recordState(['last_attempt_at' => now()->subMinutes(10)->toDateTimeString()]);
        Http::fake(['*' => Http::response(['feed_version' => 1, 'server_time' => now()->toIso8601String(), 'updates' => []])]);

        SystemUpdateSyncService::syncAfterResponseIfDue();
        Http::assertNothingSent();

        $this->app->terminate();
        Http::assertSentCount(1);

        // داخل نفس المدة لا يُسجَّل سحب جديد
        $callbacks = new \ReflectionProperty($this->app, 'terminatingCallbacks');
        $callbacks->setAccessible(true);
        $before = count($callbacks->getValue($this->app));

        SystemUpdateSyncService::syncAfterResponseIfDue();

        $this->assertCount($before, $callbacks->getValue($this->app));
    }

    /** @test */
    public function test_connection_works_from_the_settings_form_which_spoofs_put(): void
    {
        Http::fake(['*' => Http::response([
            'feed_version' => 1,
            'server_time' => now()->toIso8601String(),
            'source' => ['name' => 'Hub'],
            'updates' => [],
        ])]);

        $this->superAdmin()
            ->put(route('dev-superadmin.system-updates.test-connection'), [
                'source_url' => 'https://hub.example.test',
                'source_key' => 'su_secret',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
    }

    /** @test */
    public function link_must_be_internal_or_full_url(): void
    {
        $this->superAdmin()
            ->post(route('dev-superadmin.system-updates.store'), [
                'title' => 'رابط غير صالح',
                'category' => 'feature',
                'status' => 'draft',
                'link_url' => 'javascript:alert(1)',
            ])
            ->assertSessionHasErrors('link_url');
    }

    /** @test */
    public function markdown_body_never_renders_raw_html(): void
    {
        $update = new SystemUpdate(['body' => "<script>alert(1)</script>\n\n[x](javascript:alert(1))"]);

        $html = $update->renderedBody();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    /** @test */
    public function acknowledging_removes_the_update_from_the_banner_but_keeps_it_on_the_page(): void
    {
        $admin = Admin::factory()->create();
        $update = $this->publishedUpdate();
        $inbox = app(SystemUpdateInbox::class);

        $this->assertTrue($inbox->banner($admin)['items']->contains('id', $update->id));

        $this->actingAs($admin, 'admin')
            ->postJson(route('system-updates.acknowledge', $update))
            ->assertOk()
            ->assertJsonStructure(['acknowledged_at', 'pending_count']);

        $this->assertFalse($inbox->banner($admin)['items']->contains('id', $update->id));
        $this->assertNotNull($update->reads()->where('admin_id', $admin->id)->value('acknowledged_at'));

        $this->actingAs($admin, 'admin')
            ->get(route('system-updates.index'))
            ->assertOk()
            ->assertSee($update->title)
            ->assertSee('اطلعت عليه في');
    }

    /** @test */
    public function the_header_shows_the_banner_until_the_update_is_acknowledged(): void
    {
        $admin = Admin::factory()->create();
        $update = $this->publishedUpdate(['category' => 'important']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.no-permissions'))
            ->assertOk()
            ->assertSee('id="cm-su-bar"', false)
            ->assertSee('cm-su-bar is-important', false)
            ->assertSee('<span class="ms-2">تحديثات النظام</span>', false)
            ->assertSee($update->title);

        app(SystemUpdateInbox::class)->acknowledgeAll($admin);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.no-permissions'))
            ->assertOk()
            ->assertDontSee('id="cm-su-bar"', false);
    }

    /** @test */
    public function the_banner_can_be_turned_off_from_super_admin(): void
    {
        $admin = Admin::factory()->create();
        $this->publishedUpdate();

        $this->superAdmin()
            ->put(route('dev-superadmin.system-updates.settings'), ['banner_days' => 30, 'sync_interval_minutes' => 1])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, app(SystemUpdateInbox::class)->banner($admin)['count']);
    }

    /** @test */
    public function viewing_the_page_records_seen_without_acknowledging(): void
    {
        $admin = Admin::factory()->create();
        $update = $this->publishedUpdate();

        $this->actingAs($admin, 'admin')->get(route('system-updates.index'))->assertOk();

        $read = $update->reads()->where('admin_id', $admin->id)->first();
        $this->assertNotNull($read?->seen_at);
        $this->assertNull($read->acknowledged_at);
    }

    /** @test */
    public function drafts_scheduled_and_expired_updates_stay_out_of_the_banner(): void
    {
        $admin = Admin::factory()->create();
        $draft = $this->publishedUpdate(['status' => SystemUpdate::STATUS_DRAFT]);
        $scheduled = $this->publishedUpdate(['published_at' => now()->addDay()]);
        $expired = $this->publishedUpdate(['banner_until' => now()->subMinute()]);

        $ids = app(SystemUpdateInbox::class)->banner($admin)['items']->pluck('id');

        $this->assertNotContains($draft->id, $ids);
        $this->assertNotContains($scheduled->id, $ids);
        $this->assertNotContains($expired->id, $ids);

        // المنتهي من الشريط ما زال ظاهرًا في الصفحة
        $this->actingAs($admin, 'admin')
            ->get(route('system-updates.index'))
            ->assertSee($expired->title)
            ->assertDontSee($draft->title)
            ->assertDontSee($scheduled->title);
    }

    /** @test */
    public function employees_cannot_acknowledge_an_unpublished_update(): void
    {
        $admin = Admin::factory()->create();
        $draft = $this->publishedUpdate(['status' => SystemUpdate::STATUS_DRAFT]);

        $this->actingAs($admin, 'admin')
            ->postJson(route('system-updates.acknowledge', $draft))
            ->assertNotFound();
    }

    /** @test */
    public function feed_requires_an_active_client_key(): void
    {
        $this->getJson(route('api.system-updates.feed'))->assertStatus(401);

        [$client, $token] = SystemUpdateClient::issue('عميل اختبار');
        $client->update(['is_active' => false]);

        $this->getJson(route('api.system-updates.feed'), ['X-System-Updates-Key' => $token])->assertStatus(401);
    }

    /** @test */
    public function feed_shares_only_published_shared_local_updates(): void
    {
        [, $token] = SystemUpdateClient::issue('عميل اختبار');
        $shared = $this->publishedUpdate();
        $private = $this->publishedUpdate(['is_shared' => false]);
        $draft = $this->publishedUpdate(['status' => SystemUpdate::STATUS_DRAFT]);
        $remote = $this->publishedUpdate(['source' => SystemUpdate::SOURCE_REMOTE]);

        $uuids = collect($this->getJson(route('api.system-updates.feed'), ['X-System-Updates-Key' => $token])
            ->assertOk()
            ->assertJsonStructure(['feed_version', 'server_time', 'updates'])
            ->json('updates'))
            ->pluck('uuid');

        $this->assertContains($shared->uuid, $uuids);
        $this->assertNotContains($private->uuid, $uuids);
        $this->assertNotContains($draft->uuid, $uuids);
        $this->assertNotContains($remote->uuid, $uuids);
    }

    /** @test */
    public function feed_reports_deleted_updates_as_inactive_after_since(): void
    {
        [, $token] = SystemUpdateClient::issue('عميل اختبار');
        $update = $this->publishedUpdate();
        $since = now()->subSecond()->toIso8601String();

        $update->delete();

        $item = collect($this->getJson(route('api.system-updates.feed', ['since' => $since]), ['X-System-Updates-Key' => $token])
            ->assertOk()
            ->json('updates'))
            ->firstWhere('uuid', $update->uuid);

        $this->assertSame(['uuid' => $update->uuid, 'active' => false], $item);
    }

    /** @test */
    public function sync_stores_remote_updates_and_removes_them_when_the_source_does(): void
    {
        SystemUpdatesSettings::persist([
            'banner_enabled' => true,
            'banner_days' => 30,
            'sync_enabled' => true,
            'source_url' => 'https://hub.example.test/',
            'source_key' => 'su_secret',
            'sync_interval_minutes' => 1,
        ]);
        $uuid = (string) Str::uuid();
        $local = $this->publishedUpdate();

        Http::fakeSequence()
            ->push([
                'feed_version' => 1,
                'server_time' => now()->toIso8601String(),
                'updates' => [
                    [
                        'uuid' => $uuid,
                        'active' => true,
                        'title' => 'تحديث من المصدر',
                        'category' => 'important',
                        'link_url' => 'javascript:alert(1)',
                        'published_at' => now()->subHour()->toIso8601String(),
                        'media' => [
                            ['type' => 'image', 'url' => 'https://hub.example.test/storage/a.png'],
                            ['type' => 'script', 'url' => 'https://evil.test/x.js'],
                        ],
                    ],
                    // تحديث محلي بنفس الـ uuid لا يُستبدل أبدًا
                    ['uuid' => $local->uuid, 'active' => true, 'title' => 'محاولة استبدال'],
                ],
            ])
            ->push([
                'feed_version' => 1,
                'server_time' => now()->toIso8601String(),
                'updates' => [['uuid' => $uuid, 'active' => false]],
            ]);

        $result = app(SystemUpdateSyncService::class)->sync();

        $remote = SystemUpdate::query()->where('uuid', $uuid)->firstOrFail();
        $this->assertSame(1, $result['saved']);
        $this->assertTrue($remote->isRemote());
        $this->assertFalse($remote->is_shared);
        $this->assertSame('hub.example.test', $remote->source_host);
        $this->assertNull($remote->link_url);
        $this->assertSame(['image'], $remote->media->pluck('type')->all());
        $this->assertNotSame('محاولة استبدال', $local->fresh()->title);

        Http::assertSent(fn ($request) => $request->url() === 'https://hub.example.test/api/system-updates/feed'
            && $request->hasHeader('X-System-Updates-Key', 'su_secret'));

        app(SystemUpdateSyncService::class)->sync();

        $this->assertNull(SystemUpdate::withTrashed()->where('uuid', $uuid)->first());
        $this->assertNull(SystemUpdatesSettings::state()['last_error']);
    }

    /** @test */
    public function failed_sync_records_the_error(): void
    {
        SystemUpdatesSettings::persist([
            'sync_enabled' => true,
            'source_url' => 'https://hub.example.test',
            'source_key' => 'wrong',
            'banner_days' => 30,
            'sync_interval_minutes' => 1,
        ]);
        Http::fake(['*' => Http::response(['message' => 'no'], 401)]);

        try {
            app(SystemUpdateSyncService::class)->sync();
            $this->fail('Expected the sync to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('مفتاح الربط', $exception->getMessage());
        }

        $this->assertStringContainsString('مفتاح الربط', (string) SystemUpdatesSettings::state()['last_error']);
    }

    /** @test */
    public function source_key_is_stored_encrypted_and_kept_when_left_blank(): void
    {
        SystemUpdatesSettings::persist(['source_url' => 'https://hub.example.test', 'source_key' => 'su_plain', 'banner_days' => 30, 'sync_interval_minutes' => 1]);
        SystemUpdatesSettings::persist(['source_url' => 'https://hub.example.test', 'source_key' => '', 'banner_days' => 30, 'sync_interval_minutes' => 1]);

        $raw = (string) \DB::table('dev_superadmin_settings')->where('key', 'system_updates')->value('value');

        $this->assertStringNotContainsString('su_plain', $raw);
        $this->assertSame('su_plain', SystemUpdatesSettings::sourceKey());
    }

    /** @test */
    public function remote_updates_cannot_be_edited_here(): void
    {
        $remote = $this->publishedUpdate(['source' => SystemUpdate::SOURCE_REMOTE, 'source_host' => 'hub.example.test']);

        $this->superAdmin()
            ->get(route('dev-superadmin.system-updates.edit', $remote))
            ->assertRedirect(route('dev-superadmin.system-updates.report', $remote));

        $this->superAdmin()
            ->put(route('dev-superadmin.system-updates.update', $remote), ['title' => 'x', 'category' => 'feature', 'status' => 'published'])
            ->assertForbidden();
    }

    /** @test */
    public function super_admin_pages_render(): void
    {
        $update = $this->publishedUpdate();
        SystemUpdateClient::issue('عميل العرض');

        $this->superAdmin()->get(route('dev-superadmin.product-updates'))
            ->assertOk()
            ->assertSee($update->title)
            ->assertSee('عميل العرض');
        $this->superAdmin()->get(route('dev-superadmin.system-updates.create'))->assertOk();
        $this->superAdmin()->get(route('dev-superadmin.system-updates.edit', $update))->assertOk();
        $this->superAdmin()->get(route('dev-superadmin.system-updates.report', $update))->assertOk();
    }

    /** @test */
    public function issuing_a_client_key_shows_the_token_once(): void
    {
        $this->superAdmin()
            ->post(route('dev-superadmin.system-updates.clients.store'), ['name' => 'مكتب الرافدين'])
            ->assertRedirect(route('dev-superadmin.product-updates'));

        $token = session('system_update_client_token');
        $this->assertStringStartsWith('su_', $token);
        $this->assertNotNull(SystemUpdateClient::findActiveByToken($token));
        $this->assertDatabaseMissing('system_update_clients', ['token_hash' => $token]);
    }
}
