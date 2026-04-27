<?php

namespace App\Http\Controllers\admin\auth;

use App\Http\Controllers\admin\Controller;
use App\Models\auth\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function edit(Request $request)
    {
        if (auth()->guard('admin')->check()) {
            return redirect()->route('dashboard.index');
        }

        $email = (string) $request->query('email');
        $token = (string) $request->query('token');

        abort_unless($this->hasValidResetRequest($email, $token), 404);

        return view('admin.auth.reset-password', compact('email', 'token'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => __('ingaz.new_password_required'),
            'password.min' => __('ingaz.new_password_min'),
            'password.confirmed' => __('admin.reset_password_confirmed'),
        ]);

        $admin = Admin::query()
            ->where('email', $validated['email'])
            ->first();

        if (!$admin || $admin->reset_password_token !== $validated['token']) {
            return back()
                ->withErrors(['email' => __('admin.reset_password_invalid_link')])
                ->withInput([
                    'email' => $validated['email'],
                ]);
        }

        $admin->forceFill([
            'password' => Hash::make($validated['password']),
            'reset_password_token' => null,
            'remember_token' => null,
        ])->save();

        return redirect()
            ->route('admin.form.login')
            ->with('status', __('admin.reset_password_success'));
    }

    private function hasValidResetRequest(string $email, string $token): bool
    {
        if ($email === '' || $token === '') {
            return false;
        }

        return Admin::query()
            ->where('email', $email)
            ->where('reset_password_token', $token)
            ->exists();
    }
}
