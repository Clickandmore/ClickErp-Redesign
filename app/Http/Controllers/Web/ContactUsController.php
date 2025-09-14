<?php

namespace App\Http\Controllers\Web;

use App\Http\Actions\ingaz\ContactAction;
use App\Http\Actions\web\HomeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\ContactRequest;
use Illuminate\Http\Request;

class ContactUsController extends Controller
{
    public $postData = ['name','phone','email','subject','message'];

    public function index() {
        return view('web.ingaz.contact_us');
    }

    public function store(ContactRequest $request, ContactAction $action)
    {
        $dataInsert = $request->only($this->postData);
        $data = $action->store($dataInsert);
        return jsonSuccess($data);
    }

}
