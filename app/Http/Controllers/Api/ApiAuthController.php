<?php

namespace App\Http\Controllers\Api;

use App\Http\Actions\ingaz\PhoneVerificationAction;
use App\Http\Actions\ingaz\UserAction;
use App\Http\Actions\ingaz\UserAction as ObjAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\API\ChangePasswordRequest;
use App\Http\Requests\ingaz\API\ResendOtpRequest;
use App\Http\Requests\ingaz\auth\LoginRequest;
use App\Http\Requests\ingaz\auth\PasswordRequest;
use App\Http\Requests\ingaz\auth\RegisterRequest;
use App\Http\Requests\ingaz\auth\UpdateProfileRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\ingaz\UserFirebaseToken;
use App\Support\CommunicationSettings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use App\Models\ingaz\User;
use App\Support\ExternalAuthSettings;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiAuthController extends Controller
{
    public $postData = ['name', 'register_from', 'password', 'phone_code', 'phone'];
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'change_password']]);
    }


    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(
        RegisterRequest $request,
        UserAction $userAction,
        PhoneVerificationAction $phoneVerificationAction
    ) {
        $data = $request->validated();

        // Rate limit
        RateLimiter::hit('register:' . $data['phone'], 60);

        // Hash password
        $data['password'] = Hash::make(
            ExternalAuthSettings::usesOtpOnly() ? Str::random(40) : $data['password']
        );

        // Remove recaptcha if exists
        unset($data['g-recaptcha-response']);

        unset($data['password_confirmation']);

        // Create user
        $user = $userAction->store($data);

        // Send verification code
        $phoneVerificationAction->storeSendCode([
            'phone'      => $data['phone'],
            'phone_code' => $data['phone_code'],
        ], $request);

        // Encrypt phone (optional)
        $encryptedPhone = Crypt::encryptString($data['phone']);

        $data = [
            'phone'           => $data['phone'],
            'phone_code'      => $data['phone_code'],
            'encrypted_phone' => $encryptedPhone,
            'next_step'       => 'verify_otp',
            'sms_gateway_status' => CommunicationSettings::smsGatewayStatus(),
            'phone_verification_disabled' => CommunicationSettings::phoneVerificationIsDisabled(),
        ];
        return apiResponse(true,  __('ingaz.register_successfully'), $data, 201);

        // return response()->json([
        //     'status'  => true,
        //     'message' => __('ingaz.register_successfully'),
        //     'data'    => [
        //         'phone'           => $data['phone'],
        //         'encrypted_phone' => $encryptedPhone,
        //         'next_step'       => 'verify_otp'
        //     ]
        // ], 201);
    }

    public function update(UpdateProfileRequest $request, ObjAction $action)
    {
        //
    }


    protected function generateToken()
    {
        $user = auth()->user()->loadMissing('nationality');
        $token = JWTAuth::fromUser($user);

        return $token;
    }

    public function update_password(PasswordRequest $request, ObjAction $action)
    {
        $data = $action->updatePassword($request);

        if ($data) {
            return response()->json([
                'code' => 200,
                'data' => $data,
                'message' => 'تم بنجاح',
            ], 200);
        } else {
            return response()->json([
                'code' => 422,
                'data' => null,
                'message' => 'كلمة المرور القديمة غير صحيحة',
            ], 422);
        }
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request, PhoneVerificationAction $phoneVerificationAction)
    {
        $key = 'login:' . $request->phone;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'status'  => false,
                'message' => __('web.wait_before_retry'),
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $data = $request->validated();
        unset($data['g-recaptcha-response']);

        if (ExternalAuthSettings::usesOtpOnly()) {
            $user = User::query()->where('phone', $data['phone'])->first();
            if (!$user) {
                return apiResponse(false, __('api.not found'), null, 404);
            }

            $request->merge(['phone_code' => $user->phone_code ?: '+966']);
            $phoneVerificationAction->storeSendCode([
                'phone' => $user->phone,
                'phone_code' => $request->phone_code,
            ], $request);

            return apiResponse(true, __('ingaz.code_send_successfully'), [
                'phone' => $user->phone,
                'phone_code' => $request->phone_code,
                'next_step' => 'verify_otp',
                'sms_gateway_status' => CommunicationSettings::smsGatewayStatus(),
                'phone_verification_disabled' => CommunicationSettings::phoneVerificationIsDisabled(),
            ], 200);
        }

        if (Auth::guard('web')->attempt($data)) {
            $result =  [
                'success'      => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'user' => Auth::guard('web')->user(),
            ];
        } else {
            $result = [
                'success'      => false,
                'message' => 'بيانات الدخول غير صحيحة',
            ];
        }


        if (!$result['success']) {
            return response()->json([
                'status'  => false,
                'message' => __('ingaz.incorrect_password'),
            ], 422);
        }

        $user = $result['user'];

        // إنشاء توكن
        $token = JWTAuth::fromUser($user);

        // مسح محاولات الفشل بعد نجاح تسجيل الدخول
        RateLimiter::clear($key);

        $data = [
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'image' => $user->image,
                'image_url' => showFile($user->image),
            ],
            'token' => $token,
        ];

        return apiResponse(true,  __('ingaz.login_successfully'), $data, 200);

        // return response()->json([
        //     'status'  => true,
        //     'message' => __('ingaz.login_successfully'),
        //     'data'    => [
        //         'user' => [
        //             'id'    => $user->id,
        //             'name'  => $user->name,
        //             'phone' => $user->phone,
        //         ],
        //         'token' => $token,
        //     ]
        // ], 200);
        // return $this->respondWithToken($token);
    }

    protected function respondWithToken1($token, $user)
    {
        $user->token = $token;
        $users = UserResource::make($user);
        return jsonSuccess($users, __("auth.done successfully"));
    }

    protected function respondWithToken($token)
    {
        $user = auth()->user();
        $user->token = $token;
        $users = UserResource::make($user);
        return jsonSuccess($users, __("auth.done successfully"));
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $user = auth()->user();
        $users = UserResource::make($user);
        return jsonSuccess($users);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        UserFirebaseToken::where("user_id", Auth::user()->id)->delete();
        auth()->logout();
        return jsonSuccess(null, 'Successfully logged out');
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public function delete()
    {
        $user = auth()->user();
        $user->delete();
        return jsonSuccess(null, 'Successfully Deleted');
    }


    public function change_password(ChangePasswordRequest $request, UserAction $userAction)
    {
        $data = $request->validated();

        // لا تغيير لكلمة المرور إلا بعد تأكيد كود التحقق لنفس الرقم
        if (!\App\Support\PasswordResetGrant::consume($data['phone'])) {
            return apiResponse(false, app()->getLocale() === 'en' ? 'Verify the code sent to your phone first.' : 'يجب تأكيد كود التحقق المرسل إلى جوالك أولاً.', null, 403);
        }

        $user = $userAction->getWhereFirst(['phone' => $data['phone']]);
        $user->update(['password' => Hash::make($data['password'])]);

        // إبطال الجلسات القديمة
        $user->setRememberToken(\Illuminate\Support\Str::random(60));
        $user->save();

        return apiResponse(true,  __('ingaz.Password changed successfully'), [
            "phone" => $data['phone']
        ], 201);
    }

    public function resendOTP(ResendOtpRequest $request, PhoneVerificationAction $phoneVerificationAction)
    {
        $data = $request->validated();
        $phoneVerificationAction->storeSendCode($data, $request);
        return apiResponse(true,  __("web.resend_code_successfuly"), [
            "phone" => $data['phone']
        ], 201);
    }
}
