<?php

namespace App\Http\Controllers\site\auth;

use App\Http\Actions\ingaz\PhoneVerificationAction;
use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\auth\ChangePasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

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
        return jsonSuccess(['redirect_url' => route('site.login.index')], __("ingaz.change_password_successfully"));
    }

    public function update(ChangePasswordRequest $request, $id, UserAction $userAction)
    {
        $data = $request->validated();
        $user = $userAction->find($id);
        $user->update(['password' => Hash::make($data['password'])]);
        return jsonSuccess(null, __("ingaz.change_password_successfully"));
    }

}
