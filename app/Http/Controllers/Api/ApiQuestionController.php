<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Actions\ingaz\QuestionAction;

class ApiQuestionController extends Controller
{
    public function index(QuestionAction $questionAction){
        $questions = $questionAction->get()->where('is_show', 1);

        return apiResponse(true, __('api.All items'), $questions, 200);
    }
}
