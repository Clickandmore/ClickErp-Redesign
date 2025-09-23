<?php

namespace App\Http\Actions\site\auth;

use App\Http\Actions\MainAction;
use App\Models\ingaz\User;
use Illuminate\Support\Facades\Auth;

class LoginAction extends MainAction
{
    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function login($credentials)
    {
        if (Auth::guard('web')->attempt($credentials)) {
            return [
                'success'      => true,
                'redirect_url' => route('site.profile.index'),
            ];
        }
        return [
            'success'      => false,
            'redirect_url' => route('site.login.index'),
        ];

    }
}
