<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserType;
use App\Http\Actions\ingaz\PhoneVerificationAction as objAction;
use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\auth\ConfirmCodeRequest;
use App\Http\Requests\ingaz\auth\SendCodeRequest;
use App\Models\ingaz\PhoneVerification;
use App\Models\ingaz\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    /**
     * @var string[]  fillable data
     */

    public $postData = ['phone_code','phone'];

    public function index()
    {
        return view('web.ingaz.auth.index');
    }

    public function resetPassword()
    {
        return view('web.ingaz.auth.reset_password');
    }

    public function sendOtp(SendCodeRequest $request, objAction $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->storeSendCode($dataInsert,$request);
        Session::put('code', $data['code']);

        return redirect('otp?phone='.request()->phone)->with($data);
    }

    public function otp(objAction $action)
    {
        $data['user'] = $action->getWhere(['phone'=>request()->phone])->first();
        return view('web.ingaz.auth.otp',$data);
    }

    public function confirm_code(ConfirmCodeRequest $request, objAction $action)
    {
        $response = $action->confirmCode($request);

        if (!$response) {
            return response()->json([
                'code' => 422,
                'message' => __('api.The code is wrong')
            ], 422);
        }

        $user = User::where('phone', $request->phone)
            ->where('phone_code', $request->phone_code)
            ->first();

        if ($user) {
            Auth::guard('web')->login($user);
            return response()->json([
                'code' => 200,
                'message' => __('api.Code verified successfully')
            ]);
        }

        // Create new user with first and last name
        $user = User::create([
            'phone' => $request->phone,
            'phone_code' => $request->phone_code,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'name' => $request->first_name . ' ' . $request->last_name,
            'status' => 1
        ]);

        Auth::guard('web')->login($user);
        return response()->json([
            'code' => 200,
            'message' => __('api.Code verified successfully')
        ]);
    }

    public function profile()
    {
        return view('web.ingaz.auth.profile');
    }

    public function updateProfile(Request $request, UserAction $action)
    {
        $dataInsert = $request->only(['name', 'email']);
        $data = $action->updateUser(auth()->id(),$dataInsert);
        toastr()->addSuccess(helperTrans('web.data_updated_successfully'));
        return back();
    }

    public function logout()
    {
        Auth::guard('web')->logout();
        return redirect('/');
    }
}
