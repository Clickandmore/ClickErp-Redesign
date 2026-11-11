<?php

namespace Tests\Feature\Security;

use App\Models\ingaz\User;
use App\Support\PasswordResetGrant;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * كانت واجهات "نسيت كلمة المرور" تغيّر كلمة مرور أي عميل بمعرفة رقم جواله فقط.
 * الآن لا يتغيّر شيء إلا بعد نجاح كود التحقق لنفس الرقم (PasswordResetGrant).
 */
class PasswordResetRequiresOtpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_api_change_password_is_rejected_without_verified_code(): void
    {
        $user = $this->customer();

        $this->postJson('/api/auth/change-password', $this->apiPayload($user))->assertStatus(403);

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_api_change_password_works_once_after_verified_code(): void
    {
        $user = $this->customer();
        PasswordResetGrant::grant($user->phone);

        $this->postJson('/api/auth/change-password', $this->apiPayload($user))->assertStatus(201);
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));

        // الإذن يُستهلك مرة واحدة
        $this->postJson('/api/auth/change-password', $this->apiPayload($user))->assertStatus(403);
    }

    public function test_app_reset_password_is_rejected_without_verified_code(): void
    {
        $user = $this->customer();

        $this->postJson('/api/app/auth/reset-password', [
            'phone' => $user->phone,
            'new_password' => 'new-password-123',
            'confirm_password' => 'new-password-123',
        ])->assertStatus(403);

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_app_reset_password_works_after_verified_code(): void
    {
        $user = $this->customer();
        PasswordResetGrant::grant('0' . $user->phone);

        $this->postJson('/api/app/auth/reset-password', [
            'phone' => $user->phone,
            'new_password' => 'new-password-123',
            'confirm_password' => 'new-password-123',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_site_password_update_by_id_requires_owner_login(): void
    {
        // الموقع العام قد يكون مغلقاً في قاعدة الاختبار فيحوّل كل طلب لصفحة الدخول
        \App\Models\ingaz\Setting::query()->update(['show_public_site' => 1, 'public_site_maintenance' => 0]);
        $user = $this->customer();

        $this->put(route('site.change-password.update', $user->id), $this->apiPayload($user))->assertForbidden();

        $other = $this->customer();
        $this->actingAs($other, 'web')
            ->put(route('site.change-password.update', $user->id), $this->apiPayload($user))
            ->assertForbidden();

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    private function customer(): User
    {
        return User::forceCreate([
            'name' => 'Reset Test',
            'phone' => '5' . random_int(10000000, 99999999),
            'phone_code' => '966',
            'password' => Hash::make('old-password'),
        ]);
    }

    private function apiPayload(User $user): array
    {
        return [
            'phone' => $user->phone,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
            'g-recaptcha-response' => 'test',
        ];
    }
}
