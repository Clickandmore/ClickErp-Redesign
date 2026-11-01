<?php

namespace Tests\Feature;

use App\Jobs\SendContactNotification;
use App\Models\ingaz\Contact;
use App\Services\TeqnyatSmsService;
use App\Services\WhatsApp\WhatsAppMessagingService;
use App\Support\ContactNotificationSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContactNotificationTest extends TestCase
{
    private $originalQueue;
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'contact_notification_test');
            $app['config']->set('database.connections.contact_notification_test', [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ]);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $this->originalQueue = Queue::getFacadeRoot();
        Queue::fake();
        Http::fake();
        Schema::create('new_settings', function (Blueprint $table) {
            $table->id(); $table->string('key'); $table->text('value')->nullable(); $table->timestamps();
        });
        Schema::create('contacts', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('phone');
            $table->string('email')->nullable(); $table->string('subject');
            $table->text('message'); $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::purge('contact_notification_test');
        parent::tearDown();
    }

    private function contact(): Contact
    {
        return Contact::create(['name' => 'عميل الاختبار', 'phone' => '0500000000',
            'email' => 'client@example.test', 'subject' => 'شكوى تجريبية', 'message' => 'تفاصيل الشكوى']);
    }

    private function enable(string $channel = 'whatsapp'): void
    {
        ContactNotificationSettings::persist(['enabled' => 1, 'phone' => '966550000001', 'channel' => $channel]);
    }

    public function test_disabled_by_default_and_does_not_queue(): void
    {
        $this->assertFalse(ContactNotificationSettings::all()['enabled']);
        $this->contact();
        Queue::assertNothingPushed();
        Http::assertNothingSent();
    }

    public function test_company_phone_is_default_even_when_saved_notification_phone_is_blank(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id(); $table->string('phone');
        });
        DB::table('settings')->insert(['phone' => '920000001']);
        $this->assertSame('920000001', ContactNotificationSettings::all()['phone']);
        ContactNotificationSettings::persist(['enabled' => 0, 'phone' => '', 'channel' => 'whatsapp']);
        $this->assertSame('920000001', ContactNotificationSettings::all()['phone']);
        $this->enable();
        $this->assertSame('966550000001', ContactNotificationSettings::all()['phone']);
    }

    public function test_enabled_queues_once_on_creation_but_not_on_edit(): void
    {
        $this->enable();
        $contact = $this->contact();
        $contact->update(['subject' => 'تمت المراجعة']);
        Queue::assertPushed(SendContactNotification::class, 1);
        Queue::assertPushed(SendContactNotification::class, fn ($job) => $job->contactId === $contact->id && $job->afterCommit);
    }

    public function test_sends_whatsapp_to_company_instead_of_customer(): void
    {
        $this->enable();
        $contact = $this->contact();
        $whatsapp = $this->mock(WhatsAppMessagingService::class);
        $whatsapp->shouldReceive('sendText')->once()->withArgs(function ($phone, $message, $context) use ($contact) {
            return $phone === '966550000001' && str_contains($message, $contact->phone)
                && str_contains($message, $contact->name) && str_contains($message, $contact->message)
                && str_contains($message, $contact->subject) && str_contains($message, $contact->email)
                && $context['contact_id'] === $contact->id;
        })->andReturn(['success' => true]);
        $sms = $this->mock(TeqnyatSmsService::class);
        $sms->shouldNotReceive('sendMessage');
        (new SendContactNotification($contact->id))->handle($whatsapp, $sms);
    }

    public function test_sms_channel_uses_sms_only(): void
    {
        $this->enable('sms');
        $contact = $this->contact();
        $whatsapp = $this->mock(WhatsAppMessagingService::class);
        $whatsapp->shouldNotReceive('sendText');
        $sms = $this->mock(TeqnyatSmsService::class);
        $sms->shouldReceive('sendMessage')->once()->with('966550000001', \Mockery::type('string'), \Mockery::type('array'))->andReturn(['success' => true]);
        (new SendContactNotification($contact->id))->handle($whatsapp, $sms);
    }

    public function test_disabling_stops_already_queued_notifications(): void
    {
        $this->enable();
        $contact = $this->contact();
        ContactNotificationSettings::persist(['enabled' => 0, 'phone' => '', 'channel' => 'whatsapp']);
        $whatsapp = $this->mock(WhatsAppMessagingService::class);
        $whatsapp->shouldNotReceive('sendText');
        $sms = $this->mock(TeqnyatSmsService::class);
        $sms->shouldNotReceive('sendMessage');
        (new SendContactNotification($contact->id))->handle($whatsapp, $sms);
    }

    public function test_provider_failure_does_not_lose_contact(): void
    {
        $this->enable();
        $contact = $this->contact();
        $whatsapp = $this->mock(WhatsAppMessagingService::class);
        $whatsapp->shouldReceive('sendText')->once()->andThrow(new \RuntimeException('Unavailable'));
        (new SendContactNotification($contact->id))->handle($whatsapp, app(TeqnyatSmsService::class));
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    public function test_enabled_setting_requires_company_phone(): void
    {
        $this->expectException(ValidationException::class);
        ContactNotificationSettings::persist(['enabled' => 1, 'phone' => '', 'channel' => 'whatsapp']);
    }

    public function test_rejects_unsupported_channel(): void
    {
        $this->expectException(ValidationException::class);
        $this->enable('invalid');
    }

    public function test_settings_fields_render_inside_alerts_form_without_nested_form(): void
    {
        $this->enable('sms');
        $html = view('admin.ingaz.notifications.partials.contact-settings', [
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ])->render();
        $this->assertStringContainsString('966550000001', $html);
        $this->assertStringContainsString('alerts-card', $html);
        $this->assertStringContainsString('channel-pills', $html);
        $this->assertStringNotContainsString('<form', $html);
        $this->assertStringNotContainsString('name="section"', $html);
        $this->assertLessThan(strpos($html, 'type="checkbox"'), strpos($html, 'type="hidden"'));
    }

    public function test_transaction_rollback_does_not_send_notification(): void
    {
        $this->enable();
        config(['queue.default' => 'sync']);
        Queue::swap($this->originalQueue);
        $this->mock(WhatsAppMessagingService::class)->shouldNotReceive('sendText');
        DB::beginTransaction();
        $this->contact();
        DB::rollBack();
        $this->assertDatabaseCount('contacts', 0);
        Http::assertNothingSent();
    }

    public function test_notification_is_sent_only_after_transaction_commits(): void
    {
        $this->enable();
        config(['queue.default' => 'sync']);
        Queue::swap($this->originalQueue);
        $sent = false;
        $this->mock(WhatsAppMessagingService::class)->shouldReceive('sendText')->once()
            ->andReturnUsing(function () use (&$sent) {
                $sent = true;
                return ['success' => true];
            });
        DB::beginTransaction();
        $this->contact();
        $this->assertFalse($sent);
        DB::commit();
        $this->assertTrue($sent);
    }
}
