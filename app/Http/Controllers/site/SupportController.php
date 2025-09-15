<?php

namespace App\Http\Controllers\site;

use App\Http\Actions\ingaz\QuestionAction;
use App\Http\Controllers\Controller;

class SupportController extends Controller
{
    public function index(QuestionAction $questionAction)
    {
        $questions = $questionAction->get()->where('is_show',1);
        return view('site.pages.support',get_defined_vars());
    }


}
