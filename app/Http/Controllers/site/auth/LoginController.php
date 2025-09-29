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
