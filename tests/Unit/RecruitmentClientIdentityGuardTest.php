<?php

namespace Tests\Unit;

use App\Models\ingaz\User;
use App\Services\ingaz\RecruitmentClientIdentityGuard;
use PHPUnit\Framework\TestCase;

class RecruitmentClientIdentityGuardTest extends TestCase
{
    private RecruitmentClientIdentityGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guard = new RecruitmentClientIdentityGuard();
    }

    public function test_it_normalizes_supported_saudi_mobile_formats(): void
    {
        $this->assertSame('512345678', $this->guard->normalizeSaudiPhone('0512345678'));
        $this->assertSame('512345678', $this->guard->normalizeSaudiPhone('+966512345678'));
        $this->assertSame('512345678', $this->guard->normalizeSaudiPhone('00966512345678'));
    }

    public function test_it_rejects_the_invalid_phone_that_caused_the_client_merge(): void
    {
        $this->assertNull($this->guard->normalizeSaudiPhone('9820698511'));
    }

    public function test_it_detects_a_different_identity_on_the_same_client_record(): void
    {
        $client = new User([
            'client_type' => 'person',
            'national_num' => '1091298347',
        ]);

        $this->assertTrue($this->guard->hasDocumentConflict($client, 'person', '1122747445'));
        $this->assertFalse($this->guard->hasDocumentConflict($client, 'person', '1091298347'));
    }
}
