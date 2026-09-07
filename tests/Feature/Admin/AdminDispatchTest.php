<?php

namespace Tests\Feature\Admin;

use App\Jobs\DeliverAdminDispatch;
use App\Models\AdminNotification;
use App\Models\auth\Admin;
use App\Support\AdminDispatchPermissions as Permissions;
use App\Services\WhatsApp\WhatsAppMessagingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDispatchTest extends TestCase
{
    use DatabaseTransactions;

    private function actor(array $permissions): Admin
    {
        foreach (array_keys(Permissions::definitions()) as $name) {
            Permission::findOrCreate($name, 'admin');
        }
        $actor = Admin::factory()->create();
        $actor->givePermissionTo($permissions);
        $this->actingAs($actor, 'admin');
        Bus::fake();
        return $actor;
    }

    public function test_view_permission_does_not_allow_sending(): void
    {
        $this->actor([Permissions::VIEW]);
        $this->postJson(route('admin.dispatch.store'), [])->assertForbidden();
    }

    public function test_page_renders_and_history_is_private_to_sender(): void
    {
        $actor = $this->actor([Permissions::VIEW]);
        $other = Admin::factory()->create();
        AdminNotification::create([
            'admin_id' => $actor->id, 'created_by' => $other->id, 'title' => 'Private dispatch title',
            'message' => 'Hidden history', 'type' => 'admin_dispatch',
        ]);
        $response = $this->get(route('admin.dispatch.index'));
        $response->assertOk()->assertViewHas('history', fn ($history) => $history->total() === 0);
    }

    public function test_authorized_email_dispatch_saves_before_queueing(): void
    {
        $actor = $this->actor([Permissions::VIEW, Permissions::SEND, Permissions::AUDIENCES['users'], Permissions::CHANNELS['email']]);
        $recipient = Admin::factory()->create();
        $this->postJson(route('admin.dispatch.store'), [
            'title' => 'Test', 'message' => 'Message', 'audience' => 'users',
            'users' => [$recipient->id], 'channels' => ['email'],
        ])->assertRedirect(route('admin.dispatch.index'));
        $notification = AdminNotification::where('admin_id', $recipient->id)->where('created_by', $actor->id)->firstOrFail();
        $this->assertSame('pending', $notification->data['delivery']['email']);
        Bus::assertDispatched(DeliverAdminDispatch::class, fn ($job) => $job->notificationId === $notification->id && $job->afterCommit === true);
    }

    public function test_channels_and_all_recipients_are_authorized_on_server(): void
    {
        $this->actor([Permissions::VIEW, Permissions::SEND, Permissions::AUDIENCES['users']]);
        $recipient = Admin::factory()->create();
        $payload = ['title' => 'Test', 'message' => 'Message', 'audience' => 'users', 'users' => [$recipient->id]];
        $this->postJson(route('admin.dispatch.store'), $payload + ['channels' => ['email']])->assertForbidden();
        $payload['audience'] = 'all';
        $this->postJson(route('admin.dispatch.store'), $payload)->assertForbidden();
        $this->assertDatabaseMissing('admin_notifications', ['admin_id' => $recipient->id, 'type' => 'admin_dispatch']);
        Bus::assertNothingDispatched();
    }

    public function test_role_selection_deduplicates_and_excludes_suspended_admins(): void
    {
        $this->actor([Permissions::VIEW, Permissions::SEND, Permissions::AUDIENCES['roles']]);
        $role = Role::create(['name' => 'dispatch-'.uniqid(), 'guard_name' => 'admin']);
        $recipient = Admin::factory()->create(['role_id' => $role->id]);
        $recipient->assignRole($role);
        $suspended = Admin::factory()->create(['role_id' => $role->id, 'status' => 'suspended']);
        $this->postJson(route('admin.dispatch.store'), [
            'title' => 'Test', 'message' => 'Message', 'audience' => 'roles', 'roles' => [$role->id],
        ])->assertRedirect(route('admin.dispatch.index'));
        $this->assertSame(1, AdminNotification::where('admin_id', $recipient->id)->where('type', 'admin_dispatch')->count());
        $this->assertDatabaseMissing('admin_notifications', ['admin_id' => $suspended->id, 'type' => 'admin_dispatch']);
        Bus::assertNothingDispatched();
    }

    public function test_external_failure_preserves_database_message_and_other_channel_runs(): void
    {
        Mail::fake();
        $recipient = Admin::factory()->create(['email' => null]);
        $notification = AdminNotification::create([
            'admin_id' => $recipient->id, 'title' => 'Test', 'message' => 'Message', 'type' => 'admin_dispatch',
            'data' => ['delivery' => ['email' => 'pending', 'whatsapp' => 'pending']],
        ]);
        $whatsApp = \Mockery::mock(WhatsAppMessagingService::class);
        $whatsApp->shouldReceive('sendText')->atMost()->once()->andReturn(['success' => false]);
        (new DeliverAdminDispatch($notification->id))->handle($whatsApp);
        $this->assertSame(['email' => 'failed', 'whatsapp' => 'failed'], $notification->fresh()->data['delivery']);
        $this->assertSame('Message', $notification->fresh()->message);
        Mail::assertNothingSent();
    }

    public function test_email_is_only_sent_once_on_job_reexecution(): void
    {
        Mail::shouldReceive('raw')->once();
        $recipient = Admin::factory()->create(['email' => 'admin@example.test']);
        $notification = AdminNotification::create([
            'admin_id' => $recipient->id, 'title' => 'Test', 'message' => 'Message', 'type' => 'admin_dispatch',
            'data' => ['delivery' => ['email' => 'pending']],
        ]);
        $whatsApp = \Mockery::mock(WhatsAppMessagingService::class);
        $job = new DeliverAdminDispatch($notification->id);
        $job->handle($whatsApp);
        $job->handle($whatsApp);
        $this->assertSame('accepted', $notification->fresh()->data['delivery']['email']);
    }
}
