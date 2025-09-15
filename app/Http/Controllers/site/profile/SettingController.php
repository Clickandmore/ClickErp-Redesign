<?php

namespace App\Http\Controllers\site\profile;

use App\Http\Controllers\Controller;

class SettingController extends Controller
{
    public function index()
    {
        return view('site.pages.profile.settings');
    }


}
