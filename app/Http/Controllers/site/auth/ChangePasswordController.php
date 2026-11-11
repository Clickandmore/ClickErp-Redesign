<?php

namespace App\Http\Controllers\site\auth;

use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\site\auth\ChangePasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Str;

class ChangePasswordController extends Controller
{
    public function index(Request $request)
    {
        $phone = Crypt::decryptString($request->phone);
        return view('site.pages.auth.change_password', compact('phone'));
    }

    public function store(ChangePasswordRequest $request, UserAction $userAction)
    {
        $data = $request->validated();

        // لا تغيير لكلمة المرور إلا بعد تأكيد كود التحقق لنفس الرقم
        if (!\App\Support\PasswordResetGrant::consume($data['phone'])) {
            return jsonSuccess(null, app()->getLocale() === 'en' ? 'Verify the code sent to your phone first.' : 'يجب تأكيد كود التحقق المرسل إلى جوالك أولاً.', 403);
        }
        $user = $userAction->getWhereFirst(['phone' => $data['phone']]);
        $user->update(['password' => Hash::make($data['password'])]);

        // إبطال الجلسات القديمة
        $user->setRememberToken(\Illuminate\Support\Str::random(60));
        $user->save();

        // logout from other devices
        Auth::logoutOtherDevices($data['password']);

        $target = session()->pull('url.intended', route('site.login.index'));
        return jsonSuccess(['redirect_url' => $target], __("ingaz.change_password_successfully"));
    }

    // من صفحة إعدادات العميل: كان يغيّر كلمة مرور أي عميل برقمه التسلسلي بلا تسجيل دخول،
    // الآن يغيّر كلمة مرور العميل المسجّل دخوله فقط
    public function update(ChangePasswordRequest $request, $id)
    {
        $user = Auth::guard('web')->user();

        if (!$user || (int) $user->id !== (int) $id) {
            abort(403);
        }

        $data = $request->validated();
        $user->update(['password' => Hash::make($data['password'])]);

        return jsonSuccess(null, __("ingaz.change_password_successfully"));
    }

}
