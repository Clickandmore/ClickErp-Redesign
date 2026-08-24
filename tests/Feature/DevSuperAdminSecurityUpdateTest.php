<?php

namespace Tests\Feature;

use App\Support\DevSuperAdminSecuritySettings;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DevSuperAdminSecurityUpdateTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = sys_get_temp_dir() . '/clickerp-superadmin-security-feature-' . uniqid() . '.enc';
        config([
            'dev_superadmin.security_file' => $this->path,
            'dev_superadmin.password' => 'CurrentPassword!123',
            'dev_superadmin.verification.email' => '',
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

    public function test_contacts_can_be_managed_from_the_backup_page_with_the_current_password(): void
    {
        $response = $this->withSession(['dev_superadmin_authenticated' => true])
            ->from(route('dev-superadmin.database-backups'))
            ->put(route('dev-superadmin.database-backups.security'), [
                'current_password' => 'CurrentPassword!123',
                'verification_email' => 'security@example.com',
                'verification_phone' => '0500000000',
            ]);

        $response->assertRedirect(route('dev-superadmin.database-backups'));
        $response->assertSessionHasNoErrors();
        DevSuperAdminSecuritySettings::resetCache();
        $this->assertSame('security@example.com', DevSuperAdminSecuritySettings::all()['email']);
        $this->assertSame('0500000000', DevSuperAdminSecuritySettings::all()['phone']);
    }

    public function test_wrong_current_password_cannot_change_security_settings(): void
    {
        $response = $this->withSession(['dev_superadmin_authenticated' => true])
            ->from(route('dev-superadmin.database-backups'))
            ->put(route('dev-superadmin.database-backups.security'), [
                'current_password' => 'WrongPassword!123',
                'verification_email' => 'attacker@example.com',
            ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertFalse(File::exists($this->path));
    }

    public function test_password_change_logs_out_the_current_session_and_uses_the_new_password(): void
    {
        $response = $this->withSession(['dev_superadmin_authenticated' => true])
            ->put(route('dev-superadmin.database-backups.security'), [
                'current_password' => 'CurrentPassword!123',
                'verification_email' => 'security@example.com',
                'password' => 'NewPassword!Secure123',
                'password_confirmation' => 'NewPassword!Secure123',
            ]);

        $response->assertRedirect(route('superadmin.login'));
        $response->assertSessionMissing('dev_superadmin_authenticated');
        DevSuperAdminSecuritySettings::resetCache();
        $this->assertTrue(DevSuperAdminSecuritySettings::matchesPassword('NewPassword!Secure123'));
        $this->assertFalse(DevSuperAdminSecuritySettings::matchesPassword('CurrentPassword!123'));
    }
}
