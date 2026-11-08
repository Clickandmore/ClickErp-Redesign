<?php

namespace Tests\Unit;

use App\Jobs\SendMaidCvShare;
use App\Models\MaidCvShare;
use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Services\ingaz\MaidCvPdfGenerator;
use App\Services\ingaz\MaidCvShareService;
use App\Services\ingaz\MaidCvShareStatusService;
use App\Services\WhatsApp\WhatsAppDocumentService;
use App\Services\WhatsApp\WhatsAppMessagingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class MaidCvShareServiceTest extends TestCase
{
    private $messaging;
    private $generator;
    private MaidCvShareService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/2026_11_08_000001_create_maid_cv_shares_tables.php'))->up();
        Storage::fake('local');
        $this->messaging = Mockery::mock(WhatsAppMessagingService::class);
        $this->messaging->shouldReceive('driver')->andReturn('meta')->byDefault();
        $this->messaging->shouldReceive('isConfigured')->andReturn(true)->byDefault();
        $this->generator = Mockery::mock(MaidCvPdfGenerator::class);
        $this->service = new MaidCvShareService($this->messaging, $this->generator);
    }

    private function share(string $status = 'queued'): MaidCvShare
    {
        $share = MaidCvShare::create(['admin_id' => 7, 'client_id' => 9, 'admin_name' => 'Employee',
            'client_name' => 'Client', 'phone' => '966501234567', 'provider' => 'meta', 'message' => 'CV details',
            'status' => $status, 'confirmed_at' => now()]);
        $share->messages()->create([]);
        foreach ([1, 2] as $id) {
            Storage::disk('local')->put("maid-cv-shares/$id.pdf", '%PDF-test');
            $share->messages()->create(['maid_id' => $id, 'file_path' => "maid-cv-shares/$id.pdf", 'filename' => "$id.pdf"]);
        }
        return $share;
    }

    public function test_rental_type_comes_from_job_type_and_omits_the_workers_salary(): void
    {
        $maid = new Maid();
        $maid->setRawAttributes(['id' => 1, 'name' => 'Rental worker', 'cv_code' => 'R-001', 'age' => 25,
            'salary' => 1800, 'request_type' => 'custom_spec']);
        $type = new \App\Models\ingaz\JobType();
        $type->setRawAttributes(['id' => 2]);
        $maid->setRelation('jobTypes', collect([$type]));
        $maid->setRelation('nationality', null);
        $snapshot = $this->service->snapshot($maid);
        $this->assertSame('rental', $snapshot['cv_type']);
        $this->assertNull($snapshot['salary']);
        $message = $this->service->message([$snapshot]);
        $this->assertStringContainsString('نوع السيرة: إيجار', $message);
        $this->assertStringNotContainsString('الراتب', $message);
        $this->assertStringNotContainsString('1,800', $message);
    }

    public function test_it_sends_text_first_then_each_file_and_does_not_repeat_on_duplicate_job(): void
    {
        $share = $this->share();
        $this->messaging->shouldReceive('sendText')->once()->with('966501234567', 'CV details', Mockery::type('array'))->andReturn(['success' => true, 'message_id' => 'text-1']);
        $documents = Mockery::mock(WhatsAppDocumentService::class);
        $documents->shouldReceive('send')->twice()->andReturnUsing(function ($to, $url, $filename) use ($share) {
            $this->assertSame('accepted', $share->messages()->first()->status);
            $this->assertStringContainsString('signature=', $url);
            return ['success' => true, 'message_id' => $filename];
        });
        $this->app->instance(WhatsAppDocumentService::class, $documents);
        $this->service->send($share);
        $this->service->send($share->fresh());
        $this->assertSame('accepted', $share->fresh()->status);
        $this->assertSame([1, 1, 1], $share->messages()->pluck('attempts')->all());
        $this->assertSame([null, null, null], $share->messages()->pluck('delivered_at')->all());
    }

    public function test_rejected_text_prevents_attachments(): void
    {
        $share = $this->share();
        $this->messaging->shouldReceive('sendText')->once()->andReturn(['success' => false, 'status_code' => 400]);
        $this->service->send($share);
        $this->assertSame('failed', $share->fresh()->status);
        $this->assertSame([1, 0, 0], $share->messages()->pluck('attempts')->all());
    }

    public function test_retry_only_sends_failed_messages_and_preserves_accepted_ones(): void
    {
        $share = $this->share();
        $share->messages()->first()->update(['status' => 'accepted', 'attempts' => 1]);
        $share->messages()->where('maid_id', 1)->update(['status' => 'accepted', 'attempts' => 1]);
        $documents = Mockery::mock(WhatsAppDocumentService::class);
        $documents->shouldReceive('send')->once()->with('966501234567', Mockery::type('string'), '2.pdf', Mockery::type('array'))->andReturn(['success' => true]);
        $this->app->instance(WhatsAppDocumentService::class, $documents);
        $this->service->send($share);
        $this->assertSame('accepted', $share->fresh()->status);
        $this->assertSame([1, 1, 1], $share->messages()->pluck('attempts')->all());
    }

    public function test_timeout_is_marked_unknown_and_is_not_retried(): void
    {
        $share = $this->share();
        $this->messaging->shouldReceive('sendText')->once()->andThrow(new \RuntimeException('timeout'));
        $this->service->send($share);
        $this->assertSame('needs_review', $share->fresh()->status);
        $this->assertSame('unknown', $share->messages()->first()->status);
        $this->service->send($share->fresh());
    }

    public function test_provider_change_stops_send(): void
    {
        $share = $this->share();
        $share->update(['provider' => 'ultramsg']);
        $this->service->send($share);
        $this->assertSame('failed', $share->fresh()->status);
        $this->assertSame([0, 0, 0], $share->messages()->pluck('attempts')->all());
    }

    public function test_job_failure_marks_only_in_flight_messages_unknown(): void
    {
        $share = $this->share('processing');
        $share->messages()->first()->update(['status' => 'accepted']);
        $share->messages()->where('maid_id', 1)->update(['status' => 'sending']);
        (new SendMaidCvShare($share->id))->failed(new \RuntimeException('Worker terminated'));
        $this->assertSame(['accepted', 'unknown', 'failed'], $share->messages()->pluck('status')->all());
        $this->assertSame('needs_review', $share->fresh()->status);
    }

    public function test_delivery_callbacks_are_monotonic_and_do_not_confuse_providers(): void
    {
        $share = $this->share('accepted');
        $share->messages()->update(['status' => 'accepted']);
        $text = $share->messages()->first();
        $text->update(['provider_message_id' => 'meta-1']);
        MaidCvShareStatusService::update('meta-1', 'read', ['timestamp' => now()->timestamp]);
        MaidCvShareStatusService::update('meta-1', 'sent', []);
        MaidCvShareStatusService::update('meta-1', 'failed', []);
        $this->assertSame('read', $text->fresh()->status);
        $this->assertNotNull($text->fresh()->delivered_at);
        $this->assertNotNull($text->fresh()->read_at);
        $share->update(['provider' => 'chatwoot']);
        MaidCvShareStatusService::update('meta-1', 'delivered', []);
        $this->assertSame('read', $text->fresh()->status);
    }

    public function test_prepare_copies_all_files_and_builds_the_arabic_message_without_sending(): void
    {
        $maid = new Maid();
        $maid->setRawAttributes(['id' => 1, 'name' => 'PAULINE CHARO', 'cv_code' => 'KE-00123', 'age' => 37, 'salary' => 1000]);
        $maid->setRelation('jobTypes', collect());
        $maid->setRelation('nationality', null);
        $this->generator->shouldReceive('renderPdfContents')->once()->andReturn('%PDF-snapshot');
        $client = new User();
        $client->setRawAttributes(['id' => 9, 'name' => 'Client', 'branch_id' => 2]);
        $share = $this->service->prepare((object) ['id' => 7, 'name' => 'Employee'], $client, collect([$maid]), '0501234567', null);
        $this->assertSame('draft', $share->status);
        $this->assertNull($share->confirmed_at);
        $this->assertSame('966501234567', $share->phone);
        $this->assertStringContainsString('الراتب: 1,000 ريال', $share->message);
        $this->assertStringContainsString('KE-00123', $share->message);
        $this->assertStringContainsString('نوع السيرة: استقدام', $share->message);
        $file = $share->messages()->whereNotNull('maid_id')->first();
        $this->assertSame('PAULINE CHARO', $file->snapshot['name']);
        $this->assertSame('%PDF-snapshot', Storage::disk('local')->get($file->file_path));
    }

    public function test_a_generation_failure_rolls_back_the_whole_share_and_removes_copies(): void
    {
        $maid = new Maid();
        $maid->setRawAttributes(['id' => 1, 'name' => 'First']);
        $maid->setRelation('jobTypes', collect());
        $maid->setRelation('nationality', null);
        $second = clone $maid;
        $second->id = 2;
        $this->generator->shouldReceive('renderPdfContents')->once()->with($maid)->andReturn('%PDF-test');
        $this->generator->shouldReceive('renderPdfContents')->once()->with($second)->andThrow(new \RuntimeException('generation failed'));
        $client = new User();
        $client->setRawAttributes(['id' => 9, 'name' => 'Client']);
        try {
            $this->service->prepare((object) ['id' => 7, 'name' => 'Employee'], $client, collect([$maid, $second]), '0501234567', null);
            $this->fail('Expected a preparation error');
        } catch (ValidationException $e) {
            $this->assertSame(0, MaidCvShare::count());
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }
}
