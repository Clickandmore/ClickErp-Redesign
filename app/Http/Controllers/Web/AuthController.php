<?php

namespace App\Http\Controllers\Web;

use App\Enums\UserType;
use App\Http\Actions\ingaz\PhoneVerificationAction as objAction;
use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\auth\ConfirmCodeRequest;
use App\Http\Requests\ingaz\auth\RegisterRequest;
use App\Http\Requests\ingaz\auth\SendCodeRequest;
use App\Http\Requests\ingaz\auth\UpdateProfileRequest;
use App\Models\ingaz\PhoneConfirmCode;
use App\Models\ingaz\PhoneVerification;
use App\Models\ingaz\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;


class AuthController extends Controller
{
    /**
     * @var string[]  fillable data
     */

    public $postData = ['phone_code','phone','first_name','last_name','name',
        'latitude','longitude','address','commercial_register','register_from',
        'email','password','password_confirmation','type','logo'];

    public function index()
    {
        return view('web.ingaz.auth.index');
    }

    public function resetPassword()
    {
        return view('web.ingaz.auth.reset_password');
    }

    public function login()
    {
        $maidId = Request('maid_id') ?? null;
        $typeId = Request('order_type') ?? null;

        if($maidId && $typeId){
            Session::put('redirect_maid_id', $maidId);
            Session::put('redirect_order_type', $typeId);
        }

        return view('web.ingaz.auth.login');
    }

    public function sendLogin(Request $request, UserAction $action)
    {

        $user = User::where(['phone'=> $request->phone,'phone_code'=>$request->phone_code])->first();
        if (!$user) {
            toastr()->addError(helperTrans('web.mobile_number_error'));
            return redirect()->back();
        }
        if (!Hash::check($request->password, $user->password)) {
            toastr()->addSuccess(helperTrans('web.password_error'));
            return redirect()->back();
        }
        Auth::guard('web')->login($user);

        if (session()->has('redirect_maid_id') && session()->has('redirect_order_type')) {

            $maidId = session('redirect_maid_id');
            $orderType = session('redirect_order_type');
            // dd('im in', $maidId, $orderType);
            // Clear the session variables
            session()->forget(['redirect_maid_id', 'redirect_order_type']);

            // Redirect to the appropriate route
            return redirect()->route('web.maids.show', ['maid' => $maidId, 'order_type' => $orderType]);
        }

        return redirect('/');
    }
    public function register()
    {
        return view('web.ingaz.auth.register');
    }

    public function registerStore()
    {
        $data['user'] = PhoneVerification::where(['code'=>\request('code')])->first();
        return view('web.ingaz.auth.register_store',$data);
    }

    public function profile()
    {
        return view('web.ingaz.auth.profile');
    }

    public function sendRegister(Request $request, UserAction $action)
    {

        $code = PhoneVerification::where(['code'=>$request->code,'phone'=>$request->phone])->first();
        $dataInsert = $request->only($this->postData);
        $dataInsert['name'] = $request->name;
        $dataInsert['phone'] = $code->phone;
        $dataInsert['phone_code'] = $code->phone_code;
        $data = $action->storeUser($dataInsert,$request);
        Auth::guard('web')->login($data);
        //session(['code' => $data['code']]);

        return redirect('/');
    }

    public function sendRegisterStore(RegisterRequest $request, UserAction $action)
    {


        $dataInsert = $request->only($this->postData);
        $dataInsert['status'] = 1;
        $data = $action->storeUser($dataInsert,$request);
        Auth::guard('web')->login($data);
        //session(['code' => $data['code']]);
        return jsonSuccess(['message' =>helperTrans('web.account_created_successfully')]);

    }

    public function updateProfile(UpdateProfileRequest $request, UserAction $action)
    {
        $dataInsert = $request->only($this->postData);
        $dataInsert['name'] = $request->name;
        $data = $action->updateUser(auth()->id(),$dataInsert);

        //session(['code' => $data['code']]);
        toastr()->addSuccess(helperTrans('web.data_updated_successfully'));
        return back();
    }

    public function sendOtp(SendCodeRequest $request, objAction $action)
    {
        $user = User::where(['phone'=> $request->phone,'phone_code'=>$request->phone_code])->first();
        if ($user && !$request->reset) {
            toastr()->addInfo(helperTrans('web.number_has_account_login'));
            return redirect()->back();
        }
        $dataInsert = $request->only($this->postData);
        $data = $action->storeSendCode($dataInsert,$request);
        Session::put('code', $data['code']);

        //session(['code' => $data['code']]);
        return redirect('otp?phone='.request()->phone.'&type='.request()->submit)->with($data);
    }

    public function otp(objAction $action)
    {
        $data['user'] = $action->getWhere(['phone'=>request()->phone])->first();
        return view('web.ingaz.auth.otp',$data);
    }

    public function confirm_code(ConfirmCodeRequest $request, objAction $action)
    {
        $data = $action->confirmCode($request);


        if (!$data) {
            return response()->json([
                'code' => 422,
                'data' => $data,
                'message' => __('api.The code is wrong')
            ], 422);
        } else {
            return $data;
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout() {
        auth()->logout();
        return redirect()->route('web.auth');
    }

    public function deleteAccount(UserAction $action) {
        $user_id = auth()->id();
        auth()->logout();
        $action->delete($user_id);
        return redirect()->route('web.home');
    }
}
