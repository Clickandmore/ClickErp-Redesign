<?php

namespace Tests\Feature;

use App\Http\Actions\APPAPI\PhoneVerificationAction;
use App\Http\Actions\ingaz\UserAction;
use App\Models\ingaz\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Hash, Schema};
use Tests\HourlyTestCase;

class AppAuthModesTest extends HourlyTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.auth_modes'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''],
            'database.default'=>'auth_modes','jwt.secret'=>str_repeat('auth-mode-tests', 4),'activitylog.enabled'=>false]);
        Schema::create('settings', function (Blueprint $t) { $t->id(); $t->string('app_auth_mode')->default('password'); $t->string('external_auth_mode')->default('password'); $t->timestamps(); });
        Schema::create('settings_translations', function (Blueprint $t) { $t->id(); $t->unsignedInteger('setting_id'); $t->string('locale'); });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('phone'); $t->string('phone_code'); $t->string('password');
            $t->boolean('is_blocked')->default(false); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('user_firebase_tokens', function (Blueprint $t) { $t->id(); $t->unsignedInteger('user_id'); $t->string('phone_token'); });
        DB::table('settings')->insert(['app_auth_mode'=>'password']);
        DB::table('users')->insert(['id'=>1,'name'=>'Test User','phone'=>'512345678','phone_code'=>'966','password'=>Hash::make('Secret123')]);
    }

    public function test_unknown_login_phone_reports_registration_step_in_both_modes(): void
    {
        foreach (['password', 'otp'] as $mode) {
            DB::table('settings')->update(['app_auth_mode' => $mode]);
            $this->postJson('/api/app/auth/login', ['phone'=>'599999999', 'password'=>'Secret123'])
                ->assertStatus(422)
                ->assertJsonPath('status', false)
                ->assertJsonPath('data.registered', false)
                ->assertJsonPath('data.next_step', 'register')
                ->assertJsonPath('data.auth_mode', $mode)
                ->assertJsonPath('data.phone', '599999999')
                ->assertJsonPath('data.token', null);
            $response = $this->postJson('/api/app/auth/login', ['phone'=>'invalid']);
            $response->assertStatus(422)->assertJsonPath('data.next_step', null);
        }
    }

    public function test_password_login_still_requires_and_checks_password(): void
    {
        DB::table('settings')->update(['external_auth_mode' => 'otp']);
        $this->assertTrue(\App\Support\ExternalAuthSettings::usesOtpOnly());
        $this->assertFalse(\App\Support\AppAuthSettings::usesOtpOnly());
        $this->postJson('/api/app/auth/login', ['phone'=>'512345678'])->assertStatus(422);
        $this->postJson('/api/app/auth/login', ['phone'=>'512345678','password'=>'Incorrect'])->assertStatus(422);
        $response = $this->postJson('/api/app/auth/login', ['phone'=>'512345678','password'=>'Secret123'])->assertOk();
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_otp_login_requires_confirmation_before_issuing_token(): void
    {
        DB::table('settings')->update(['app_auth_mode'=>'otp']);
        $this->mock(PhoneVerificationAction::class, function ($mock) {
            $mock->shouldReceive('storeSendCode')->once()->withArgs(fn ($data, $request) => $data['phone_code']==='966')->andReturn(['code'=>'12345']);
            $mock->shouldReceive('confirmCode')->once()->andReturn(false);
        });
        $this->assertSame('otp', \App\Support\AppAuthSettings::mode());
        $this->assertSame('password', \App\Support\ExternalAuthSettings::mode());
        $response = $this->postJson('/api/app/auth/login', ['phone'=>'512345678'])->assertOk()->assertJsonPath('data.next_step','verify_otp');
        $this->assertNull($response->json('data.token'));
        $this->postJson('/api/app/auth/confirm-code', ['phone'=>'512345678','phone_code'=>'966','code'=>'wrong'])->assertStatus(422);
        $this->mock(PhoneVerificationAction::class, function ($mock) {
            $mock->shouldReceive('confirmCode')->once()->andReturn(true);
            $mock->shouldReceive('getUser')->once()->andReturn(User::findOrFail(1));
        });
        $response = $this->postJson('/api/app/auth/confirm-code', ['phone'=>'512345678','phone_code'=>'966','code'=>'12345'])->assertOk();
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_app_settings_api_reports_its_own_authentication_mode(): void
    {
        Schema::create('sliders', function (Blueprint $table) { $table->id(); });
        foreach (['password', 'otp'] as $mode) {
            DB::table('settings')->update([
                'app_auth_mode' => $mode,
                'external_auth_mode' => $mode === 'otp' ? 'password' : 'otp',
            ]);
            $this->getJson('/api/user/settings')->assertOk()
                ->assertJsonPath('data.app_auth_mode', $mode)
                ->assertJsonPath('data.uses_otp_authentication', $mode === 'otp');
        }
    }

    public function test_app_mode_defaults_to_password_before_migration(): void
    {
        Schema::table('settings', fn (Blueprint $table) => $table->dropColumn('app_auth_mode'));
        DB::table('settings')->update(['external_auth_mode' => 'otp']);
        $this->assertSame('password', \App\Support\AppAuthSettings::mode());
    }

    public function test_app_mode_can_be_saved_and_invalid_modes_are_rejected(): void
    {
        $rules = (new \App\Http\Requests\ingaz\SettingRequest())->rules();
        $this->assertTrue(validator(['app_auth_mode' => 'otp'], ['app_auth_mode' => $rules['app_auth_mode']])->passes());
        $this->assertTrue(validator(['app_auth_mode' => 'invalid'], ['app_auth_mode' => $rules['app_auth_mode']])->fails());
        $setting = \App\Models\ingaz\Setting::firstOrFail();
        $setting->update(['app_auth_mode' => 'otp']);
        $this->assertSame('otp', \App\Support\AppAuthSettings::mode());
        $this->assertSame('password', \App\Support\ExternalAuthSettings::mode());
        $this->assertFalse(\App\Support\WebsiteSettingsAccess::allowsPayload(['app_auth_mode' => 'otp']));
    }

    public function test_migration_defaults_app_to_password_without_changing_website_mode(): void
    {
        Schema::table('settings', fn (Blueprint $table) => $table->dropColumn('app_auth_mode'));
        DB::table('settings')->update(['external_auth_mode' => 'otp']);
        $migration = require database_path('migrations/2026_09_30_110000_add_app_auth_mode_to_settings_table.php');
        $migration->up();
        $this->assertSame('password', DB::table('settings')->value('app_auth_mode'));
        $this->assertSame('otp', DB::table('settings')->value('external_auth_mode'));
        $migration->down();
        $this->assertFalse(Schema::hasColumn('settings', 'app_auth_mode'));
    }

    public function test_confirming_phone_without_account_requires_registration_and_issues_no_token(): void
    {
        $this->mock(PhoneVerificationAction::class, function ($mock) {
            $mock->shouldReceive('confirmCode')->once()->andReturn(true);
            $mock->shouldReceive('getUser')->once()->andReturn(null);
        });

        $response = $this->postJson('/api/app/auth/confirm-code', [
            'phone' => '598765432', 'phone_code' => '966', 'code' => '12345',
        ])->assertOk()
            ->assertJsonPath('data.verified', true)
            ->assertJsonPath('data.registered', false)
            ->assertJsonPath('data.next_step', 'register');

        $this->assertNull($response->json('data.token'));
        $this->assertDatabaseMissing('users', ['phone' => '598765432']);
    }

    public function test_blocked_user_cannot_login_or_confirm_otp(): void
    {
        DB::table('settings')->update(['app_auth_mode'=>'otp']);
        DB::table('users')->update(['is_blocked'=>true]);
        $this->postJson('/api/app/auth/login', ['phone'=>'512345678'])->assertStatus(422);
        $this->postJson('/api/app/auth/confirm-code', ['phone'=>'512345678','phone_code'=>'966','code'=>'12345'])->assertForbidden();
    }

    public function test_registration_supports_both_modes(): void
    {
        $data = ['name'=>'New User','phone'=>'598765432','phone_code'=>'966','register_from'=>'app','software_type'=>'android'];
        $this->postJson('/api/app/auth/register', $data)->assertStatus(422);
        $this->mock(UserAction::class, function ($mock) {
            $mock->shouldReceive('store')->twice()->andReturnUsing(function ($data) {
                $this->assertSame('bcrypt', password_get_info($data['password'])['algoName']);
                $id = DB::table('users')->insertGetId(collect($data)->only(['name','phone','phone_code','password'])->all());
                return User::findOrFail($id);
            });
        });
        $this->mock(PhoneVerificationAction::class, fn ($mock) => $mock->shouldReceive('storeSendCode')->twice()->andReturn(['code'=>'12345']));
        $response = $this->postJson('/api/app/auth/register', $data + ['password'=>'Secret123','password_confirmation'=>'Secret123'])->assertOk();
        $this->assertNotEmpty($response->json('data.token'));
        DB::table('settings')->update(['app_auth_mode'=>'otp']);
        $data['phone'] = '598765433';
        $response = $this->postJson('/api/app/auth/register', $data)->assertOk()->assertJsonPath('data.next_step','verify_otp');
        $this->assertNull($response->json('data.token'));
        $this->assertNull($response->json('data.code'));
    }
}
