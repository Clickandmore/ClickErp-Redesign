<?php

namespace App\Http\Controllers\APPAPIControllers;

use App\Http\Actions\APPAPI\PhoneVerificationAction;
use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\APPAPI\Auth\ConfirmCodeRequest;
use App\Http\Requests\APPAPI\Auth\LoginRequest;
use App\Http\Requests\APPAPI\Auth\RegisterRequest;
use App\Http\Requests\APPAPI\Auth\ResetPasswordRequest;
use App\Http\Requests\APPAPI\Auth\SendCodeRequest;
use App\Http\Requests\APPAPI\Auth\UpdateProfileRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\ingaz\User;
use App\Models\ingaz\UserFirebaseToken;
use App\Support\CommunicationSettings;
use App\Support\AppAuthSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(LoginRequest $request, PhoneVerificationAction $phoneVerificationAction)
    {
        $key = 'app-login:' . $request->phone;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return apiResponse(false, __('web.wait_before_retry'), null, 429);
        }

        RateLimiter::hit($key, 60);

        $credentials = $request->validated();

        if (AppAuthSettings::usesOtpOnly()) {
            $user = User::where('phone', $credentials['phone'])->firstOrFail();
            $request->merge(['phone_code' => $user->phone_code ?: '966']);
            $phoneVerificationAction->storeSendCode([
                'phone' => $user->phone,
                'phone_code' => $request->phone_code,
            ], $request);

            return apiResponse(true, __('ingaz.code_send_successfully'), [
                'phone' => $user->phone,
                'phone_code' => $request->phone_code,
                'encrypted_phone' => Crypt::encryptString($user->phone),
                'auth_mode' => 'otp',
                'next_step' => 'verify_otp',
            ]);
        }

        if (!Auth::guard('web')->attempt([
            'phone' => $credentials['phone'],
            'password' => $credentials['password'],
        ])) {
            return apiResponse(false, __('ingaz.incorrect_password'), [
                'errors' => [
                    'phone' => [__('ingaz.incorrect_password')],
                ],
            ], 422);
        }

        /** @var User $user */
        $user = Auth::guard('web')->user();
        $token = JWTAuth::fromUser($user);

        RateLimiter::clear($key);

        return apiResponse(true, __('ingaz.login_successfully'), [
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function register(
        RegisterRequest $request,
        UserAction $userAction,
        PhoneVerificationAction $phoneVerificationAction
    ) {
        $data = $request->validated();

        RateLimiter::hit('app-register:' . $data['phone'], 60);

        $userData = $data;
        $userData['password'] = Hash::make(AppAuthSettings::usesOtpOnly() ? Str::random(40) : $data['password']);
        unset($userData['password_confirmation'], $userData['software_type']);

        /** @var User $user */
        $user = $userAction->store($userData);

        $verification = $phoneVerificationAction->storeSendCode([
            'phone' => $data['phone'],
            'phone_code' => $data['phone_code'],
        ], $request);

        if (AppAuthSettings::usesOtpOnly()) {
            return jsonSuccess([
                'id' => (int) $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'phone_code' => $user->phone_code,
                'encrypted_phone' => Crypt::encryptString($user->phone),
                'auth_mode' => 'otp',
                'next_step' => 'verify_otp',
            ]);
        }

        $token = JWTAuth::fromUser($user);

        return jsonSuccess($this->buildRegisterResponseData($user, $token, $verification['code'] ?? ''));
    }

    public function sendCode(SendCodeRequest $request, PhoneVerificationAction $phoneVerificationAction)
    {
        $data = $request->validated();

        $phoneVerificationAction->storeSendCode($data, $request);

        return apiResponse(true, __('ingaz.code_send_successfully'), [
            'phone' => $data['phone'],
            'phone_code' => $data['phone_code'],
            'encrypted_phone' => Crypt::encryptString($data['phone']),
            'next_step' => 'verify_otp',
        ]);
    }

    public function confirmCode(ConfirmCodeRequest $request, PhoneVerificationAction $phoneVerificationAction)
    {
        $data = $request->validated();
        $account = User::where('phone', $data['phone'])->first();
        if ($account && (int) $account->is_blocked === 1) {
            return apiResponse(false, app()->getLocale() === 'en' ? 'Your account has been blocked.' : 'تم حظر حسابك، يرجى التواصل مع الدعم.', null, 403);
        }

        if (blank($data['code'] ?? null)) {
            $phoneVerificationAction->storeSendCode([
                'phone' => $data['phone'],
                'phone_code' => $data['phone_code'],
            ], $request);

            return apiResponse(true, __('ingaz.code_send_successfully'), [
                'phone' => $data['phone'],
                'phone_code' => $data['phone_code'],
                'encrypted_phone' => Crypt::encryptString($data['phone']),
                'next_step' => 'verify_otp',
            ]);
        }

        if (!$phoneVerificationAction->confirmCode($request)) {
            return apiResponse(false, __('ingaz.code_is_wrong'), [
                'errors' => [
                    'code' => [__('ingaz.code_is_wrong')],
                ],
            ], 422);
        }

        $user = $phoneVerificationAction->getUser($request);

        if (!$user) {
            return apiResponse(true, app()->getLocale() === 'en'
                ? 'Phone verified. No matching account was found; register to continue.'
                : 'تم التحقق من رقم الجوال. لا يوجد حساب مطابق؛ أنشئ حسابًا للمتابعة.', [
                'phone' => $data['phone'],
                'phone_code' => $data['phone_code'],
                'verified' => true,
                'registered' => false,
                'next_step' => 'register',
            ]);
        }

        $token = JWTAuth::fromUser($user);

        return jsonSuccess($this->buildConfirmCodeResponseData($user, $token));
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $data = $request->validated();

        /** @var User|null $user */
        $user = User::where('phone', $data['phone'])->first();

        if (!$user) {
            $message = app()->getLocale() === 'en'
                ? 'Phone number is not registered.'
                : 'رقم الجوال غير مسجل في النظام.';

            return apiResponse(false, $message, [
                'errors' => [
                    'phone' => [$message],
                ],
            ], 422);
        }

        $user->forceFill([
            'password' => Hash::make($data['new_password']),
            'remember_token' => Str::random(60),
        ])->saveOrFail();

        $user = $user->fresh();

        if (!$user || !Hash::check($data['new_password'], $user->password)) {
            return apiResponse(false, __('api.error_updating_profile'), null, 500);
        }

        return apiResponse(true, __('ingaz.change_password_successfully'), [
            'user' => new UserResource($user),
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request)
    {
        /** @var User $user */
        $user = auth('api')->user();

        $data = $request->validated();
        $updateData = [];

        foreach (['name', 'phone', 'phone_code'] as $field) {
            if (array_key_exists($field, $data) && !is_null($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($data['new_password'])) {
            if (!Hash::check($data['old_password'], $user->password)) {
                return apiResponse(false, __('ingaz.incorrect_password'), [
                    'errors' => [
                        'old_password' => [__('ingaz.incorrect_password')],
                    ],
                ], 422);
            }

            $updateData['password'] = Hash::make($data['new_password']);
        }

        if (!empty($updateData)) {
            $user->update($updateData);
        }

        $user = $user->fresh();
        $token = JWTAuth::fromUser($user);

        return jsonSuccess($this->buildConfirmCodeResponseData($user, $token));
    }

    public function logout()
    {
        $user = auth('api')->user();

        UserFirebaseToken::where('user_id', $user->id)->delete();
        auth('api')->logout();

        return apiResponse(true, __('web.logout'));
    }

    public function delete()
    {
        /** @var User $user */
        $user = auth('api')->user();

        UserFirebaseToken::where('user_id', $user->id)->delete();
        $user->delete();

        return jsonSuccess(null, 'Successfully Deleted');
    }

    private function buildRegisterResponseData(User $user, string $token, $code): array
    {
        return [
            'code' => (string) $code,
            'firebase_token' => $this->resolveFirebaseToken($user),
            'id' => (int) $user->id,
            'name' => (string) ($user->name ?? ''),
            'phone' => (string) ($user->phone ?? ''),
            'token' => $token,
            'sms_gateway_status' => CommunicationSettings::smsGatewayStatus(),
        ];
    }

    private function buildConfirmCodeResponseData(User $user, string $token): array
    {
        return [
            'id' => (int) $user->id,
            'name' => (string) ($user->name ?? ''),
            'verification_code' => (string) ($user->verification_code ?? ''),
            'phone' => (string) ($user->phone ?? ''),
            'is_blocked' => $user->is_blocked === null ? '' : (string) $user->is_blocked,
            'token' => $token,
            'firebase_token' => $this->resolveFirebaseToken($user),
        ];
    }

    private function resolveFirebaseToken(User $user): ?string
    {
        return UserFirebaseToken::where('user_id', $user->id)
            ->latest('id')
            ->value('phone_token');
    }
}
