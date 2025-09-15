<?php

namespace App\Http\Controllers\site\profile;

use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    public function index()
    {
        return view('site.pages.profile.notifications');
    }


}
