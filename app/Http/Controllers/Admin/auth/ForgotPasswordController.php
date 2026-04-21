<?php

namespace App\Http\Controllers\admin\auth;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\auth\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function create()
    {
        if (auth()->guard('admin')->check()) {
            return redirect()->route('dashboard.index');
        }

        return view('admin.auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc'],
        ]);

        $message = __('admin.reset_password_email_sent_notice');

        $admin = Admin::query()
            ->where('email', $validated['email'])
            ->first();

        if (!$admin) {
            return back()->with('status', $message)->withInput([
                'email' => $validated['email'],
            ]);
        }

        $token = Str::random(64);

        $admin->forceFill([
            'reset_password_token' => $token,
        ])->save();

        $resetUrl = route('admin.password.reset', [
            'token' => $token,
            'email' => $admin->email,
        ]);

        try {
            Mail::to($admin->email)->send(new ResetPasswordMail($resetUrl));
        } catch (\Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['email' => __('admin.reset_password_email_failed')])
                ->withInput([
                    'email' => $validated['email'],
                ]);
        }

        return back()->with('status', $message)->withInput([
            'email' => $validated['email'],
        ]);
    }
}
