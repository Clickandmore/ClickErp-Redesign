<?php

namespace App\Http\Controllers\office;

use App\Enums\Office\OfficesStatusEnum;
use App\Http\Actions\auth\AdminAction;
use App\Http\Actions\ingaz\JobTitleAction;
use App\Http\Actions\ingaz\JobTypeAction;
use App\Http\Actions\ingaz\NationalityAction;
use App\Http\Actions\office\AdminOfficeAction;
use App\Http\Actions\office\JopTypeOfficeAction;
use App\Http\Actions\office\OfficeAction;
use App\Http\Actions\office\OfficeAction as objAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\office\OfficeRequest;
use App\Http\Requests\office\SendEmailResetPasswordRequest;
use App\Models\office\Office as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

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
