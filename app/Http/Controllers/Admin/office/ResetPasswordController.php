<?php

namespace App\Http\Controllers\admin\office;

use App\Http\Actions\auth\AdminAction;
use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;

class ResetPasswordController extends Controller
{
    public function index(Request $request, AdminAction $action)
    {
        $email = $request->email;
        $token = $request->token;
        $admin = $action->getWhereFirst(['email' => $email]);
        if (!$admin || $admin->reset_password_token != $token) {
            abort(404);
        }
        return view('admin.office.reset_password.index', compact('admin'));
    }

}
