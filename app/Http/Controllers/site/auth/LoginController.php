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
        $result = $loginAction->login($data);
        if ($result['success']) {
            return jsonSuccess(['result' => $result['redirect_url']], __("ingaz.login_successfully"));
        }
        return jsonSuccess(['result' => $result['redirect_url']], __("ingaz.incorrect_password"), 422);

    }

}
