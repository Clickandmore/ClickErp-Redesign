<?php

namespace App\Http\Controllers\site\auth;

use App\Http\Actions\site\auth\LoginAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\site\auth\LoginRequest;

class LoginController extends Controller
{
    public function index()
    {
        return view('site.pages.auth.login');
    }

    public function store(LoginRequest $request, LoginAction $loginAction)
    {
        $data = $request->validated();
        unset($data['g-recaptcha-response']);

        $result = $loginAction->login($data);

        if ($result['success']) {
            // URL محفوظ في السيشن (في حالة عدم وجود قيمة في localStorage عند العميل)
            $target = session()->pull('url.intended', $result['redirect_url'] ?? route('site.home.index'));

            return jsonSuccess([
                'result'            => $target,
                // 🔑 إشعار للواجهة الأمامية إننا نسمح باستخدام localStorage
                'use_localstorage'  => true,
            ], __("ingaz.login_successfully"));
        }

        return jsonSuccess([
            'result' => $result['redirect_url']
        ], __("ingaz.incorrect_password"), 422);

    }

}
