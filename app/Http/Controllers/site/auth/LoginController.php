<?php

namespace App\Http\Controllers\site\auth;

use App\Http\Actions\site\auth\LoginAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\auth\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function index()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('site.home.index');
        }
        return view('site.pages.auth.login');
    }

    public function store(LoginRequest $request, LoginAction $loginAction)
    {
        if (RateLimiter::tooManyAttempts('login', 1)) {
            throw ValidationException::withMessages([
                'phone' => [__('web.wait_before_retry')],
            ]);
        }
        RateLimiter::hit('login');
        $data = $request->validated();
        unset($data['g-recaptcha-response']);

        $result = $loginAction->login($data);

        if ($result['success']) {
            $target = session()->pull('url.intended', route('site.home.index'));
            return response()->json([
                'success' => true,
                'message' => __("ingaz.login_successfully"),
                'result'  => $target,
            ]);
        }
        return response()->json([
            'success' => false,
            'message' => __("ingaz.incorrect_password"),
        ], 422);
    }


}
