<?php

namespace App\Http\Controllers\site\profile;

use App\Http\Controllers\Controller;

class ProfileController extends Controller
{
    public function index()
    {
        return view('site.pages.profile.index');
    }


}
