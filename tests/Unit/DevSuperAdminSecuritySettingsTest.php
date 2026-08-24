<?php

namespace Tests\Unit;

use App\Support\DevSuperAdminSecuritySettings;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DevSuperAdminSecuritySettingsTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = sys_get_temp_dir() . '/clickerp-superadmin-security-' . uniqid() . '.enc';
        config([
            'dev_superadmin.security_file' => $this->path,
            'dev_superadmin.password' => 'LegacyPassword!123',
            'dev_superadmin.verification.email' => 'legacy@example.com',
            'dev_superadmin.verification.phone' => '',
        ]);
        DevSuperAdminSecuritySettings::resetCache();
    }

    protected function tearDown(): void
    {
        if (File::exists($this->path)) {
            File::delete($this->path);
        }
        DevSuperAdminSecuritySettings::resetCache();

        parent::tearDown();
    }

    public function test_it_uses_environment_values_only_until_the_encrypted_file_is_saved(): void
    {
        $this->assertTrue(DevSuperAdminSecuritySettings::matchesPassword('LegacyPassword!123'));
        $this->assertSame('legacy@example.com', DevSuperAdminSecuritySettings::all()['email']);

        DevSuperAdminSecuritySettings::persist([
            'email' => 'security@example.com',
            'phone' => '0500000000',
            'password' => 'NewStrong!Password123',
        ]);

        $encryptedContents = File::get($this->path);
        $this->assertStringNotContainsString('security@example.com', $encryptedContents);
        $this->assertStringNotContainsString('NewStrong!Password123', $encryptedContents);

        DevSuperAdminSecuritySettings::resetCache();
        $this->assertSame('security@example.com', DevSuperAdminSecuritySettings::all()['email']);
        $this->assertSame('0500000000', DevSuperAdminSecuritySettings::all()['phone']);
        $this->assertTrue(DevSuperAdminSecuritySettings::matchesPassword('NewStrong!Password123'));
        $this->assertFalse(DevSuperAdminSecuritySettings::matchesPassword('LegacyPassword!123'));
    }

    public function test_updating_contacts_without_a_password_keeps_the_current_password_hash(): void
    {
        DevSuperAdminSecuritySettings::persist([
            'email' => 'first@example.com',
            'phone' => '',
            'password' => 'NewStrong!Password123',
        ]);
        DevSuperAdminSecuritySettings::persist([
            'email' => '',
            'phone' => '0511111111',
            'password' => '',
        ]);

        DevSuperAdminSecuritySettings::resetCache();
        $this->assertTrue(DevSuperAdminSecuritySettings::matchesPassword('NewStrong!Password123'));
        $this->assertSame('', DevSuperAdminSecuritySettings::all()['email']);
        $this->assertSame('0511111111', DevSuperAdminSecuritySettings::all()['phone']);
    }
}
