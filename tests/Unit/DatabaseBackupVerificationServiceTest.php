<?php

namespace Tests\Unit;

use App\Services\DatabaseBackupVerificationService;
use App\Support\DevSuperAdminSecuritySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DatabaseBackupVerificationServiceTest extends TestCase
{
    private DatabaseBackupVerificationService $verification;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'dev_superadmin.security_file' => sys_get_temp_dir() . '/clickerp-missing-security-' . uniqid() . '.enc',
            'dev_superadmin.verification.email' => 'security@example.com',
            'dev_superadmin.verification.phone' => null,
            'dev_superadmin.verification.expires_minutes' => 10,
            'dev_superadmin.verification.max_attempts' => 5,
        ]);
        DevSuperAdminSecuritySettings::resetCache();
        $this->verification = app(DatabaseBackupVerificationService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DevSuperAdminSecuritySettings::resetCache();
        parent::tearDown();
    }

    public function test_code_is_bound_to_the_exact_action_and_backup_and_is_consumed_once(): void
    {
        $request = $this->requestWithSession();
        $key = 'clickerp/database-backups/clickerp-manual-20260823-120000-abcdefgh.sql.gz';
        $sentMessage = '';

        Mail::shouldReceive('raw')
            ->once()
            ->withArgs(function (string $message, callable $callback) use (&$sentMessage): bool {
                $sentMessage = $message;
                return true;
            });

        $challenge = $this->verification->issue($request, 'restore', $key, 'email');

        $this->assertSame('restore', $challenge['action']);
        $this->assertSame($key, $challenge['key']);
        $this->assertStringNotContainsString('security@example.com', $challenge['destination']);
        $this->assertArrayNotHasKey('code_hash', $challenge);
        preg_match('/:\s(\d{6})\. صالح/u', $sentMessage, $matches);
        $this->assertNotEmpty($matches[1] ?? null);
        $code = $matches[1];

        try {
            $this->verification->verifyAndConsume($request, 'delete', $key, $code);
            $this->fail('A restore code must not authorize deletion.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('verification_code', $exception->errors());
        }

        $this->verification->verifyAndConsume($request, 'restore', $key, $code);
        $this->assertNull($request->session()->get(DatabaseBackupVerificationService::SESSION_KEY));

        $this->expectException(ValidationException::class);
        $this->verification->verifyAndConsume($request, 'restore', $key, $code);
    }

    public function test_expired_code_is_rejected_and_removed(): void
    {
        Carbon::setTestNow('2026-08-23 12:00:00');
        $request = $this->requestWithSession();
        $key = 'clickerp/database-backups/clickerp-manual-20260823-120000-abcdefgh.sql.gz';

        Mail::shouldReceive('raw')->once();
        $this->verification->issue($request, 'delete', $key, 'email');
        Carbon::setTestNow('2026-08-23 12:11:00');

        $this->assertNull($this->verification->current($request));
        $this->assertNull($request->session()->get(DatabaseBackupVerificationService::SESSION_KEY));
    }

    public function test_destructive_actions_have_no_available_channel_without_a_trusted_destination(): void
    {
        config([
            'dev_superadmin.verification.email' => null,
            'dev_superadmin.verification.phone' => '',
        ]);
        DevSuperAdminSecuritySettings::resetCache();

        $this->assertSame([], $this->verification->availableChannels());

        $this->expectException(ValidationException::class);
        $this->verification->issue(
            $this->requestWithSession(),
            'restore',
            'clickerp/database-backups/clickerp-manual-20260823-120000-abcdefgh.sql.gz',
            'email'
        );
    }

    private function requestWithSession(): Request
    {
        $request = Request::create('/dev-super-admin/database-backups', 'POST', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $session = app('session')->driver();
        $session->start();
        $request->setLaravelSession($session);

        return $request;
    }
}
