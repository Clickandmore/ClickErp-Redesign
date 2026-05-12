<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\auth\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        $agent = Auth::guard('agent')->user();

        if ($agent && $agent->isExternalOfficeAgent()) {
            return redirect()->route('agent.dashboard');
        }

        return view('agent.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'البريد الإلكتروني أو رقم الهاتف مطلوب.',
            'password.required' => 'كلمة المرور مطلوبة.',
        ]);

        $login = trim((string) $request->input('login'));
        $agent = Admin::query()
            ->where('email', $login)
            ->orWhere('phone', $login)
            ->first();

        if (! $agent || ! Hash::check((string) $request->input('password'), (string) $agent->password)) {
            return back()
                ->withErrors(['login' => 'بيانات الدخول غير صحيحة.'])
                ->withInput($request->only('login'));
        }

        if (($agent->status ?? 'active') === 'suspended') {
            return back()
                ->withErrors(['login' => 'هذا الحساب موقوف حالياً.'])
                ->withInput($request->only('login'));
        }

        if (! $agent->isExternalOfficeAgent()) {
            return back()
                ->withErrors(['login' => 'هذا الحساب غير مرتبط بمكتب خارجي.'])
                ->withInput($request->only('login'));
        }

        Auth::guard('agent')->login($agent, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('agent.dashboard'));
    }

    public function logout(Request $request)
    {
        $keepAdminSession = Auth::guard('admin')->check();

        Auth::guard('agent')->logout();
        $request->session()->forget('_agent_impersonate_office_id');

        if (! $keepAdminSession) {
            $request->session()->invalidate();
        }

        $request->session()->regenerateToken();

        return redirect()->route('agent.login.form');
    }
}
