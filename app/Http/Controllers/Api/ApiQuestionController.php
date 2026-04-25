<?php

namespace App\Http\Controllers\Api;

use App\Http\Actions\ingaz\QuestionAction;
use App\Http\Controllers\admin\Controller;

class ApiQuestionController extends Controller
{
    public function index(QuestionAction $questionAction){
        $questions = $questionAction->get()->where('is_show', 1);

        return apiResponse(true, __('api.All items'), $questions, 200);
    }
}
