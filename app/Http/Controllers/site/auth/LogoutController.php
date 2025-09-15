<?php

namespace App\Http\Controllers\site\auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    public function index()
    {
        Auth::logout();
        return redirect(route('site.home.index'));
    }


}
