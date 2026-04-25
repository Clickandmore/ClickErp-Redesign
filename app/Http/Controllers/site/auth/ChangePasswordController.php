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

    public function update(ChangePasswordRequest $request, $id, UserAction $userAction)
    {
        $data = $request->validated();
        $user = $userAction->find($id);
        $user->update(['password' => Hash::make($data['password'])]);
        return jsonSuccess(null, __("ingaz.change_password_successfully"));
    }

}
