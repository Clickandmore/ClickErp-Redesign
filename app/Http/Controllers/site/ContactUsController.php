<?php

namespace App\Http\Controllers\site;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ingaz\ContactController;
use App\Http\Requests\site\ContactRequest;
use App\Models\ingaz\Contact;

class ContactUsController extends Controller
{
    public function index()
    {
        return view('site.pages.contact_us');
    }

    public function store(ContactRequest $request, ContactController $service)
    {
        $data = $request->validated();
        unset($data['g-recaptcha-response']);
        Contact::create($data);
        return jsonSuccess(null, null, 201);
    }


}
