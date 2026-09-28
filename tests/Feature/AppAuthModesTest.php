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
        Schema::create('settings', function (Blueprint $t) { $t->id(); $t->string('external_auth_mode'); });
        Schema::create('settings_translations', function (Blueprint $t) { $t->id(); $t->unsignedInteger('setting_id'); $t->string('locale'); });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('phone'); $t->string('phone_code'); $t->string('password');
            $t->boolean('is_blocked')->default(false); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('user_firebase_tokens', function (Blueprint $t) { $t->id(); $t->unsignedInteger('user_id'); $t->string('phone_token'); });
        DB::table('settings')->insert(['external_auth_mode'=>'password']);
        DB::table('users')->insert(['id'=>1,'name'=>'Test User','phone'=>'512345678','phone_code'=>'966','password'=>Hash::make('Secret123')]);
    }

    public function test_password_login_still_requires_and_checks_password(): void
    {
        $this->postJson('/api/app/auth/login', ['phone'=>'512345678'])->assertStatus(422);
        $this->postJson('/api/app/auth/login', ['phone'=>'512345678','password'=>'Incorrect'])->assertStatus(422);
        $response = $this->postJson('/api/app/auth/login', ['phone'=>'512345678','password'=>'Secret123'])->assertOk();
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_otp_login_requires_confirmation_before_issuing_token(): void
    {
        DB::table('settings')->update(['external_auth_mode'=>'otp']);
        $this->mock(PhoneVerificationAction::class, function ($mock) {
            $mock->shouldReceive('storeSendCode')->once()->withArgs(fn ($data, $request) => $data['phone_code']==='966')->andReturn(['code'=>'12345']);
            $mock->shouldReceive('confirmCode')->once()->andReturn(false);
        });
        $this->assertSame('otp', \App\Support\ExternalAuthSettings::mode());
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

    public function test_blocked_user_cannot_login_or_confirm_otp(): void
    {
        DB::table('settings')->update(['external_auth_mode'=>'otp']);
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
        DB::table('settings')->update(['external_auth_mode'=>'otp']);
        $data['phone'] = '598765433';
        $response = $this->postJson('/api/app/auth/register', $data)->assertOk()->assertJsonPath('data.next_step','verify_otp');
        $this->assertNull($response->json('data.token'));
        $this->assertNull($response->json('data.code'));
    }
}
