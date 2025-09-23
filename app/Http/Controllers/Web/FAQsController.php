<?php

namespace App\Http\Controllers\Web;

use App\Http\Actions\ingaz\QuestionAction;
use App\Http\Controllers\Controller;

class FAQsController extends Controller
{

    public function index(QuestionAction $action) {
        $data['faqs'] = $action->get();
        return view('web.ingaz.faqs',$data);
    }

}
