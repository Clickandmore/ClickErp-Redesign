<?php

namespace App\Http\Controllers\site;

use App\Http\Controllers\Controller;

class ContactUsController extends Controller
{
    public function index()
    {
        return view('site.pages.contact');
    }


}
