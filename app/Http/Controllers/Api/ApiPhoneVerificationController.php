<?php

namespace App\Http\Controllers\Api;

use App\Http\Actions\ingaz\PhoneVerificationAction as objAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\auth\ConfirmCodeRequest;
use App\Http\Requests\ingaz\auth\ResetPasswordRequest;
use App\Http\Requests\ingaz\auth\SendCodeRequest;
use App\Http\Requests\ingaz\PhoneVerificationRequest;
use Illuminate\Http\Request;
use App\Http\Actions\ingaz\PhoneVerificationAction;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Crypt;
use App\Http\Actions\ingaz\UserAction;

class ApiPhoneVerificationController extends Controller
{
    public $postData = ['phone_code', 'phone'];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SendCodeRequest $request, PhoneVerificationAction $phoneVerificationAction)
    {

    }

    public function resend_code(SendCodeRequest $request, PhoneVerificationAction $phoneVerificationAction)
    {
        $data = $request->validated();
        $phoneVerificationAction->storeSendCode($data, $request);
        return jsonSuccess(null, __("web.resend_code_successfuly"), 200);
    }

    public function confirm_code(ConfirmCodeRequest $request, PhoneVerificationAction $phoneVerificationAction)
    {
        $data = $request->validated();

        // تحقق من الكود
        $isValid = $phoneVerificationAction->confirmCode($request);

        if (!$isValid) {
            return response()->json([
                'status'  => false,
                'message' => __('ingaz.code_is_wrong'),
            ], 422);
        }

        // جلب المستخدم
        $user = $phoneVerificationAction->getUser($request);

        // لو تستخدم Sanctum (مفضل في API)
        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => __('api.not found'),
            ], 404);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status'  => true,
            'message' => __('ingaz.login_successfully'),
            'data'    => [
                'user'  => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'phone' => $user->phone,
                    // 'email' => $user->email,
                ],
                'token' => $token,
            ]
        ], 200);
    }

    public function reset_password_otp(ConfirmCodeRequest $request, PhoneVerificationAction $phoneVerificationAction, UserAction $userAction){
        $data = $request->validated();
        $response = $phoneVerificationAction->confirmCode($request);
        if (!$response) {
            return response()->json([
                'status'  => false,
                'message' => __("ingaz.code_is_wrong"),
            ], 422);
        }

        $user = $userAction->find($response->getData()->data->id);
        $encryptedPhone = Crypt::encryptString($user->phone);

         return response()->json([
            'status'  => true,
            'message' => __('ingaz.code_confirm_successfully'),
            'data'    => [
                'phone'           => $data['phone'],
                'encrypted_phone' => $encryptedPhone,
                'next_step'       => 'verify_otp'
            ]
        ], 200);
    }

    public function reset_password(ResetPasswordRequest $request, PhoneVerificationAction $phoneVerificationAction)
    {
        $data = $request->validated();

        unset($data['g-recaptcha-response']);

        // إرسال كود التحقق
        $phoneVerificationAction->storeSendCode($data, $request);

        $encryptedPhone = Crypt::encryptString($data['phone']);

        return response()->json([
            'status'  => true,
            'message' => __('ingaz.code_send_successfully'),
            'data'    => [
                'phone'           => $data['phone'],
                'encrypted_phone' => $encryptedPhone,
                'next_step'       => 'verify_otp'
            ]
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
