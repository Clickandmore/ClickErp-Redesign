<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\auth\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        $agent = Auth::guard('agent')->user();

        if ($agent && $agent->isExternalOfficeAgent()) {
            return redirect($this->localizedRoute('agent.dashboard'));
        }

        return view('agent.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => app()->getLocale() === 'ar' ? 'اسم المستخدم أو البريد الإلكتروني مطلوب.' : 'Username or email is required.',
            'password.required' => app()->getLocale() === 'ar' ? 'كلمة المرور مطلوبة.' : 'Password is required.',
        ]);

        $login = trim((string) $request->input('login'));
        $agent = Admin::query()
            ->whereHas('agentOfficeLinks')
            ->where(function ($query) use ($login) {
                $query->where('email', $login)
                    ->orWhere('username', $login)
                    ->orWhere('phone', $login);
            })
            ->first();

        if (! $agent || ! Hash::check((string) $request->input('password'), (string) $agent->password)) {
            return back()
                ->withErrors(['login' => app()->getLocale() === 'ar' ? 'بيانات تسجيل الدخول غير صحيحة.' : 'The login credentials are invalid.'])
                ->withInput($request->only('login'));
        }

        if (($agent->status ?? 'active') === 'suspended') {
            return back()
                ->withErrors(['login' => app()->getLocale() === 'ar' ? 'هذا الحساب موقوف حالياً.' : 'This account is currently suspended.'])
                ->withInput($request->only('login'));
        }

        if (! $agent->isExternalOfficeAgent()) {
            return back()
                ->withErrors(['login' => app()->getLocale() === 'ar' ? 'هذا الحساب غير مرتبط بوكيل خارجي.' : 'This account is not linked to an external office.'])
                ->withInput($request->only('login'));
        }

        Auth::guard('agent')->login($agent, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect($this->localizedRoute('agent.dashboard'));
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

        return redirect($this->localizedRoute('agent.login.form'));
    }

    private function localizedRoute(string $routeName, array $parameters = []): string
    {
        return LaravelLocalization::getLocalizedURL(app()->getLocale(), route($routeName, $parameters), [], true);
    }
}
