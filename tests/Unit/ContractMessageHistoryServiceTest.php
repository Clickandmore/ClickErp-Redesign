<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\OutboundMessageLog;
use App\Models\RentalContract;
use App\Services\ContractMessageHistoryService;
use App\Support\OutboundMessageRecorder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractMessageHistoryServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        (require database_path('migrations/2026_10_12_200000_create_outbound_message_logs_table.php'))->up();
        Schema::create('admins', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->softDeletes();
        });
    }

    private function contract(string $type, int $id = 42)
    {
        $contract = $type === 'rental' ? new RentalContract() : new Contract();
        $contract->setRawAttributes(['id' => $id]);
        return $contract;
    }

    private function log(array $context, string $source = 'configured_alert', array $attributes = []): OutboundMessageLog
    {
        return OutboundMessageLog::create($attributes + ['context' => $context, 'source' => $source,
            'channel' => 'whatsapp', 'recipient' => '966501234567', 'message' => 'Contract message', 'status' => 'accepted']);
    }

    public function test_same_numeric_contract_id_does_not_mix_recruitment_and_rental_messages(): void
    {
        $recruitment = $this->log(['contract_id' => 42, 'contract_type' => 'recruitment']);
        $rental = $this->log(['contract_id' => 42, 'contract_type' => 'rental']);
        $this->log(['contract_id' => 43, 'contract_type' => 'rental']);
        $this->log(['user_id' => 9]);
        $service = app(ContractMessageHistoryService::class);
        $this->assertSame([$recruitment->id], $service->forContract($this->contract('recruitment'))->pluck('id')->all());
        $this->assertSame([$rental->id], $service->forContract($this->contract('rental'))->pluck('id')->all());
    }

    public function test_known_legacy_sources_are_matched_but_ambiguous_links_are_excluded(): void
    {
        $recruitment = $this->log(['contract_id' => '42'], 'recruitment_stage');
        $rental = $this->log(['contract_id' => 42], 'legacy:rental_contract_expiry_reminders');
        $this->log(['contract_id' => 42], 'unknown_source');
        $this->log(['contract_id' => 42, 'contract_type' => 'recruitment'], 'rental_contract_expiry_reminder');
        $service = app(ContractMessageHistoryService::class);
        $this->assertSame([$rental->id], $service->forContract($this->contract('rental'))->pluck('id')->all());
        $this->assertContains($recruitment->id, $service->forContract($this->contract('recruitment'))->pluck('id')->all());
        $this->assertSame(2, $service->forContract($this->contract('recruitment'))->total());
    }

    public function test_newest_attempts_come_first_and_verification_content_is_excluded(): void
    {
        $context = ['contract_id' => 42, 'contract_type' => 'rental'];
        $old = $this->log($context, 'configured_alert', ['created_at' => now()->subDays(2)]);
        $failed = $this->log($context, 'configured_alert', ['status' => 'failed']);
        $this->log($context, 'configured_alert', ['purpose' => 'verification']);
        $page = app(ContractMessageHistoryService::class)->forContract($this->contract('rental'));
        $this->assertSame([$failed->id, $old->id], $page->pluck('id')->all());
        $this->assertStringContainsString('contract_messages_page=', $page->url(2));
        $this->assertStringContainsString('#contract-messages', $page->url(2));
    }

    public function test_recorder_retains_contract_identity_and_restores_context_after_an_exception(): void
    {
        try {
            OutboundMessageRecorder::withContext(['contract_id' => 42, 'contract_type' => 'rental'], function () {
                $log = OutboundMessageRecorder::begin('email', 'client@example.test', 'Contract message', 'test');
                $this->assertSame('rental', $log->context['contract_type']);
                $this->assertSame(42, $log->context['contract_id']);
                throw new \RuntimeException('Test exception');
            });
        } catch (\RuntimeException $e) {
            $log = OutboundMessageRecorder::begin('email', 'client@example.test', 'Other message', 'test');
            $this->assertArrayNotHasKey('contract_id', $log->context);
        }
    }

    public function test_legacy_contract_senders_gain_explicit_type_when_recorded(): void
    {
        $log = OutboundMessageRecorder::begin('email', 'client@example.test', 'Rental reminder', 'test',
            ['source' => 'rental_contract_expiry_reminder', 'contract_id' => 42]);
        $this->assertSame('rental', $log->context['contract_type']);
    }

    public function test_email_transport_keeps_contract_context_without_creating_duplicate_audit_rows(): void
    {
        OutboundMessageRecorder::withContext(ContractMessageHistoryService::context($this->contract('recruitment')),
            fn () => Mail::mailer('array')->raw('Contract update', fn ($mail) => $mail->to('client@example.test')->subject('Contract')));
        $page = app(ContractMessageHistoryService::class)->forContract($this->contract('recruitment'));
        $this->assertSame(1, $page->total());
        $this->assertSame('email', $page->first()->channel);
        $this->assertSame('skipped', $page->first()->status);
    }
}
