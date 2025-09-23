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
use App\Mail\ResetPasswordMail;
use App\Models\office\Office as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Swift_TransportException;
use Yajra\DataTables\Facades\DataTables;

class SendEmailResetPasswordController extends Controller
{
    public function store(SendEmailResetPasswordRequest $request, AdminAction $adminAction, OfficeAction $action)
    {
        $office_id = $request->input('office_id');
        $office    = $action->find($office_id);
        $validator = Validator::make(
            ['manager_email' => $office->manager_email],
            ['manager_email' => 'required|email:rfc,dns']
        );

        if ($validator->fails()) {
            return jsonSuccess(null, $validator->errors()->first('manager_email'), 422);
        }
        $admin = $adminAction->getWhereFirst(['email' => $office->manager_email]);
        if (! $admin) {
            return jsonSuccess(null, __('ingaz.office_not_found'), 422);
        }
        $token = Str::random(60);
        $admin->reset_password_token = $token;
        $admin->save();
        $resetUrl = route('reset-password.index', [
            'token' => $admin->reset_password_token,
            'email' => $admin->email
        ]);
        try {
            Mail::to($admin->email)->send(new ResetPasswordMail($resetUrl));
            return jsonSuccess(null, __('ingaz.mail_sent_successfully'), 200);

        } catch (Swift_TransportException $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Mail sending failed: '.$e->getMessage(),
            ], 500);
        }


    }

}
