<?php

namespace App\Http\Controllers\Web;

use App\Http\Actions\ingaz\AppVisitAction;
use App\Http\Actions\web\HomeAction;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HomeController extends Controller
{

    public function index(HomeAction $homeAction, AppVisitAction $appVisitAction, Request $request)
    {
        if (!isset($_COOKIE['web_cookie'])) {

            setcookie('web_cookie', Str::uuid());
            return redirect('/');
        }
        $data = $homeAction->index($appVisitAction, $request);
        return view('web.ingaz.index', $data);
    }

    public function privacyPolicy()
    {
        return view('web.ingaz.privacy_policy');
    }

    public function termsConditions()
    {
        return view('web.ingaz.terms_conditions');
    }
    public function rightsMaids()
    {
        return view('web.ingaz.rights_maids');
    }

    public function rightsUsers()
    {
        return view('web.ingaz.rights_users');
    }
}